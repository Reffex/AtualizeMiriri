<?php
function calcular_operacao($mysqli, $operacao, $lancamentos)
{
    // Valores iniciais
    $valor_inicial = $operacao['valor_inicial'] ?? 0.0;
    $indexador = $operacao['indexador'];
    $data_inicio = new DateTime($operacao['data_criacao']);
    $data_fim = new DateTime($operacao['atualizar_ate']);

    // Taxas configuráveis
    $correcao_monetaria = ($operacao['atualizar_correcao_monetaria'] ?? 0.0) / 100;
    $juros_nominais = ($operacao['atualizar_juros_nominais'] ?? 0.0) / 100;
    $multa = $operacao['valor_multa'] ?? 0.0;
    $honorarios = $operacao['valor_honorarios'] ?? 0.0;

    // Inicializa arrays para o extrato detalhado
    $extrato = [];
    $saldo = $valor_inicial;
    $correcao_total = 0;
    $juros_total = 0;

    // Adiciona o saldo inicial ao extrato
    $extrato[] = [
        'data' => $data_inicio->format('d/m/Y'),
        'descricao' => 'Saldo Inicial',
        'debito' => '',
        'credito' => '',
        'saldo' => $saldo,
        'indice' => '',
        'dias_uteis' => ''
    ];

    // Processa lançamentos
    $lancamentos->data_seek(0);
    while ($l = $lancamentos->fetch_assoc()) {
        $data_lancamento = new DateTime($l['data']);
        $valor = $l['valor'];

        if ($l['tipo'] === 'debito') {
            $saldo -= $valor;
            $extrato[] = [
                'data' => $data_lancamento->format('d/m/Y'),
                'descricao' => $l['descricao'],
                'debito' => $valor,
                'credito' => '',
                'saldo' => $saldo,
                'indice' => '',
                'dias_uteis' => ''
            ];
        } else {
            $saldo += $valor;
            $extrato[] = [
                'data' => $data_lancamento->format('d/m/Y'),
                'descricao' => $l['descricao'],
                'debito' => '',
                'credito' => $valor,
                'saldo' => $saldo,
                'indice' => '',
                'dias_uteis' => ''
            ];
        }
    }

    // Cálculo da correção monetária e juros por períodos
    $periodos = new DatePeriod(
        $data_inicio,
        new DateInterval('P1M'), // Mensal (ajustar conforme periodicidade)
        $data_fim
    );

    foreach ($periodos as $periodo) {
        if ($periodo >= $data_fim) break;

        // Obtém índice do período
        $indice = obter_indice($mysqli, $indexador, $periodo->format('Y-m-d'));

        // Calcula dias úteis
        $dias_uteis = calcular_dias_uteis($periodo, $data_fim);

        // Aplica correção monetária e juros
        $correcao_periodo = $saldo * $indice;
        $juros_periodo = $saldo * $juros_nominais * ($dias_uteis / 30);

        $saldo += $correcao_periodo + $juros_periodo;
        $correcao_total += $correcao_periodo;
        $juros_total += $juros_periodo;

        $extrato[] = [
            'data' => $periodo->format('d/m/Y'),
            'descricao' => 'Correção Monetária + Juros',
            'debito' => '',
            'credito' => '',
            'saldo' => $saldo,
            'indice' => number_format($indice * 100, 2) . '%',
            'dias_uteis' => $dias_uteis
        ];
    }

    // Aplica multa e honorários
    $saldo_final = $saldo + $multa + $honorarios;

    return [
        'movimentacao' => $saldo - $valor_inicial - $correcao_total - $juros_total,
        'correcao' => $correcao_total,
        'juros' => $juros_total,
        'multa' => $multa,
        'honorarios' => $honorarios,
        'saldo_atualizado' => $saldo_final,
        'extrato_detalhado' => $extrato
    ];
}

function calcular_dias_uteis($inicio, $fim)
{
    $dias = 0;
    $interval = new DateInterval('P1D');
    $periodo = new DatePeriod($inicio, $interval, $fim);

    foreach ($periodo as $data) {
        $dia_semana = $data->format('N');
        if ($dia_semana < 6) { // 1-5 = segunda a sexta
            $dias++;
        }
    }

    return $dias;
}
