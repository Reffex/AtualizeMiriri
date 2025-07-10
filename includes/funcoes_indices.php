<?php
function atualizar_indices($mysqli, $indice, $codigo_sgs)
{
    $url = "https://api.bcb.gov.br/dados/serie/bcdata.sgs.$codigo_sgs/dados/ultimos/12?formato=json";
    $json = @file_get_contents($url);
    if (!$json) return "Falha ao acessar API";

    $dados = json_decode($json, true);
    if (!$dados) return "JSON inválido";

    $stmt = $mysqli->prepare("
            INSERT INTO indices (nome, data_referencia, valor)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE valor = VALUES(valor)
        ");
    foreach ($dados as $d) {
        $data_formatada = date('Y-m-01', strtotime(str_replace('/', '-', $d['data'])));
        $valor = floatval(str_replace(',', '.', $d['valor']));

        $stmt->bind_param("ssd", $indice, $data_formatada, $valor);
        $stmt->execute();
    }
    return;
}
