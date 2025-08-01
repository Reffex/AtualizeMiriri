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
        if (!$mysqli || !$operacao || !$lancamentos_result) {
            throw new Exception("Parâmetros inválidos para cálculo");
        }

        $indexador = $operacao['indexador'] ?? 'SELIC';
        $periodicidade = $operacao['periodicidade'] ?? 'Mensal';
        $correcao_pct = (float) ($operacao['atualizar_correcao_monetaria'] ?? 100) / 100;
        $juros_pct_mensal = (float) ($operacao['atualizar_juros_nominais'] ?? 0) / 100;
        $dia_debito = (int) ($operacao['atualizar_dia_debito'] ?? 1);
        $data_fim = new DateTime($operacao['atualizar_ate'] ?? date('Y-m-d'));

        $divisor_juros = $operacao['divisor_juros'] ?? 30;
        $tipo_juros = $operacao['tipo_juros'] ?? 'composto';
        $valores_negativos = $operacao['valores_negativos'] ?? 0;
        $corrigir_saldos = $operacao['corrigir_saldos'] ?? 0;
        $indexador_mensal = $operacao['indexador_mensal'] ?? 'mes_anterior';

        // Processar lançamentos
        $lancamentos = [];
        while ($row = $lancamentos_result->fetch_assoc()) {
            $lancamentos[] = $row;
        }

        usort($lancamentos, fn($a, $b) => strtotime($a['data']) - strtotime($b['data']));

        $data_inicio = null;
        $saldo = 0.0;

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
                'debito' => strtolower($l['tipo']) === 'debito' ? -$valor : 0,
                'credito' => strtolower($l['tipo']) === 'credito' ? $valor : 0,
                'saldo' => $saldo,
                'indice' => '',
                'dias_corridos' => 0
            ];
        }

        if ($data_inicio === null) {
            return array_merge($totais, ['extrato_detalhado' => $extrato]);
        }

        // Datas de correção
        $datas_correcao = [];
        $data_corr = DateTime::createFromFormat('Y-m-d', $data_inicio->format('Y-m-') . str_pad($dia_debito, 2, '0', STR_PAD_LEFT));
        if ($data_corr < $data_inicio) $data_corr->modify('+1 ' . getPeriodicidadeModifier($periodicidade));
        while ($data_corr <= $data_fim) {
            $datas_correcao[] = clone $data_corr;
            $data_corr->modify('+1 ' . getPeriodicidadeModifier($periodicidade));
        }

        $data_ant = clone $data_inicio;
        foreach ($datas_correcao as $dt_corr) {
            if ($dt_corr < $data_ant) continue;

            $dias = calcularDias($data_ant, $dt_corr, getTipoDiasPorIndexador($indexador));
            if ($dias <= 0) continue;

            $indice = obter_indice_periodo($mysqli, $data_ant, $dt_corr, $indexador, $indexador_mensal);

            // Correção monetária
            if ($correcao_pct > 0 && abs($saldo) > 0.01) {
                $fator_correcao = 1 + ($indice / 100) * $correcao_pct;
                if (!$valores_negativos && $indice < 0) $fator_correcao = 1;

                $correcao = $saldo * ($fator_correcao - 1);
                $saldo += $correcao;
                $totais['correcao'] += $correcao;

                $extrato[] = [
                    'data' => $dt_corr->format('d/m/Y'),
                    'descricao' => $dt_corr == $data_fim ? 'Correção Final' : 'Correção Monetária',
                    'debito' => $correcao < 0 ? $correcao : 0,
                    'credito' => $correcao > 0 ? $correcao : 0,
                    'saldo' => $saldo,
                    'indice' => number_format($indice, 4) . '%',
                    'dias_corridos' => $dias
                ];
            }

            // Juros
            if ($juros_pct_mensal > 0 && $saldo < 0) {
                if ($tipo_juros === 'composto') {
                    $taxa_diaria = pow(1 + $juros_pct_mensal, 1 / $divisor_juros) - 1;
                    $juros = abs($saldo) * (pow(1 + $taxa_diaria, $dias) - 1);
                } else {
                    $juros = abs($saldo) * $juros_pct_mensal * ($dias / $divisor_juros);
                }

                $saldo -= $juros;
                $totais['juros'] += $juros;

                $extrato[] = [
                    'data' => $dt_corr->format('d/m/Y'),
                    'descricao' => $tipo_juros === 'composto' ? 'Juros Compostos' : 'Juros Simples',
                    'debito' => -$juros,
                    'credito' => 0,
                    'saldo' => $saldo,
                    'indice' => number_format($juros_pct_mensal * 100, 3) . '% a.m.',
                    'dias_corridos' => $dias
                ];
            }

            $data_ant = clone $dt_corr;
        }

        // Multa e honorários
        if ($saldo < 0) {
            $multa = (float) ($operacao['valor_multa'] ?? 0);
            $honorarios = (float) ($operacao['valor_honorarios'] ?? 0);

            if ($multa > 0) {
                $saldo -= $multa;
                $totais['multa'] = $multa;
                $extrato[] = [
                    'data' => $data_fim->format('d/m/Y'),
                    'descricao' => 'Multa',
                    'debito' => -$multa,
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
                    'debito' => -$honorarios,
                    'credito' => 0,
                    'saldo' => $saldo,
                    'indice' => 'Valor Fixo',
                    'dias_corridos' => 0
                ];
            }
        }

        $totais['saldo_atualizado'] = $saldo;
        $totais['movimentacao'] = array_reduce($extrato, fn($carry, $item) => $carry + $item['debito'] + $item['credito'], 0);

        return array_merge($totais, ['extrato_detalhado' => $extrato]);

    } catch (Exception $e) {
        error_log("Erro ao calcular operação: " . $e->getMessage());
        return array_merge($totais, ['extrato_detalhado' => $extrato, 'erro' => $e->getMessage()]);
    }
}

// Consulta de índice real no banco:
function obter_indice_periodo($mysqli, $data_inicio, $data_fim, $indexador, $modo = 'mes_anterior') {
    $inicio = $data_inicio->format('Y-m-d');
    $fim = $data_fim->format('Y-m-d');

    $query = "SELECT SUM(valor) AS total 
              FROM indices 
              WHERE nome = ? 
              AND data_referencia BETWEEN ? AND ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("sss", $indexador, $inicio, $fim);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    return (float) ($row['total'] ?? 0);
}

// As demais funções auxiliares permanecem iguais (getPeriodicidadeModifier, calcularDias, etc.)


// Funções auxiliares (devem estar no mesmo arquivo ou em um include)

function getPeriodicidadeModifier($periodicidade) {
    switch ($periodicidade) {
        case 'Trimestral': return '3 months';
        case 'Semestral': return '6 months';
        case 'Anual': return '1 year';
        default: return '1 month'; // Mensal
    }
}

function getTipoDiasPorIndexador($indexador) {
    $tipos = [
        'CDI' => 'uteis',
        'CDI (CETIP) Diário' => 'uteis',
        'SELIC' => 'corridos',
        'IPCA' => 'mes_completo',
        'INPC' => 'mes_completo',
        'IGP-M' => 'mes_completo',
        'TR' => 'corridos'
    ];
    return $tipos[$indexador] ?? 'corridos';
}

function calcularDias($inicio, $fim, $tipo = 'corridos') {
    switch ($tipo) {
        case 'uteis':
            return calcularDiasUteis($inicio, $fim);
        case 'mes_completo':
            return calcularDiasMesCompleto($inicio, $fim);
        default:
            return $inicio->diff($fim)->days;
    }
}

function calcularDiasUteis($inicio, $fim) {
    $diasUteis = 0;
    $periodo = new DatePeriod(
        $inicio,
        new DateInterval('P1D'),
        $fim->modify('+1 day') // Inclui o dia final
    );
    
    foreach ($periodo as $data) {
        $diaSemana = $data->format('N');
        if ($diaSemana < 6) { // 1-5 = segunda a sexta
            $diasUteis++;
        }
    }
    return $diasUteis;
}

function calcularDiasMesCompleto($inicio, $fim) {
    $diasMes = cal_days_in_month(CAL_GREGORIAN, $inicio->format('m'), $inicio->format('Y'));
    $diasPeriodo = $inicio->diff($fim)->days + 1;
    return $diasPeriodo / $diasMes; // Retorna fração do mês
}

?>
