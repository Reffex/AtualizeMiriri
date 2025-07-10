<?php
function calcular_operacao($mysqli, $operacao, $lancamentos)
{
    $valor_inicial = $operacao['valor_inicial'] ?? 0;
    $indexador = $operacao['indexador'];
    $data_inicio = new DateTime($operacao['data_criacao']);
    $data_fim = new DateTime($operacao['atualizar_ate']);

    $periodicidade = $operacao['periodicidade'];
    $dias_periodo = match ($periodicidade) {
        'Mensal' => 30,
        'Trimestral' => 90,
        'Semestral' => 180,
        'Anual' => 365,
        default => 30
    };

    $periodos = new DatePeriod($data_inicio, new DateInterval("P{$dias_periodo}D"), $data_fim);

    $correcao = 0;
    foreach ($periodos as $data) {
        $data_ref = $data->format('Y-m-01');
        $stmt = $mysqli->prepare("SELECT valor FROM indices WHERE nome = ? AND data_referencia = ?");
        $stmt->bind_param("ss", $indexador, $data_ref);
        $stmt = $mysqli->prepare("SELECT valor FROM indices WHERE nome = ? AND data_referencia = ?");
        $stmt->bind_param("ss", $indexador, $data_ref);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $correcao += $row['valor'];
        }
        $stmt->close();
    }

    $juros_total = 0;
    $saldo_base = $valor_inicial;
    $lancs = [];

    $lancamentos->data_seek(0);
    while ($l = $lancamentos->fetch_assoc()) {
        $lancs[] = $l;
        if ($l['tipo'] === 'credito') {
            $saldo_base += $l['valor'];
        } else {
            $saldo_base -= $l['valor'];
        }
    }

    foreach ($periodos as $data) {
        $juros_nominais = $operacao['atualizar_juros_nominais'] ?? 0;
        $juros_total += ($saldo_base * ($juros_nominais / 100));
    }


    $saldo = $valor_inicial;
    $lancamentos->data_seek(0);
    while ($l = $lancamentos->fetch_assoc()) {
        $saldo += ($l['tipo'] === 'credito') ? $l['valor'] : -$l['valor'];
    }

    $multa = $operacao['valor_multa'] ?? 0;
    $honorarios = $operacao['valor_honorarios'] ?? 0;

    $saldo_final = $saldo * (1 + ($correcao + $juros_total) / 100) + $multa + $honorarios;

    return [
        'correcao' => $correcao,
        'juros' => $juros_total,
        'multa' => $multa,
        'honorarios' => $honorarios,
        'saldo_atualizado' => $saldo_final,
        'movimentacao' => $saldo
    ];
}
