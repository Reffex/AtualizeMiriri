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
        // Validação de parâmetros
        if (!$mysqli || !$operacao || !$lancamentos_result) {
            throw new Exception("Parâmetros inválidos para cálculo");
        }

        // Parâmetros obrigatórios (da primeira imagem)
        $indexador = $operacao['indexador'] ?? 'CDI (CETIP) Diário';
        $periodicidade = $operacao['periodicidade'] ?? 'Mensal';
        $correcao_pct = (float) ($operacao['atualizar_correcao_monetaria'] ?? 100) / 100;
        $juros_pct_mensal = (float) ($operacao['atualizar_juros_nominais'] ?? 0) / 100;
        $dia_debito = (int) ($operacao['atualizar_dia_debito'] ?? 1);
        $data_fim = new DateTime($operacao['atualizar_ate'] ?? date('Y-m-d'));

        // Parâmetros adicionais (da segunda imagem)
        $divisor_juros = ($operacao['divisor_juros'] ?? 360) == 360 ? 360 : 365;
        $tipo_juros = $operacao['tipo_juros'] ?? 'composto';
        $valores_negativos = (bool) ($operacao['valores_negativos'] ?? false);
        $corrigir_saldos = (bool) ($operacao['corrigir_saldos'] ?? false);
        $indexador_mensal = $operacao['indexador_mensal'] ?? 'mes_atual';
        $tipo_lancamento = $operacao['tipo_lancamento'] ?? 'original'; // 'original' ou 'reais'
        $modo_operacao = strtolower($operacao['modo_operacao'] ?? 'outros'); // 'outros' ou 'credito_rural'
        $resolucao_2471 = (bool) ($operacao['resolucao_2471'] ?? false); // true ou false
        $aplica_juros = true; // Valor padrão


        // Processar lançamentos
        $lancamentos = [];
        while ($row = $lancamentos_result->fetch_assoc()) {
            $lancamentos[] = $row;
        }

        // Ordenar lançamentos por data
        usort($lancamentos, fn($a, $b) => strtotime($a['data']) - strtotime($b['data']));

        $data_inicio = null;
        $saldo = 0.0;

        // Processar cada lançamento
        foreach ($lancamentos as $l) {
            $data_lanc = new DateTime($l['data']);
            $valor = (float) $l['valor'];

            if (strtolower($l['tipo']) === 'debito') {
                if ($data_inicio === null) $data_inicio = clone $data_lanc;
                $saldo -= $valor;
            } else {
                $saldo += $valor;
            }

            $extrato[] = [
                'data' => $data_lanc->format('d/m/Y'),
                'descricao' => $l['descricao'],
                'debito' => strtolower($l['tipo']) === 'debito' ? $valor : 0,
                'credito' => strtolower($l['tipo']) === 'credito' ? $valor : 0,
                'saldo' => $saldo,
                'indice' => '',
                'dias_corridos' => 0
            ];
        }

        if ($data_inicio === null) {
            return array_merge($totais, ['extrato_detalhado' => $extrato]);
        }

        // Datas de correção considerando periodicidade
        $datas_correcao = [];
        $data_corr = clone $data_inicio;
        $data_corr->modify('first day of next month');
        $data_corr->setDate(
            (int) $data_corr->format('Y'),
            (int) $data_corr->format('m'),
            $dia_debito
        );

        while ($data_corr <= $data_fim) {
            $datas_correcao[] = clone $data_corr;
            $data_corr->modify('+1 ' . getPeriodicidadeModifier($periodicidade));
        }

        // Adicionar data final se não estiver na lista
        $ultima_data = end($datas_correcao);
        if ($ultima_data === false || $ultima_data < $data_fim) {
            $datas_correcao[] = clone $data_fim;
        }

        $data_ant = clone $data_inicio;
        foreach ($datas_correcao as $dt_corr) {
            if ($dt_corr < $data_ant) continue;

            $dias = calcularDias($data_ant, $dt_corr, getTipoDiasPorIndexador($indexador));
            if ($dias <= 0) continue;

            // Obter índice do período
            $indice = obter_indice_periodo($mysqli, $data_ant, $dt_corr, $indexador, $indexador_mensal);

            // Aplicar correção monetária se necessário
            if ($correcao_pct > 0 && (!$corrigir_saldos || $saldo < 0)) {
                $fator_correcao = 1 + ($indice / 100) * $correcao_pct;

                // Verificar se deve ignorar valores negativos (deflação)
                if ($valores_negativos && $indice < 0) {
                    $fator_correcao = 1;
                }

                $correcao = $saldo * ($fator_correcao - 1);

                // Se for para corrigir apenas saldos devedores e o saldo for positivo, ignora
                if (!$corrigir_saldos || $saldo < 0) {
                    $saldo += $correcao;
                    $totais['correcao'] += $correcao;

                    $extrato[] = [
                        'data' => $dt_corr->format('d/m/Y'),
                        'descricao' => $dt_corr == $data_fim ? 'Correção Final' : 'Correção Monetária',
                        'debito' => $correcao < 0 ? abs($correcao) : 0,
                        'credito' => $correcao > 0 ? $correcao : 0,
                        'saldo' => $saldo,
                        'indice' => number_format($indice, 4) . '%',
                        'dias_corridos' => $dias
                    ];
                }
            }

            // Aplicar juros se houver saldo devedor
            if ($juros_pct_mensal > 0 && $saldo < 0 && $aplica_juros) {
                if ($tipo_juros === 'composto') {
                    $taxa_diaria = pow(1 + $juros_pct_mensal, 1 / $divisor_juros) - 1;
                    $juros = abs($saldo) * (pow(1 + $taxa_diaria, $dias) - 1);
                } else {
                    // Juros simples
                    $juros = abs($saldo) * $juros_pct_mensal * ($dias / $divisor_juros);
                }

                $saldo -= $juros;
                $totais['juros'] += $juros;

                $extrato[] = [
                    'data' => $dt_corr->format('d/m/Y'),
                    'descricao' => $tipo_juros === 'composto' ? 'Juros Compostos' : 'Juros Simples',
                    'debito' => $juros,
                    'credito' => 0,
                    'saldo' => $saldo,
                    'indice' => number_format($juros_pct_mensal * 100, 3) . '% a.m.',
                    'dias_corridos' => $dias
                ];
            }

            $data_ant = clone $dt_corr;
        }

        // Aplicar multa e honorários se houver saldo devedor
        if ($saldo < 0) {
            $multa = (float) ($operacao['valor_multa'] ?? 0);
            $honorarios = (float) ($operacao['valor_honorarios'] ?? 0);

            if ($multa > 0) {
                $saldo -= $multa;
                $totais['multa'] = $multa;
                $extrato[] = [
                    'data' => $data_fim->format('d/m/Y'),
                    'descricao' => 'Multa',
                    'debito' => $multa,
                    'credito' => 0,
                    'saldo' => $saldo,
                    'indice' => 'Valor Fixo',
                    'dias_corridos' => 0
                ];
            }

            if ($honorarios > 0) {
                $saldo -= $honorarios;
                $totais['honorarios'] = $honorarios;
                $extrato[] = [
                    'data' => $data_fim->format('d/m/Y'),
                    'descricao' => 'Honorários',
                    'debito' => $honorarios,
                    'credito' => 0,
                    'saldo' => $saldo,
                    'indice' => 'Valor Fixo',
                    'dias_corridos' => 0
                ];
            }

            if ($tipo_lancamento === 'reais') {
                foreach ($lancamentos as &$l) {
                    $data_lanc = new DateTime($l['data']);
                    if ($data_lanc >= $data_fim) continue;

                    $indice = obter_indice_periodo($mysqli, $data_lanc, $data_fim, $indexador, $indexador_mensal);

                    if ($valores_negativos && $indice < 0) {
                        $indice = 0; // Ignora deflação
                    }

                    $fator = 1 + ($indice / 100);
                    $l['valor'] = (float) $l['valor'] * $fator;
                    $l['descricao'] .= ' (Corrigido até hoje)';
                }
            }

            if ($resolucao_2471) {
                $tipo_juros = 'simples';
                $corrigir_saldos = true;

                if (stripos($indexador, 'CDI') !== false) {
                    $divisor_juros = 252;
                } else {
                    $divisor_juros = 365;
                }

                // Caso queira aplicar juros diários ao invés de mensal:
                $juros_pct_mensal = $juros_pct_mensal; // mantém
            }

            // Modo de operação: Crédito Rural -> NÃO aplica juros
            if ($modo_operacao === 'credito_rural') {
                $aplica_juros = false;
            } else {
                $aplica_juros = true;
            }
        }

        // Calcular totais
        $totais['saldo_atualizado'] = $saldo;
        $totais['movimentacao'] = array_reduce($extrato, fn($carry, $item) => $carry + abs($item['debito']) + abs($item['credito']), 0);

        return array_merge($totais, ['extrato_detalhado' => $extrato]);
    } catch (Exception $e) {
        error_log("Erro ao calcular operação: " . $e->getMessage());
        return array_merge($totais, ['extrato_detalhado' => $extrato, 'erro' => $e->getMessage()]);
    }
}

// Função para obter índice do período considerando dias úteis
function obter_indice_periodo($mysqli, $data_inicio, $data_fim, $indexador, $modo = 'mes_atual')
{
    // Índices considerados mensais (usam valor fixo do mês)
    $indices_mensais = ['IPCA', 'INPC'];

    foreach ($indices_mensais as $mensal) {
        if (stripos($indexador, $mensal) !== false) {
            // Definir mês de referência
            if ($modo === 'mes_anterior') {
                $data_ref = (clone $data_inicio)->modify('first day of previous month');
            } else {
                $data_ref = (clone $data_inicio)->modify('first day of this month');
            }

            $mes_ref = $data_ref->format('Y-m-d');

            $query = "SELECT valor FROM indices WHERE nome = ? AND data_referencia = ? LIMIT 1";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param("ss", $indexador, $mes_ref);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();

            return (float) ($row['valor'] ?? 0.0);
        }
    }

    // Para índices diários (ex: CDI, SELIC), somar os valores do período
    $inicio = $data_inicio->format('Y-m-d');
    $fim = $data_fim->format('Y-m-d');

    if (stripos($indexador, 'CDI') !== false) {
        $query = "SELECT valor 
                  FROM indices 
                  WHERE nome = ? 
                    AND data_referencia BETWEEN ? AND ? 
                    AND DAYOFWEEK(data_referencia) NOT IN (1,7)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("sss", $indexador, $inicio, $fim);
        $stmt->execute();
        $res = $stmt->get_result();

        $fator_total = 1.0;
        while ($row = $res->fetch_assoc()) {
            $cdi_diario = (float) $row['valor'];
            $fator_total *= (1 + ($cdi_diario / 100));
        }

        $indice_acumulado = ($fator_total - 1) * 100;
        return $indice_acumulado;
    }
}
// Funções auxiliares
function getPeriodicidadeModifier($periodicidade)
{
    switch ($periodicidade) {
        case 'Trimestral':
            return '3 months';
        case 'Semestral':
            return '6 months';
        case 'Anual':
            return '1 year';
        default:
            return '1 month'; // Mensal
    }
}

function getTipoDiasPorIndexador($indexador)
{
    $tipos = [
        'CDI (CETIP) Diário' => 'uteis',
        'SELIC' => 'corridos',
        'IPCA' => 'mes_completo',
        'INPC' => 'mes_completo',
    ];
    return $tipos[$indexador] ?? 'corridos';
}

function calcularDias($inicio, $fim, $tipo = 'corridos')
{
    switch ($tipo) {
        case 'uteis':
            return calcularDiasUteis($inicio, $fim);
        case 'mes_completo':
            return calcularDiasMesCompleto($inicio, $fim);
        default:
            return $inicio->diff($fim)->days;
    }
}

function calcularDiasUteis($inicio, $fim)
{
    $diasUteis = 0;
    $periodo = new DatePeriod(
        $inicio,
        new DateInterval('P1D'),
        $fim // Remova o +1 day aqui
    );

    foreach ($periodo as $data) {
        $diaSemana = $data->format('N');
        if ($diaSemana < 6) { // 1-5 = segunda a sexta
            $diasUteis++;
        }
    }
    return $diasUteis;
}

function calcularDiasMesCompleto($inicio, $fim)
{
    $diasMes = cal_days_in_month(CAL_GREGORIAN, $inicio->format('m'), $inicio->format('Y'));
    $diasPeriodo = $inicio->diff($fim)->days + 1;
    return $diasPeriodo / $diasMes; // Retorna fração do mês
}
