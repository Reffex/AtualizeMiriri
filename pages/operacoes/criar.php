<?php
    require_once '../../includes/auto_check.php';
    require_once '../../includes/connect_app.php';

    $mensagem = '';
    $clientes = $mysqli->query("SELECT id, nome FROM clientes ORDER BY nome");

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $cliente_id = $_POST['cliente_id'];
        $identificador = $_POST['identificador'];
        $indexador = $_POST['indexador'];
        $periodicidade = $_POST['periodicidade'];

        $atualizar_ate = $_POST['atualizar_ate'];
        $atualizar_dia_debito = $_POST['atualizar_dia_debito'];
        $atualizar_correcao_monetaria = $_POST['atualizar_correcao_monetaria'];
        $atualizar_juros_nominais = $_POST['atualizar_juros_nominais'];

        $alterar_taxas_em = $_POST['alterar_taxas_em'];
        $alterar_dia_debito = $_POST['alterar_dia_debito'];
        $alterar_correcao_monetaria = $_POST['alterar_correcao_monetaria'];
        $alterar_juros_nominais = $_POST['alterar_juros_nominais'];

        $valor_multa = $_POST['valor_multa'];
        $valor_honorarios = $_POST['valor_honorarios'];
        $observacao = $_POST['observacao'];

        $stmt = $mysqli->prepare("INSERT INTO operacoes (
            cliente_id, identificador, indexador, periodicidade,
            atualizar_ate, atualizar_dia_debito, atualizar_correcao_monetaria, atualizar_juros_nominais,
            alterar_taxas_em, alterar_dia_debito, alterar_correcao_monetaria, alterar_juros_nominais,
            valor_multa, valor_honorarios, observacao
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt) {
        $stmt->bind_param(
            "isssdsidddddddd",
            $cliente_id,
            $identificador,
            $indexador,
            $periodicidade,
            $atualizar_ate,
            $atualizar_dia_debito,
            $atualizar_correcao_monetaria,
            $atualizar_juros_nominais,
            $alterar_taxas_em,
            $alterar_dia_debito,
            $alterar_correcao_monetaria,
            $alterar_juros_nominais,
            $valor_multa,
            $valor_honorarios,
            $observacao
        );

        if ($stmt->execute()) {
            header("Location: listar.php?sucesso=1");
            exit();
        } else {
            $mensagem = "Erro ao cadastrar operação: " . $stmt->error;
        }
    } else {
        $mensagem = "Erro na query: " . $mysqli->error;
    }
    }
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <style>
        .form-box {
            width: 100%;
            max-width: 1000px;
            margin: 20px auto;
            padding: 30px;
            background-color: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            backdrop-filter: blur(20px);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            color: #000;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-item {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 200px;
        }

        .form-item label {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .form-item input,
        .form-item select {
            width: 100%;
            padding: 10px;
            border-radius: 40px;
            background-color: rgba(255, 255, 255, 0.7);
            color: #000;
            border: 2px solid rgba(255, 255, 255, .2);
        }

        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border-radius: 10px;
            border: 2px solid rgba(255, 255, 255, .2);
            background-color: rgba(255, 255, 255, 0.7);
            color: #000;
            resize: vertical;
            min-height: 80px;
        }

        .button-group {
            text-align: center;
            margin-top: 20px;
        }

        button {
            margin: 10px 5px;
            padding: 10px 20px;
            background-color: #fff;
            border: none;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 0 10px rgba(0, 0, 0, .1);
        }

        button:hover {
            background-color: transparent;
            border: 2px solid rgba(255, 255, 255, .2);
            color: #fff;
            transition: 0.5s;
        }

        hr {
            border: 1px solid rgba(255, 255, 255, .2);
            margin: 30px 0;
        }

        @media (max-width: 768px) {
            .form-box {
                padding: 15px;
            }
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
            .form-item {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="form-box">
        <h1 style="text-align:center; margin-bottom: 25px;">Nova Operação</h1>
        <?php if (!empty($mensagem)): ?>
            <p style="color:red; text-align:center;"><?= $mensagem ?></p>
        <?php endif; ?>
        <form method="POST">

            <!-- Cliente e identificador -->
            <div class="form-row">
                <div class="form-item">
                    <label for="cliente_id">Cliente:</label>
                    <select name="cliente_id" required>
                        <option value="">Selecione um cliente</option>
                        <?php while ($cliente = $clientes->fetch_assoc()): ?>
                            <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nome']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-item">
                    <label for="identificador">Identificador da operação:</label>
                    <input type="text" name="identificador" required>
                </div>
            </div>

            <!-- Indexador e Periodicidade -->
            <div class="form-row">
                <div class="form-item">
                    <label for="indexador">Indexador:</label>
                    <select name="indexador" required>
                        <option value="INPC">INPC</option>
                        <option value="CDI">CDI</option>
                        <option value="IPCA">IPCA</option>
                    </select>
                </div>
                <div class="form-item">
                    <label for="periodicidade">Periodicidade:</label>
                    <select name="periodicidade" required>
                        <option value="Mensal">Mensal</option>
                        <option value="Trimestral">Trimestral</option>
                        <option value="Semestral">Semestral</option>
                        <option value="Anual">Anual</option>
                    </select>
                </div>
            </div>

            <!-- Atualizar até -->
            <div class="form-row">
                <div class="form-item">
                    <label for="atualizar_ate">Atualizar até:</label>
                    <input type="date" name="atualizar_ate" required>
                </div>
                <div class="form-item">
                    <label for="atualizar_dia_debito">Dia do débito:</label>
                    <input type="number" name="atualizar_dia_debito" min="1" max="31" value="1">
                </div>
                <div class="form-item">
                    <label for="atualizar_correcao_monetaria">Correção monetária(%):</label>
                    <input type="number" step="0.001" name="atualizar_correcao_monetaria" value="100.000" required>
                </div>
                <div class="form-item">
                    <label for="atualizar_juros_nominais">Juros nominais(%):</label>
                    <input type="number" step="0.001" name="atualizar_juros_nominais" value="12.000" required>
                </div>
            </div>

            <!-- Alterar taxas em -->
            <div class="form-row">
                <div class="form-item">
                    <label for="alterar_taxas_em">Alterar taxas em:</label>
                    <input type="date" name="alterar_taxas_em" required>
                </div>
                <div class="form-item">
                    <label for="alterar_dia_debito">Dia do Debito:</label>
                    <input type="number" name="alterar_dia_debito" value="1" min="1" max="31" required>
                </div>
                <div class="form-item">
                    <label for="alterar_correcao_monetaria">Correção monetária(%):</label>
                    <input type="number" step="0.001" name="alterar_correcao_monetaria" value="100.000" required>
                </div>
                <div class="form-item">
                    <label for="alterar_juros_nominais">Juros nominais(%):</label>
                    <input type="number" step="0.001" name="alterar_juros_nominais" value="12.000" required>
                </div>
            </div>

            <!-- Multa e Honorários -->
            <div class="form-row">
                <div class="form-item">
                    <label for="valor_multa">Valor da multa:</label>
                    <input type="number" step="0.01" name="valor_multa">
                </div>
                <div class="form-item">
                    <label for="valor_honorarios">Valor dos honorários:</label>
                    <input type="number" step="0.01" name="valor_honorarios">
                </div>
            </div>

            <!-- Observação -->
            <div class="form-item">
                <label for="observacao">Observação:</label>
                <textarea name="observacao" placeholder=""></textarea>
            </div>

            <div class="button-group">
                <button type="submit">Criar operação</button>
                <button type="button" onclick="window.location.href='listar.php'">Cancelar</button>
            </div>
        </form>
    </div>
</body>
</html>
