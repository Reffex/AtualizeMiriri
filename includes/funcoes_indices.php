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
        'IPCA'  => 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.433/dados?formato=json',
        'CDI (CETIP) Diário'   => 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.12/dados?formato=json&dataInicial=' . date('d/m/Y', strtotime('-10 years')),
        'SELIC' => 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.4390/dados?formato=json'
    ];

    $mensagem = '';

    if ($mysqli->connect_errno) {
        return "Erro na conexão: " . $mysqli->connect_error;
    }

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

            if ($stmt === false) {
                $mensagem .= "Erro ao preparar consulta para $nome: " . $mysqli->error . "<br>";
                continue;
            }

            foreach ($dados as $dado) {
                if (!isset($dado['data'], $dado['valor'])) continue;

                $data_formatada = date('Y-m-d', strtotime(str_replace('/', '-', $dado['data'])));
                $valor = floatval(str_replace(',', '.', $dado['valor']));

                if (!$stmt->bind_param("ssd", $nome, $data_formatada, $valor)) {
                    $mensagem .= "Erro ao vincular parâmetros para $nome: " . $stmt->error . "<br>";
                    continue;
                }

                if (!$stmt->execute()) {
                    $mensagem .= "Erro ao inserir dados de $nome: " . $stmt->error . "<br>";
                }
            }

            $stmt->close();
            $mensagem .= "$nome processado.<br>";
        } else {
            $mensagem .= "Falha ao obter dados de $nome<br>";
        }
    }

    return $mensagem;
}
