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
        max-width: 800px; /* Aumentei a largura máxima */
        margin: 20px auto;
        padding: 30px;
        background-color: rgba(255, 255, 255, 0.1);
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 10px;
        backdrop-filter: blur(20px);
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        color: #000;
    }

    .form-group {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 20px;
        align-items: flex-end;
    }

    .form-group label {
        flex-basis: 100%;
        font-weight: 600;
    }

    .form-group input,
    .form-group select {
        flex: 1;
        min-width: 200px;
        padding: 10px;
        border: 2px solid rgba(255, 255, 255, .2);
        border-radius: 40px;
        background-color: rgba(255, 255, 255, 0.7); /* Fundo branco semitransparente */
        color: #000;
    }

    textarea {
        width: 100%;
        padding: 10px;
        margin-top: 10px;
        border-radius: 10px;
        border: 2px solid rgba(255, 255, 255, .2);
        background-color: rgba(255, 255, 255, 0.7); /* Fundo branco semitransparente */
        color: #000;
        resize: vertical;
        min-height: 80px;
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

    /* Estilo para tabelas (caso precise no criar.php) */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background-color: rgba(255, 255, 255, 0.7);
    }

    th, td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    th {
        background-color: rgba(0, 0, 0, 0.1);
        font-weight: 600;
    }

    tr:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }

    .login {
        padding: 10px 25px;
        margin: 10px 0;
    }

    @media (max-width: 768px) {
        .form-box {
            padding: 15px;
        }
        th, td {
            padding: 8px 10px;
            font-size: 14px;
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
    <div class="form-group">
        <select name="cliente_id" required>
            <option value="">Selecione um cliente</option>
            <?php while ($cliente = $clientes->fetch_assoc()): ?>
                <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nome']) ?></option>
            <?php endwhile; ?>
        </select>
        <input type="text" name="identificador" placeholder="Identificador da operação" required>
    </div>

    <!-- Indexador e Periodicidade -->
    <div class="form-group">
        <select name="indexador" required>
            <option value="INPC">INPC</option>
            <option value="CDI">CDI</option>
            <option value="IPCA">IPCA</option>
        </select>
        <select name="periodicidade" required>
            <option value="Mensal">Mensal</option>
            <option value="Trimestral">Trimestral</option>
            <option value="Semestral">Semestral</option>
            <option value="Anual">Anual</option>
        </select>
    </div>

    <hr>

    <!-- Atualizar até -->
    <div style="margin-bottom: 10px;"><label for="atualizar_ate">Atualizar até:</label></div>
    <div class="form-group">
        <input type="date" name="atualizar_ate" required>
        <input type="number" name="atualizar_dia_debito" placeholder="Dia do débito" min="1" max="31" required>
        <input type="number" step="0.001" name="atualizar_correcao_monetaria" placeholder="Correção monetária (%)" required>
        <input type="number" step="0.001" name="atualizar_juros_nominais" placeholder="Juros nominais (%)" required>
    </div>

    <!-- Alterar taxas em -->
    <div style="margin-bottom: 10px;"><label for="alterar_taxas_em">Alterar taxas em:</label></div>
    <div class="form-group">
        <input type="date" name="alterar_taxas_em" required>
        <input type="number" name="alterar_dia_debito" placeholder="Dia do débito" min="1" max="31" required>
        <input type="number" step="0.001" name="alterar_correcao_monetaria" placeholder="Correção monetária (%)" required>
        <input type="number" step="0.001" name="alterar_juros_nominais" placeholder="Juros nominais (%)" required>
    </div>

    <hr>

    <!-- Multa e Honorários -->
    <div class="form-group">
        <input type="number" step="0.01" name="valor_multa" placeholder="Valor da multa">
        <input type="number" step="0.01" name="valor_honorarios" placeholder="Valor dos honorários">
    </div>

    <!-- Observação -->
    <label for="observacao">Observação:</label>
    <textarea name="observacao" placeholder=""></textarea>

    <div style="text-align:center;">
        <button type="submit">Criar operação</button>
        <button type="button" onclick="window.location.href='listar.php'">Cancelar</button>
    </div>
</form>

    </div>
</body>
</html>
