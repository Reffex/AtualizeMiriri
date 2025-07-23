<?php
require_once 'funcoes_indices.php';

function calcular_operacao($mysqli, $operacao, $lancamentos)
{
    // Valores iniciais
    $valor_inicial = $operacao['valor_inicial'] ?? 0.0;
    $indexador = $operacao['indexador'];
    $data_inicio = new DateTime($operacao['data_criacao']);
    $data_fim = new DateTime($operacao['atualizar_ate']);
    $periodicidade = $operacao['periodicidade'];

    // Taxas configuráveis
    $percentual_correcao = ($operacao['atualizar_correcao_monetaria'] ?? 100.0) / 100;
    $juros_mensais = ($operacao['atualizar_juros_nominais'] ?? 0.0) / 100;
    $multa = $operacao['valor_multa'] ?? 0.0;
    $honorarios = $operacao['valor_honorarios'] ?? 0.0;

    // Inicializa arrays para o extrato detalhado
    $extrato = [];
    $saldo = $valor_inicial;
    $correcao_total = 0;
    $juros_total = 0;
    $movimentacao_total = 0;

    // Processa lançamentos
    $lancamentos->data_seek(0);
    while ($l = $lancamentos->fetch_assoc()) {
        $data_lancamento = new DateTime($l['data']);
        $valor = $l['valor'];

        if ($l['tipo'] === 'debito') {
            $saldo -= $valor;
            $movimentacao_total -= $valor;
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
            $movimentacao_total += $valor;
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

    // Define o intervalo de periodicidade
    $intervalo = match ($periodicidade) {
        'Diário' => 'P1D',
        'Mensal' => 'P1M',
        'Trimestral' => 'P3M',
        'Semestral' => 'P6M',
        'Anual' => 'P1Y',
        default => 'P1M'
    };

    // Cálculo da correção monetária e juros por períodos
    $periodos = new DatePeriod($data_inicio, new DateInterval($intervalo), $data_fim);

    foreach ($periodos as $periodo) {
        if ($periodo >= $data_fim) break;

        // Obtém índice do período
        $indice = obter_indice($mysqli, $indexador, $periodo->format('Y-m-d'));

        // Calcula dias úteis até o próximo período ou data final
        $proximo_periodo = clone $periodo;
        $proximo_periodo->add(new DateInterval($intervalo));
        if ($proximo_periodo > $data_fim) {
            $proximo_periodo = $data_fim;
        }

        $dias_uteis = calcular_dias_uteis($periodo, $proximo_periodo);
        $dias_corridos = $periodo->diff($proximo_periodo)->days + 1;

        // Aplica correção monetária
        $correcao_periodo = $saldo * $indice * $percentual_correcao;
        $correcao_total += $correcao_periodo;

        // Aplica juros (considerando juros ao mês e proporcional aos dias)
        $juros_periodo = $saldo * $juros_mensais * ($dias_corridos / 30);
        $juros_total += $juros_periodo;

        // Atualiza saldo
        $saldo_anterior = $saldo;
        $saldo += $correcao_periodo + $juros_periodo;

        $extrato[] = [
            'data' => $periodo->format('d/m/Y'),
            'descricao' => 'Correção Monetária + Juros',
            'debito' => '',
            'credito' => '',
            'saldo' => $saldo,
            'indice' => number_format($indice * 100, 4) . '%',
            'dias_uteis' => $dias_uteis
        ];
    }

    // Aplica multa e honorários (se houver saldo devedor)
    if ($saldo < 0) {
        $saldo_final = $saldo + $multa + $honorarios;
    } else {
        $saldo_final = $saldo;
        $multa = 0;
        $honorarios = 0;
    }

    return [
        'movimentacao' => $movimentacao_total,
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
