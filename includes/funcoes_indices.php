<?php
function obter_indice($mysqli, $indexador, $data)
{
    $stmt = $mysqli->prepare("SELECT valor FROM indices WHERE nome = ? AND data_referencia <= ? ORDER BY data_referencia DESC LIMIT 1");
    $stmt->bind_param("ss", $indexador, $data);
    $stmt->execute();
    $result = $stmt->get_result();
    $valor = 0.0;
    if ($row = $result->fetch_assoc()) {
        $valor = $row['valor'];
    }
    $stmt->close();
    return $valor;
}

function atualizar_indices($mysqli)
{
    $urls = [
        'IPCA'  => 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.433/dados/ultimos/20?formato=json',
        'CDI'   => 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.12/dados/ultimos/20?formato=json',
        'SELIC' => 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.4390/dados/ultimos/20?formato=json'
    ];

    $mensagem = '';

    foreach ($urls as $nome => $url) {
        $json = @file_get_contents($url);

        if ($json) {
            $dados = json_decode($json, true);

            if (!is_array($dados)) {
                $mensagem .= "Erro ao interpretar dados de $nome<br>";
                continue;
            }

            $stmt = $mysqli->prepare("INSERT INTO indices (nome, data_referencia, valor) VALUES (?, ?, ?) 
                                     ON DUPLICATE KEY UPDATE valor = VALUES(valor)");

            foreach ($dados as $dado) {
                if (!isset($dado['data'], $dado['valor'])) continue;

                $data_formatada = date('Y-m-d', strtotime(str_replace('/', '-', $dado['data'])));
                $valor = floatval(str_replace(',', '.', $dado['valor']));

                $stmt->bind_param("ssd", $nome, $data_formatada, $valor);
                $stmt->execute();
            }

            $stmt->close();
            $mensagem .= "$nome atualizado com sucesso.<br>";
        } else {
            $mensagem .= "Falha ao obter dados de $nome<br>";
        }
    }

    return $mensagem;
}
