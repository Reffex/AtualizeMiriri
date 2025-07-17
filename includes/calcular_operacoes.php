<?php
function calcular_operacao($mysqli, $operacao, $lancamentos)
{
    $valor_inicial = $operacao['valor_inicial'] ?? 0.0;
    $indexador = $operacao['indexador'];
    $data_inicio = new DateTime($operacao['data_criacao']);
    $data_fim = new DateTime($operacao['atualizar_ate']);
    $juros_nominais = ($operacao['atualizar_juros_nominais'] ?? 0.0) / 100;

    $dias_periodo = match ($operacao['periodicidade']) {
        'Mensal' => 30,
        'Trimestral' => 90,
        'Semestral' => 180,
        'Anual' => 365,
        default => 30
    };

    $periodos = new DatePeriod(
        new DateTime($data_inicio->format('Y-m-01')),
        new DateInterval("P1M"),
        $data_fim
    );

    $fator_total = 1.0;

    foreach ($periodos as $data) {
        $data_ref = $data->format('Y-m-01');

        $stmt = $mysqli->prepare("SELECT valor FROM indices WHERE nome = ? AND data_referencia = ?");
        $stmt->bind_param("ss", $indexador, $data_ref);
        $stmt->execute();
        $result = $stmt->get_result();
        $indice = 0.0;
        if ($row = $result->fetch_assoc()) {
            $indice = $row['valor'] / 100; 
        }
        $stmt->close();

        $fator_total *= (1 + $indice + $juros_nominais);
    }

    $saldo = $valor_inicial;
    $lancamentos->data_seek(0);
    while ($l = $lancamentos->fetch_assoc()) {
        $valor = $l['valor'];
        $saldo += ($l['tipo'] === 'credito') ? $valor : -$valor;
    }

    $valor_corrigido = $saldo * $fator_total;

    $multa = $operacao['valor_multa'] ?? 0;
    $honorarios = $operacao['valor_honorarios'] ?? 0;

    $saldo_final = $valor_corrigido + $multa + $honorarios;

    return [
        'fator_correcao' => $fator_total,
        'valor_corrigido' => $valor_corrigido,
        'juros_percentual' => $juros_nominais * 100,
        'multa' => $multa,
        'honorarios' => $honorarios,
        'saldo_atualizado' => $saldo_final,
        'movimentacao' => $saldo
    ];
}
?>
