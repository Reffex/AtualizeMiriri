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
    $valor_inicial = $_POST['valor_inicial'];

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
        cliente_id, identificador, indexador, periodicidade, valor_inicial,
        atualizar_ate, atualizar_dia_debito, atualizar_correcao_monetaria, atualizar_juros_nominais,
        alterar_taxas_em, alterar_dia_debito, alterar_correcao_monetaria, alterar_juros_nominais,
        valor_multa, valor_honorarios, observacao
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt) {
        $stmt->bind_param(
            "isssdsidddddddds",
            $cliente_id,
            $identificador,
            $indexador,
            $periodicidade,
            $valor_inicial,
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
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: transparent;
            border: 2px solid rgba(255, 255, 255, .2);
            border-radius: 10px;
            color: #000000;
            box-shadow: 0 0 10px rgba(0, 0, 0, .2);
            backdrop-filter: blur(20px);
        }

        .form-box input[type="text"],
        .form-box input[type="number"],
        .form-box input[type="date"],
        .form-box select,
        .form-box textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 2px solid rgba(255, 255, 255, .2);
            border-radius: 40px;
            background-color: transparent;
            color: #000000;
        }

        .form-box textarea {
            border-radius: 10px;
            min-height: 100px;
        }

        .form-box button {
            margin: 10px 5px;
            padding: 10px 20px;
            background-color: #fff;
            border: none;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 0 10px rgba(0, 0, 0, .1);
        }

        .form-box button:hover {
            background-color: transparent;
            border: 2px solid rgba(255, 255, 255, .2);
            color: #fff;
            transition: 0.5s;
        }

        hr {
            margin: 20px 0;
            border: 1px solid rgba(255, 255, 255, .2);
        }

        label {
            display: block;
            margin-top: 10px;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <div class="form-box">
        <h1 style="text-align:center;">Nova Operação</h1>
        <?php if (!empty($mensagem)): ?>
            <p style="color:red; text-align:center;"><?= $mensagem ?></p>
        <?php endif; ?>
        <form method="POST">

            <!-- Cliente -->
            <select name="cliente_id" required>
                <option value="">Selecione um cliente</option>
                <?php while ($cliente = $clientes->fetch_assoc()): ?>
                    <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nome']) ?></option>
                <?php endwhile; ?>
            </select>

            <!-- Identificador -->
            <input type="text" name="identificador" placeholder="Identificador da operação" required>

            <!-- Indexador -->
            <select name="indexador" required>
                <option value="INPC">INPC</option>
                <option value="CDI">CDI</option>
                <option value="IPCA">IPCA</option>
            </select>

            <!-- Periodicidade -->
            <select name="periodicidade" required>
                <option value="Mensal">Mensal</option>
                <option value="Trimestral">Timestral</option>
                <option value="Semestral">Semestral</option>
                <option value="Anual">Anual</option>
            </select>

            <hr>

            <!-- Atualizar até -->
            <label>Atualizar até:</label>
            <input type="date" name="atualizar_ate" required>
            <label>Dia do débito:</label>
            <input type="number" name="atualizar_dia_debito" min="1" max="31" value="1" required>
            <label>Correção monetária (%):</label>
            <input type="number" step="0.001" name="atualizar_correcao_monetaria" required>
            <label>Juros nominais (%):</label>
            <input type="number" step="0.001" name="atualizar_juros_nominais" required>

            <hr>

            <!-- Alterar taxas em -->
            <label>Alterar taxas em:</label>
            <input type="date" name="alterar_taxas_em" required>
            <label>Dia do débito:</label>
            <input type="number" name="alterar_dia_debito" min="1" max="31" value="1" required>
            <label>Correção monetária (%):</label>
            <input type="number" step="0.001" name="alterar_correcao_monetaria" required>
            <label>Juros nominais (%):</label>
            <input type="number" step="0.001" name="alterar_juros_nominais" required>

            <hr>

            <!-- Multa e Honorários -->
            <input type="number" step="0.01" name="valor_multa" placeholder="Valor da multa">
            <input type="number" step="0.01" name="valor_honorarios" placeholder="Valor dos honorários">

            <!-- Observações -->
            <textarea name="observacao" placeholder="Observação" rows="4"></textarea>

            <div style="text-align:center;">
                <button type="submit">Criar operação</button>
                <button type="button" onclick="window.location.href='listar.php'">Cancelar</button>
            </div>
        </form>
    </div>
</body>

</html>
