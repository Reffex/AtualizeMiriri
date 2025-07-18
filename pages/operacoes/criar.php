<?php
require_once '../../includes/auto_check.php';
require_once '../../includes/connect_app.php';

$mensagem = '';
$clientes = $mysqli->query("SELECT id, nome FROM clientes ORDER BY nome");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Casts para garantir os tipos corretos
    $cliente_id = (int) $_POST['cliente_id'];
    $identificador = $_POST['identificador'];
    $indexador = $_POST['indexador'];
    $periodicidade = $_POST['periodicidade'];

    $atualizar_ate = $_POST['atualizar_ate']; // data string, ok
    $atualizar_dia_debito = (int) $_POST['atualizar_dia_debito'];
    $atualizar_correcao_monetaria = (float) $_POST['atualizar_correcao_monetaria'];
    $atualizar_juros_nominais = (float) $_POST['atualizar_juros_nominais'];

    $alterar_taxas_em = $_POST['alterar_taxas_em']; // data string, ok
    $alterar_dia_debito = (int) $_POST['alterar_dia_debito'];
    $alterar_correcao_monetaria = (float) $_POST['alterar_correcao_monetaria'];
    $alterar_juros_nominais = (float) $_POST['alterar_juros_nominais'];

    // Pode receber vazio, então trata para float 0.0 caso vazio
    $valor_multa = $_POST['valor_multa'] !== '' ? (float) $_POST['valor_multa'] : 0.0;
    $valor_honorarios = $_POST['valor_honorarios'] !== '' ? (float) $_POST['valor_honorarios'] : 0.0;
    $observacao = $_POST['observacao'];

    $stmt = $mysqli->prepare("INSERT INTO operacoes (
        cliente_id, identificador, indexador, periodicidade,
        atualizar_ate, atualizar_dia_debito, atualizar_correcao_monetaria, atualizar_juros_nominais,
        alterar_taxas_em, alterar_dia_debito, alterar_correcao_monetaria, alterar_juros_nominais,
        valor_multa, valor_honorarios, observacao
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt) {
        $stmt->bind_param(
            "isssiiddsiddddds",
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
</head>

<body>
    <div class="form-box nova-operacao-box">
        <h1 class="nova-operacao-titulo">Nova Operação</h1>
        <?php if (!empty($mensagem)): ?>
            <p class="mensagem-erro"><?= $mensagem ?></p>
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
                        <option value="SELIC">SELIC</option>
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
                    <input type="number" step="0.001" name="atualizar_correcao_monetaria" required>
                </div>
                <div class="form-item">
                    <label for="atualizar_juros_nominais">Juros nominais(%):</label>
                    <input type="number" step="0.001" name="atualizar_juros_nominais" required>
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
                    <input type="number" step="0.001" name="alterar_correcao_monetaria" required>
                </div>
                <div class="form-item">
                    <label for="alterar_juros_nominais">Juros nominais(%):</label>
                    <input type="number" step="0.001" name="alterar_juros_nominais" required>
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
            </div>
            <div class="register-link">
                <p><a href="listar.php">Voltar para operações</a></p>
            </div>
        </form>
    </div>
</body>

</html>
