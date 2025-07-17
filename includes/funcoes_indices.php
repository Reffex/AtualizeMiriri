<?php
function obter_indice($mysqli, $indexador, $data)
{
    $stmt = $mysqli->prepare("SELECT valor FROM indices WHERE nome = ? AND data_referencia <= ? ORDER BY data_referencia DESC LIMIT 1");
    $stmt->bind_param("ss", $indexador, $data);
    $stmt->execute();
    $result = $stmt->get_result();
    $valor = 0.0;
    if ($row = $result->fetch_assoc()) {
        $valor = $row['valor'] / 100;
    }
    $stmt->close();
    return $valor;
}

function atualizar_indices($mysqli)
{
    $indices = [
        'IPCA' => ['codigo' => 10844, 'nome' => 'IPCA'],
        'CDI' => ['codigo' => 4390, 'nome' => 'CDI'],
        'SELIC' => ['codigo' => 1178, 'nome' => 'SELIC']
    ];

    foreach ($indices as $indice) {
        $url = "https://api.bcb.gov.br/dados/serie/bcdata.sgs.{$indice['codigo']}/dados/ultimos/12?formato=json";
        $json = @file_get_contents($url);

        if ($json) {
            $dados = json_decode($json, true);

            $stmt = $mysqli->prepare("INSERT INTO indices (nome, data_referencia, valor) VALUES (?, ?, ?) 
                                     ON DUPLICATE KEY UPDATE valor = VALUES(valor)");

            foreach ($dados as $dado) {
                $data_formatada = date('Y-m-01', strtotime(str_replace('/', '-', $dado['data'])));
                $valor = floatval(str_replace(',', '.', $dado['valor']));

                $stmt->bind_param("ssd", $indice['nome'], $data_formatada, $valor);
                $stmt->execute();
            }
        }
    }
}
