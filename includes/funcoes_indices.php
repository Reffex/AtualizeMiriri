<?php
function obter_indice($mysqli, $indexador, $data)
{
    if ($data instanceof DateTime) {
        $data = $data->format('Y-m-d');
    }

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

function obter_indice_periodo($mysqli, $data_inicio, $data_fim, $indexador)
{
    if (!($data_inicio instanceof DateTime)) {
        $data_inicio = new DateTime($data_inicio);
    }
    if (!($data_fim instanceof DateTime)) {
        $data_fim = new DateTime($data_fim);
    }

    $data_inicio_str = $data_inicio->format('Y-m-d');
    $data_fim_str = $data_fim->format('Y-m-d');

    if (stripos($indexador, 'CDI') !== false) {
        // Para CDI
        $query = "SELECT valor FROM indices 
                  WHERE nome = ? AND data_referencia BETWEEN ? AND ? 
                  AND DAYOFWEEK(data_referencia) NOT IN (1,7)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("sss", $indexador, $data_inicio_str, $data_fim_str);
        $stmt->execute();
        $res = $stmt->get_result();

        $fator = 1.0;
        while ($row = $res->fetch_assoc()) {
            $cdi = (float)$row['valor'];
            $fator *= (1 + $cdi / 100);
        }
        return ($fator - 1) * 100;
    } elseif (stripos($indexador, 'IPCA') !== false) {
        // Para IPCA
        $mes_ref = (clone $data_fim)->modify('first day of previous month')->format('Y-m-d');

        $query = "SELECT valor FROM indices 
              WHERE nome = ? AND data_referencia = ? LIMIT 1";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ss", $indexador, $mes_ref);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $ipca_mensal = (float)($row['valor'] ?? 0.0);

        // Calcular dias do período e dias no mês
        $dias_no_mes = $data_fim->format('t'); // Total de dias no mês atual
        $dias_periodo = $data_inicio->diff($data_fim)->days + 1;

        // Aplicar correção proporcional se o período for menor que um mês
        if ($dias_periodo < $dias_no_mes) {
            $fator_correcao = pow(1 + ($ipca_mensal / 100), ($dias_periodo / $dias_no_mes));
            return ($fator_correcao - 1) * 100;
        } else {
            return $ipca_mensal; 
        }
    }
    return 0.0; 
}

function calcular_dias_uteis($inicio, $fim, $mysqli = null)
{
    if (!($inicio instanceof DateTime)) {
        $inicio = new DateTime($inicio);
    }
    if (!($fim instanceof DateTime)) {
        $fim = new DateTime($fim);
    }

    $dias_uteis = 0;
    $data = clone $inicio;

    while ($data <= $fim) {
        $dia_semana = $data->format('N'); 
        if ($dia_semana < 6) { 
            $dias_uteis++;
        }
        $data->modify('+1 day');
    }

    return $dias_uteis;
}

function calcular_dias_corridos($data_inicio, $data_fim, $indexador, $mysqli)
{
    if (!($data_inicio instanceof DateTime)) {
        $data_inicio = new DateTime($data_inicio);
    }
    if (!($data_fim instanceof DateTime)) {
        $data_fim = new DateTime($data_fim);
    }

    if (stripos($indexador, 'CDI') !== false) {
        // Contar somente os dias úteis que realmente têm CDI na base
        $query = "SELECT COUNT(*) as total 
                  FROM indices 
                  WHERE nome = ? 
                    AND data_referencia BETWEEN ? AND ?
                    AND DAYOFWEEK(data_referencia) NOT IN (1, 7)";
        $stmt = $mysqli->prepare($query);
        $inicio_str = $data_inicio->format('Y-m-d');
        $fim_str = $data_fim->format('Y-m-d');
        $stmt->bind_param("sss", $indexador, $inicio_str, $fim_str);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        return (int)($row['total'] ?? 0);
    }

    return $data_inicio->diff($data_fim)->days;
}

function calcular_dias_uteis_entre_datas($data_inicio, $data_fim)
{
    if (!($data_inicio instanceof DateTime)) {
        $data_inicio = new DateTime($data_inicio);
    }
    if (!($data_fim instanceof DateTime)) {
        $data_fim = new DateTime($data_fim);
    }

    $dias = 0; 
    $intervalo = DateInterval::createFromDateString('1 day');
    $periodo = new DatePeriod($data_inicio, $intervalo, $data_fim);

    foreach ($periodo as $data) {
        $dia_semana = (int)$data->format('N'); 
        if ($dia_semana < 6) { 
            $dias++;
        }
    }
    return $dias;
}
