<?php
require_once 'funcoes_indices.php';

function calcular_operacao($mysqli, $operacao, $lancamentos_result) {
    $extrato = [];
    $totais = ['movimentacao'=>0, 'correcao'=>0, 'juros'=>0, 'multa'=>0, 'honorarios'=>0, 'saldo_atualizado'=>0];

    $indexador = $operacao['indexador'];
    $periodicidade = $operacao['periodicidade'];
    $correcao_pct = ($operacao['atualizar_correcao_monetaria'] ?? 0) / 100;
    $juros_pct_mensal = ($operacao['atualizar_juros_nominais'] ?? 0) / 100;
    $dia_debito = (int)($operacao['atualizar_dia_debito'] ?? 1);
    $data_fim = new DateTime($operacao['atualizar_ate']);

    $lancamentos = [];
    while($row = $lancamentos_result->fetch_assoc()) $lancamentos[] = $row;
    usort($lancamentos, fn($a,$b) => strtotime($a['data']) - strtotime($b['data']));

    // 1. data inicial e saldo inicial (primeiro débito)
    $data_inicio = null;
    $saldo = 0.0;
    foreach ($lancamentos as $l) {
        if (strtolower($l['tipo'])==='debito') {
            $data_inicio = new DateTime($l['data']);
            $saldo = (float)$l['valor'];
            $extrato[] = ['data'=>$data_inicio->format('d/m/Y'), 'descricao'=>$l['descricao'], 'debito'=>$saldo, 'credito'=>0, 'saldo'=>$saldo, 'indice'=>'', 'dias_corridos'=>0];
            break;
        }
    }
    if (!$data_inicio) return array_merge($totais, ['extrato_detalhado'=>[]]);

    // 2. aplicar lançamentos intermediários no saldo
    foreach ($lancamentos as $l) {
        $data_lanc = new DateTime($l['data']);
        if ($data_lanc <= $data_inicio) continue;
        $valor = (float)$l['valor'];
        if (strtolower($l['tipo'])==='debito') $saldo += $valor;
        else $saldo -= $valor;
        $extrato[] = ['data'=>$data_lanc->format('d/m/Y'), 'descricao'=>$l['descricao'], 'debito'=> strtolower($l['tipo'])==='debito' ? $valor : 0, 'credito'=> strtolower($l['tipo'])==='credito' ? $valor : 0, 'saldo'=>$saldo, 'indice'=>'', 'dias_corridos'=>0];
    }

    // 3. totais movimentação
    foreach ($extrato as $item) $totais['movimentacao'] += $item['credito'] - $item['debito'];

    // 4. gerar datas correção conforme periodicidade e dia de débito
    $datas_correcao = [];
    $data_corr = DateTime::createFromFormat('Y-m-d', $data_inicio->format('Y-m-') . str_pad($dia_debito, 2, '0', STR_PAD_LEFT));
    if ($data_corr < $data_inicio) {
        switch ($periodicidade) {
            case 'Mensal': $data_corr->modify('+1 month'); break;
            case 'Trimestral': $data_corr->modify('+3 months'); break;
            case 'Semestral': $data_corr->modify('+6 months'); break;
            case 'Anual': $data_corr->modify('+1 year'); break;
            default: $data_corr->modify('+1 month');
        }
    }
    while ($data_corr < $data_fim) {
        $datas_correcao[] = clone $data_corr;
        switch ($periodicidade) {
            case 'Mensal': $data_corr->modify('+1 month'); break;
            case 'Trimestral': $data_corr->modify('+3 months'); break;
            case 'Semestral': $data_corr->modify('+6 months'); break;
            case 'Anual': $data_corr->modify('+1 year'); break;
            default: $data_corr->modify('+1 month');
        }
    }
    $datas_correcao[] = clone $data_fim;

    // 5. função auxiliar dias corridos inclusivos
    $dias_corridos = fn($inicio,$fim) => $inicio->diff($fim)->days + 1;

    // 6. aplicar correção e juros compostos
    $data_ant = clone $data_inicio;
    foreach ($datas_correcao as $dt_corr) {
        $dias = $dias_corridos($data_ant, $dt_corr);

        // obter índice prorata proporcional
        $indice = obter_indice_dias_corridos($mysqli, $indexador, $data_ant, $dt_corr);

        if ($correcao_pct > 0) {
            $correcao = $saldo * ($indice / 100) * $correcao_pct;
            $saldo += $correcao;
            $totais['correcao'] += $correcao;
            $extrato[] = ['data'=>$dt_corr->format('d/m/Y'), 'descricao'=>($dt_corr == $data_fim ? 'Correção Final' : 'Correção Monetária'), 'debito'=>$correcao, 'credito'=>0, 'saldo'=>$saldo, 'indice'=>number_format($indice,4).'%', 'dias_corridos'=>$dias];
        }

        if ($juros_pct_mensal > 0 && $dt_corr > $data_ant) {
            $dias_base = 30.44;
            $taxa_dia = pow(1 + $juros_pct_mensal, 1 / $dias_base) - 1;
            $juros = $saldo * (pow(1 + $taxa_dia, $dias) - 1);
            $saldo += $juros;
            $totais['juros'] += $juros;
            $extrato[] = ['data'=>$dt_corr->format('d/m/Y'), 'descricao'=>'Juros Nominais Compostos', 'debito'=>$juros, 'credito'=>0, 'saldo'=>$saldo, 'indice'=>number_format($taxa_dia*100,6).'% a.d.', 'dias_corridos'=>$dias];
        }

        $data_ant = clone $dt_corr;
    }

    // 7. multa e honorários no final
    if (!empty($operacao['valor_multa']) && $operacao['valor_multa'] > 0) {
        $saldo += $operacao['valor_multa'];
        $totais['multa'] = $operacao['valor_multa'];
        $extrato[] = ['data'=>$data_fim->format('d/m/Y'), 'descricao'=>'Multa', 'debito'=>$operacao['valor_multa'], 'credito'=>0, 'saldo'=>$saldo, 'indice'=>'Valor Fixo', 'dias_corridos'=>0];
    }
    if (!empty($operacao['valor_honorarios']) && $operacao['valor_honorarios'] > 0) {
        $saldo += $operacao['valor_honorarios'];
        $totais['honorarios'] = $operacao['valor_honorarios'];
        $extrato[] = ['data'=>$data_fim->format('d/m/Y'), 'descricao'=>'Honorários', 'debito'=>$operacao['valor_honorarios'], 'credito'=>0, 'saldo'=>$saldo, 'indice'=>'Valor Fixo', 'dias_corridos'=>0];
    }

    $totais['saldo_atualizado'] = $saldo;

    return array_merge($totais, ['extrato_detalhado'=>$extrato]);
}

// Função para obter índice proporcional ao período (pró-rata)
function obter_indice_dias_corridos($mysqli, $indexador, $inicio, $fim) {
    $indice_total = 0.0;
    $data_atual = clone $inicio;
    $data_fim = clone $fim;

    while ($data_atual < $data_fim) {
        $ano = $data_atual->format('Y');
        $mes = $data_atual->format('m');

        $primeiro_dia_mes = new DateTime("$ano-$mes-01");
        $ultimo_dia_mes = (clone $primeiro_dia_mes)->modify('last day of this month');

        $inicio_periodo = clone $data_atual;
        $fim_periodo = $data_fim < $ultimo_dia_mes ? $data_fim : clone $ultimo_dia_mes;

        $dias_periodo = $inicio_periodo->diff($fim_periodo)->days + 1;
        $dias_mes = $primeiro_dia_mes->diff($ultimo_dia_mes)->days + 1;

        $stmt = $mysqli->prepare("SELECT valor FROM indices WHERE nome = ? AND data_referencia = ?");
        $data_ref = $primeiro_dia_mes->format('Y-m-01');
        $stmt->bind_param("ss", $indexador, $data_ref);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $indice_mes = $row['valor'] ?? 0.0;

        $indice_total += $indice_mes * ($dias_periodo / $dias_mes);

        $data_atual = (clone $ultimo_dia_mes)->modify('+1 day');
    }
    return $indice_total;
}

