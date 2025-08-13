<?php
require_once 'funcoes_indices.php';

function calcular_operacao($mysqli, $operacao, $lancamentos_result)
{
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
        $indexador = $operacao['indexador'];
        $periodicidade = strtolower($operacao['periodicidade']);
        $correcao_pct = (float)$operacao['atualizar_correcao_monetaria'] / 100;
        $juros_pct = (float)$operacao['atualizar_juros_nominais'] / 100;
        $dia_debito = (int)$operacao['atualizar_dia_debito'];
        $data_fim = new DateTime($operacao['atualizar_ate']);
        $tipo_juros = $operacao['tipo_juros'] ?? 'composto';
        $valor_multa = (float)$operacao['valor_multa'];
        $valor_honorarios = (float)$operacao['valor_honorarios'];

        $lancamentos = $lancamentos_result->fetch_all(MYSQLI_ASSOC);
        usort($lancamentos, fn($a, $b) => strtotime($a['data']) - strtotime($b['data']));

        $primeiro_debito = null;
        foreach ($lancamentos as $l) {
            if (strtolower($l['tipo']) === 'debito') {
                $primeiro_debito = new DateTime($l['data']);
                break;
            }
        }
        if (!$primeiro_debito) throw new Exception("É necessário pelo menos um débito para iniciar os cálculos.");

        $saldo = 0.0;
        $total_movimentacao = 0.0;

        // ---- INÍCIO DO CÓDIGO CORRIGIDO ----
        
        // 1. Criar um array unificado com todas as datas de eventos (lançamentos e correções)
        $eventos = [];
        foreach ($lancamentos as $l) {
            $eventos[] = ['data' => new DateTime($l['data']), 'tipo' => 'lancamento', 'detalhes' => $l];
        }

        $data_corrente = clone $primeiro_debito;
        $data_corrente->setTime(0, 0, 0); // Zera o horário para evitar problemas de comparação
        
        while ($data_corrente <= $data_fim) {
            $ultimo_dia_mes = (int)$data_corrente->format('t');
            $dia_aplicavel = min($dia_debito, $ultimo_dia_mes);
            $data_atualizacao = new DateTime($data_corrente->format('Y-m-') . str_pad($dia_aplicavel, 2, '0', STR_PAD_LEFT));
            
            if ($data_atualizacao >= $primeiro_debito && $data_atualizacao <= $data_fim) {
                // Adiciona a data de atualização mensal
                $eventos[] = ['data' => $data_atualizacao, 'tipo' => 'correcao'];
            }
            $data_corrente->modify('first day of next month');
        }
        
        // Adiciona a data final do cálculo como um evento de correção
        if ($data_fim > end($eventos)['data']) {
             $eventos[] = ['data' => $data_fim, 'tipo' => 'correcao'];
        }

        // 2. Ordenar todos os eventos cronologicamente
        usort($eventos, fn($a, $b) => $a['data']->getTimestamp() - $b['data']->getTimestamp());
        
        // 3. Processar cada evento em ordem
        $ultima_data_processada = clone $primeiro_debito;
        $ultima_data_processada->modify('-1 day');

        foreach ($eventos as $evento) {
            $data_evento = $evento['data'];
            
            // Ignora eventos com datas iguais ou anteriores à última processada
            if ($data_evento <= $ultima_data_processada) {
                continue;
            }

            // Aplica correção e juros para o período desde a última data processada
            $dias = calcular_dias_corridos($ultima_data_processada, $data_evento, $indexador, $mysqli);
            if ($dias > 0) {
                $indice_pct = obter_indice_periodo($mysqli, $ultima_data_processada, $data_evento, $indexador);
                $fator_correcao = 1 + ($indice_pct / 100) * $correcao_pct;
                $valor_correcao = $saldo * ($fator_correcao - 1);
                $saldo += $valor_correcao;
                $totais['correcao'] += $valor_correcao;

                if (abs($valor_correcao) > 0.0001) {
                    $extrato[] = [
                        'data' => $data_evento->format('d/m/Y'),
                        'descricao' => 'Correção Monetária',
                        'debito' => $valor_correcao < 0 ? abs($valor_correcao) : 0,
                        'credito' => $valor_correcao > 0 ? $valor_correcao : 0,
                        'saldo' => round($saldo, 4),
                        'indice' => number_format($indice_pct, 4),
                        'dias_corridos' => $dias
                    ];
                }

                if ($juros_pct > 0 && $saldo < 0) {
                    if ($tipo_juros === 'composto') {
                        $dias_base_anual = stripos($indexador, 'CDI') !== false ? 252 : 365;
                        $taxa_diaria = pow(1 + $juros_pct, 1 / $dias_base_anual) - 1;
                        $juros = abs($saldo) * (pow(1 + $taxa_diaria, $dias) - 1);
                    } else {
                        $dias_base_anual = stripos($indexador, 'CDI') !== false ? 252 : 365;
                        $juros = abs($saldo) * $juros_pct * ($dias / $dias_base_anual);
                    }

                    $saldo -= $juros;
                    $totais['juros'] -= $juros;

                    if ($juros > 0.0001) {
                        $extrato[] = [
                            'data' => $data_evento->format('d/m/Y'),
                            'descricao' => 'Juros Nominais',
                            'debito' => $juros,
                            'credito' => 0,
                            'saldo' => round($saldo, 4),
                            'indice' => number_format($juros_pct * 100, 3),
                            'dias_corridos' => $dias
                        ];
                    }
                }
            }

            // Aplica os lançamentos que ocorreram nesta data
            if ($evento['tipo'] === 'lancamento') {
                $l = $evento['detalhes'];
                $valor = (float)$l['valor'];
                $tipo = strtolower($l['tipo']);
                $saldo += ($tipo === 'credito') ? $valor : -$valor;
                $total_movimentacao += ($tipo === 'credito') ? $valor : -$valor;

                $extrato[] = [
                    'data' => $data_evento->format('d/m/Y'),
                    'descricao' => $l['descricao'],
                    'debito' => $tipo === 'debito' ? $valor : 0,
                    'credito' => $tipo === 'credito' ? $valor : 0,
                    'saldo' => round($saldo, 4),
                    'indice' => '',
                    'dias_corridos' => 0
                ];
            }
            
            $ultima_data_processada = $data_evento;
        }

        // ---- FIM DO CÓDIGO CORRIGIDO ----
        
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
