<?php
require_once 'funcoes_indices.php';

function calcular_operacao($mysqli, $operacao, $lancamentos_result) {
    $extrato = [];
    $totais = [
        'movimentacao' => 0,
        'correcao' => 0,
        'juros' => 0,
        'multa' => 0,
        'honorarios' => 0,
        'saldo_atualizado' => 0
    ];

    try {
        // Configurações básicas
        $indexador = $operacao['indexador'];
        $periodicidade = strtolower($operacao['periodicidade']);
        $correcao_pct = (float)$operacao['atualizar_correcao_monetaria'] / 100;
        $juros_pct = (float)$operacao['atualizar_juros_nominais'] / 100;
        $dia_debito = (int)$operacao['atualizar_dia_debito'];
        $data_fim = new DateTime($operacao['atualizar_ate']);
        $tipo_juros = $operacao['tipo_juros'];
        $valor_multa = (float)$operacao['valor_multa'];
        $valor_honorarios = (float)$operacao['valor_honorarios'];
        $base_temporal_juros = $operacao['base_temporal_juros'] ?? 'mensal'; // 'mensal' ou 'anual'

        // Converter taxa anual para mensal se necessário
        if ($base_temporal_juros === 'anual') {
            $juros_pct = pow(1 + $juros_pct, 1/12) - 1;
        }

        // Ordenar lançamentos por data
        $lancamentos = $lancamentos_result->fetch_all(MYSQLI_ASSOC);
        usort($lancamentos, fn($a, $b) => strtotime($a['data']) - strtotime($b['data']));

        // Encontrar primeiro débito e seu valor para o cálculo de juros simples
        $primeiro_debito = null;
        $valor_principal_debito = 0.0;
        foreach ($lancamentos as $l) {
            if (strtolower($l['tipo']) === 'debito') {
                $primeiro_debito = new DateTime($l['data']);
                $valor_principal_debito = (float)$l['valor'];
                break;
            }
        }
        if (!$primeiro_debito) {
            throw new Exception("É necessário pelo menos um débito para iniciar os cálculos.");
        }

        // Preparar datas de atualização
        $datas_correcao = [];
        $data_corrente = clone $primeiro_debito;
        
        while ($data_corrente <= $data_fim) {
            $ultimo_dia_mes = (int)$data_corrente->format('t');
            $dia_aplicavel = min($dia_debito, $ultimo_dia_mes);
            $data_atualizacao = new DateTime($data_corrente->format('Y-m-') . str_pad($dia_aplicavel, 2, '0', STR_PAD_LEFT));
            
            if ($data_atualizacao >= $primeiro_debito && $data_atualizacao <= $data_fim) {
                $datas_correcao[] = $data_atualizacao;
            }
            $data_corrente->modify('first day of next month');
        }

        // Adicionar data final ao array de datas de correção, se ainda não estiver presente.
        $ultima_data_correcao_gerada = end($datas_correcao);
        if ($data_fim > $ultima_data_correcao_gerada) {
            $datas_correcao[] = clone $data_fim;
        }

        // Processar cálculos
        $saldo = 0.0;
        $i = 0;
        $total_movimentacao = 0.0;
        $ultima_correcao = clone $primeiro_debito;

        foreach ($datas_correcao as $data_corrente) {
            if ($data_corrente > $ultima_correcao) {
                // Calcular correção monetária
                $dias = calcular_dias_corridos($ultima_correcao, $data_corrente, $indexador, $mysqli);
                $indice_pct = obter_indice_periodo($mysqli, $ultima_correcao, $data_corrente, $indexador);
                $fator_correcao = 1 + ($indice_pct / 100) * $correcao_pct;
                $valor_correcao = $saldo * ($fator_correcao - 1);
                $saldo += $valor_correcao;
                $totais['correcao'] += $valor_correcao;

                if (abs($valor_correcao) > 0.0001) {
                    $extrato[] = [
                        'data' => $data_corrente->format('d/m/Y'),
                        'descricao' => 'Correção Monetária',
                        'debito' => $valor_correcao < 0 ? abs($valor_correcao) : 0,
                        'credito' => $valor_correcao > 0 ? $valor_correcao : 0,
                        'saldo' => round($saldo, 4),
                        'indice' => number_format($indice_pct, 4),
                        'dias_corridos' => $dias
                    ];
                }

                // Calcular juros (apenas para saldo negativo)
                if ($juros_pct > 0 && $saldo < 0) {
                    $dias_corridos_juros = $ultima_correcao->diff($data_corrente)->days;
                    
                    if ($dias_corridos_juros > 0) {
                        if ($tipo_juros === 'composto') {
                            // Juros compostos: calculados sobre o saldo acumulado
                            $juros = abs($saldo) * (pow(1 + $juros_pct, $dias_corridos_juros / 30) - 1);
                        } else {
                            // Juros simples: calculados sobre o valor principal inicial
                            $juros = abs($valor_principal_debito) * $juros_pct * ($dias_corridos_juros / 30);
                        }

                        $saldo -= $juros;
                        $totais['juros'] -= $juros;

                        if ($juros > 0.0001) {
                            $extrato[] = [
                                'data' => $data_corrente->format('d/m/Y'),
                                'descricao' => 'Juros ' . ($tipo_juros === 'composto' ? 'Compostos' : 'Simples'),
                                'debito' => $juros,
                                'credito' => 0,
                                'saldo' => round($saldo, 4),
                                'indice' => number_format($juros_pct * 100, 3) . '',
                                'dias_corridos' => $dias_corridos_juros
                            ];
                        }
                    }
                }

                $ultima_correcao = clone $data_corrente;
            }

            // Processar lançamentos até a data corrente
            while ($i < count($lancamentos)) {
                $l_data = new DateTime($lancamentos[$i]['data']);
                if ($l_data > $data_corrente) break;

                $l = $lancamentos[$i];
                $valor = (float)$l['valor'];
                $tipo = strtolower($l['tipo']);
                $saldo += ($tipo === 'credito') ? $valor : -$valor;
                $total_movimentacao += ($tipo === 'credito') ? $valor : -$valor;

                $extrato[] = [
                    'data' => $l_data->format('d/m/Y'),
                    'descricao' => $l['descricao'],
                    'debito' => $tipo === 'debito' ? $valor : 0,
                    'credito' => $tipo === 'credito' ? $valor : 0,
                    'saldo' => round($saldo, 4),
                    'indice' => '',
                    'dias_corridos' => 0
                ];

                $i++;
            }
        }

        // Aplicar multa e honorários no final (se saldo negativo)
        if ($saldo < 0) {
            if ($valor_multa > 0) {
                $saldo -= $valor_multa;
                $totais['multa'] -= $valor_multa;
                $extrato[] = [
                    'data' => $data_fim->format('d/m/Y'),
                    'descricao' => 'Multa',
                    'debito' => $valor_multa,
                    'credito' => 0,
                    'saldo' => round($saldo, 4),
                    'indice' => 'Manual',
                    'dias_corridos' => 0
                ];
            }

            if ($valor_honorarios > 0) {
                $saldo -= $valor_honorarios;
                $totais['honorarios'] -= $valor_honorarios;
                $extrato[] = [
                    'data' => $data_fim->format('d/m/Y'),
                    'descricao' => 'Honorários',
                    'debito' => $valor_honorarios,
                    'credito' => 0,
                    'saldo' => round($saldo, 4),
                    'indice' => 'Manual',
                    'dias_corridos' => 0
                ];
            }
        }

        $totais['saldo_atualizado'] = round($saldo, 4);
        $totais['movimentacao'] = round($total_movimentacao, 4);

        return array_merge($totais, ['extrato_detalhado' => $extrato]);
    } catch (Exception $e) {
        return ['erro' => 'Erro ao calcular: ' . $e->getMessage()];
    }
}
