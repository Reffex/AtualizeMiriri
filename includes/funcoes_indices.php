<?php
function obter_indice($mysqli, $indexador, $data) {
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

function atualizar_indices($mysqli) {
    // Configuração dos endpoints do BCB (SGS - Sistema Gerenciador de Séries Temporais)
    $urls = [
        'IPCA'  => 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.433/dados/ultimos/1?formato=json', // IPCA acumulado 12 meses
        'CDI'   => 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.12/dados/ultimos/1?formato=json',  // Taxa DI (CDI)
        'SELIC' => 'https://api.bcb.gov.br/dados/serie/bcdata.sgs.432/dados/ultimos/1?formato=json'  // SELIC Meta
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
                if (!isset($dado['data'], $dado['valor'])) {
                    continue;
                }

                // Formata a data para YYYY-MM-01 (padrão mensal, exceto CDI/SELIC que são diários)
                $data_formatada = ($nome == 'IPCA') 
                    ? date('Y-m-01', strtotime($dado['data'])) 
                    : $dado['data'];

                $valor = floatval($dado['valor']);

                $stmt->bind_param("ssd", $nome, $data_formatada, $valor);
                $stmt->execute();
            }

            $mensagem .= "$nome atualizado com sucesso.<br>";
        } else {
            $mensagem .= "Falha ao obter dados de $nome<br>";
        }
    }

    return $mensagem;
}
