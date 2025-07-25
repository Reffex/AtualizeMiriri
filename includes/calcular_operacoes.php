<?php
require_once 'funcoes_indices.php';

function calcular_operacao($mysqli, $operacao, $lancamentos_result)
{
    $extrato = [];

    $valor_inicial = $operacao['valor_inicial'] ?? 0.0;
    $indexador = $operacao['indexador'] ?? 'IPCA';
    $data_inicio = new DateTime($operacao['data_criacao'] ?? 'now');
    $data_vencimento = isset($operacao['data_vencimento']) ? new DateTime($operacao['data_vencimento']) : clone $data_inicio;
    $data_hoje = new DateTime();

    $juros_nominais = isset($operacao['juros_nominais']) ? (float)$operacao['juros_nominais'] : 0.0;
    $multa_percentual = isset($operacao['valor_multa']) ? (float)$operacao['valor_multa'] : 0.0;
    $honorarios_percentual = isset($operacao['valor_honorarios']) ? (float)$operacao['valor_honorarios'] : 0.0;
    $correcao_composta = isset($operacao['correcao_composta']) ? (bool)$operacao['correcao_composta'] : false;

    $lancamentos = [];
    if ($lancamentos_result instanceof mysqli_result) {
        while ($row = $lancamentos_result->fetch_assoc()) {
            $lancamentos[] = $row;
        }
    }

    usort($lancamentos, function ($a, $b) {
        return strtotime($a['data']) <=> strtotime($b['data']);
    });

    $saldo = $valor_inicial;
    $data_corrente = clone $data_inicio;
    $multa_aplicada = false;

    foreach ($lancamentos as $lanc) {
        $data_lanc = new DateTime($lanc['data']);
        while ($data_corrente < $data_lanc) {
            aplicar_correcao_e_juros($mysqli, $extrato, $indexador, $saldo, $data_corrente, $juros_nominais, $correcao_composta);
            aplicar_multa_e_honorarios($extrato, $multa_aplicada, $saldo, $data_corrente, $data_vencimento, $multa_percentual, $honorarios_percentual);
            $data_corrente->modify('+1 day');
        }

        $valor = (float) $lanc['valor'];
        $tipo = strtolower($lanc['tipo']);
        if ($tipo === 'débito' || $tipo === 'debito') {
            $saldo -= $valor;
        } else {
            $saldo += $valor;
        }

        $extrato[] = [
            'data' => $data_lanc->format('d/m/Y'),
            'descricao' => $lanc['descricao'],
            'debito' => $tipo === 'débito' || $tipo === 'debito' ? $valor : 0.0,
            'credito' => $tipo === 'crédito' || $tipo === 'credito' ? $valor : 0.0,
            'saldo' => $saldo,
            'indice' => '',
            'dias_uteis' => 0,
            'tipo' => 'lancamento',
            'valor' => 0.0
        ];

        $data_corrente = (clone $data_lanc)->modify('+1 day');
    }

    while ($data_corrente <= $data_hoje) {
        aplicar_correcao_e_juros($mysqli, $extrato, $indexador, $saldo, $data_corrente, $juros_nominais, $correcao_composta);
        aplicar_multa_e_honorarios($extrato, $multa_aplicada, $saldo, $data_corrente, $data_vencimento, $multa_percentual, $honorarios_percentual);
        $data_corrente->modify('+1 day');
    }

    return ['extrato_detalhado' => $extrato];
}

function aplicar_correcao_e_juros($mysqli, &$extrato, $indexador, &$saldo, $data_obj, $juros_nominais, $composta)
{
    if (!eh_dia_util($data_obj)) return;

    $data_str = $data_obj->format('Y-m-d');
    $indice = obter_indice($mysqli, $indexador, $data_str);
    $correcao_valor = $saldo * ($indice / 100);
    $saldo += $correcao_valor;

    $extrato[] = [
        'data' => $data_obj->format('d/m/Y'),
        'descricao' => 'Correção Monetária',
        'debito' => 0.0,
        'credito' => 0.0,
        'saldo' => $saldo,
        'indice' => number_format($indice, 2) . '%',
        'dias_uteis' => 1,
        'tipo' => 'correcao',
        'valor' => $correcao_valor
    ];

    if ($juros_nominais > 0) {
        $juros = $saldo * ($juros_nominais / 100) / 30;
        $saldo += $juros;

        $extrato[] = [
            'data' => $data_obj->format('d/m/Y'),
            'descricao' => 'Juros',
            'debito' => 0.0,
            'credito' => 0.0,
            'saldo' => $saldo,
            'indice' => number_format($juros_nominais, 2) . '%',
            'dias_uteis' => 1,
            'tipo' => 'juros',
            'valor' => $juros
        ];
    }
}

function aplicar_multa_e_honorarios(&$extrato, &$multa_aplicada, &$saldo, $data_corrente, $data_vencimento, $multa_pct, $honorarios_pct)
{
    if ($multa_aplicada || $data_corrente <= $data_vencimento || $saldo >= 0) return;

    $multa = abs($saldo) * ($multa_pct / 100);
    $honorarios = abs($saldo) * ($honorarios_pct / 100);
    $saldo += ($multa + $honorarios);

    $extrato[] = [
        'data' => $data_corrente->format('d/m/Y'),
        'descricao' => 'Multa',
        'debito' => 0.0,
        'credito' => 0.0,
        'saldo' => $saldo,
        'indice' => number_format($multa_pct, 2) . '%',
        'dias_uteis' => 0,
        'tipo' => 'multa',
        'valor' => $multa
    ];

    $extrato[] = [
        'data' => $data_corrente->format('d/m/Y'),
        'descricao' => 'Honorários',
        'debito' => 0.0,
        'credito' => 0.0,
        'saldo' => $saldo,
        'indice' => number_format($honorarios_pct, 2) . '%',
        'dias_uteis' => 0,
        'tipo' => 'honorarios',
        'valor' => $honorarios
    ];

    $multa_aplicada = true;
}

function eh_dia_util($data)
{
    $dia_semana = (int)$data->format('N');
    return $dia_semana <= 5;
}
