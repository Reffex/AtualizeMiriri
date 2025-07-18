<?php
require_once '../../includes/auto_check.php';
require_once '../../includes/connect_app.php';

$id = $_GET['id'] ?? null;
$mensagem = '';

if (!$id) {
    echo "ID da operação não foi informado.";
    exit;
}

$stmt = $mysqli->prepare("
        SELECT o.*, c.nome AS cliente_nome
        FROM operacoes o
        JOIN clientes c ON c.id = o.cliente_id
        WHERE o.id = ?
    ");

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$op = $result->fetch_assoc();

if (!$op) {
    echo "Operação não encontrada.";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $stmt = $mysqli->prepare("UPDATE operacoes SET 
            identificador = ?, indexador = ?, periodicidade = ?, 
            atualizar_ate = ?, atualizar_dia_debito = ?, atualizar_correcao_monetaria = ?, atualizar_juros_nominais = ?, 
            alterar_taxas_em = ?, alterar_dia_debito = ?, alterar_correcao_monetaria = ?, alterar_juros_nominais = ?, 
            valor_multa = ?, valor_honorarios = ?, observacao = ?
            WHERE id = ?
    ");

    $stmt->bind_param(
        "ssssiddsidddssi",
        $_POST['identificador'],
        $_POST['indexador'],
        $_POST['periodicidade'],
        $_POST['atualizar_ate'],
        $_POST['atualizar_dia_debito'],
        $_POST['atualizar_correcao_monetaria'],
        $_POST['atualizar_juros_nominais'],
        $_POST['alterar_taxas_em'],
        $_POST['alterar_dia_debito'],
        $_POST['alterar_correcao_monetaria'],
        $_POST['alterar_juros_nominais'],
        $_POST['valor_multa'],
        $_POST['valor_honorarios'],
        $_POST['observacao'],
        $id
    );

    if ($stmt->execute()) {
        header("Location: listar.php?sucesso=2");
        exit;
    } else {
        $mensagem = "Erro ao atualizar operação: " . $stmt->error;
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
        <h1 class="editar-operacao-titulo">Editar Operação</h1>
        <?php if (!empty($mensagem)): ?>
            <p class="mensagem-erro"><?= $mensagem ?></p>
        <?php endif; ?>
        <form method="POST">

            <!-- Cliente e identificador -->
            <div class="form-row">
                <div class="form-item">
                    <label for="cliente_nome">Cliente:</label>
                    <input type="text" value="<?= htmlspecialchars($op['cliente_nome']) ?>" disabled>
                </div>
                <div class="form-item">
                    <label for="identificador">Identificador da operação:</label>
                    <input type="text" name="identificador" value="<?= htmlspecialchars($op['identificador']) ?>" required>
                </div>
            </div>

            <!-- Indexador e Periodicidade -->
            <div class="form-row">
                <div class="form-item">
                    <label for="indexador">Indexador:</label>
                    <select name="indexador" required>
                        <option value="INPC" <?= $op['indexador'] === 'INPC' ? 'selected' : '' ?>>INPC</option>
                        <option value="CDI" <?= $op['indexador'] === 'CDI' ? 'selected' : '' ?>>CDI</option>
                        <option value="IPCA" <?= $op['indexador'] === 'IPCA' ? 'selected' : '' ?>>IPCA</option>
                    </select>
                </div>
                <div class="form-item">
                    <label for="periodicidade">Periodicidade:</label>
                    <select name="periodicidade" required>
                        <option value="Mensal" <?= $op['periodicidade'] === 'Mensal' ? 'selected' : '' ?>>Mensal</option>
                        <option value="Trimestral" <?= $op['periodicidade'] === 'Trimestral' ? 'selected' : '' ?>>Trimestral</option>
                        <option value="Semestral" <?= $op['periodicidade'] === 'Semestral' ? 'selected' : '' ?>>Semestral</option>
                        <option value="Anual" <?= $op['periodicidade'] === 'Anual' ? 'selected' : '' ?>>Anual</option>
                    </select>
                </div>
            </div>

            <!-- Atualizar até -->
            <div class="form-row">
                <div class="form-item">
                    <label for="atualizar_ate">Atualizar até:</label>
                    <input type="date" name="atualizar_ate" value="<?= $op['atualizar_ate'] ?>" required>
                </div>
                <div class="form-item">
                    <label for="atualizar_dia_debito">Dia do débito:</label>
                    <input type="number" name="atualizar_dia_debito" min="1" max="31" value="<?= $op['atualizar_dia_debito'] ?>">
                </div>
                <div class="form-item">
                    <label for="atualizar_correcao_monetaria">Correção monetária(%):</label>
                    <input type="number" step="0.001" name="atualizar_correcao_monetaria" value="<?= $op['atualizar_correcao_monetaria'] ?>" required>
                </div>
                <div class="form-item">
                    <label for="atualizar_juros_nominais">Juros nominais(%):</label>
                    <input type="number" step="0.001" name="atualizar_juros_nominais" value="<?= $op['atualizar_juros_nominais'] ?>" required>
                </div>
            </div>

            <!-- Alterar taxas em -->
            <div class="form-row">
                <div class="form-item">
                    <label for="alterar_taxas_em">Alterar taxas em:</label>
                    <input type="date" name="alterar_taxas_em" value="<?= $op['alterar_taxas_em'] ?>" required>
                </div>
                <div class="form-item">
                    <label for="alterar_dia_debito">Dia do Debito:</label>
                    <input type="number" name="alterar_dia_debito" value="<?= $op['alterar_dia_debito'] ?>" min="1" max="31" required>
                </div>
                <div class="form-item">
                    <label for="alterar_correcao_monetaria">Correção monetária(%):</label>
                    <input type="number" step="0.001" name="alterar_correcao_monetaria" value="<?= $op['alterar_correcao_monetaria'] ?>" required>
                </div>
                <div class="form-item">
                    <label for="alterar_juros_nominais">Juros nominais(%):</label>
                    <input type="number" step="0.001" name="alterar_juros_nominais" value="<?= $op['alterar_juros_nominais'] ?>" required>
                </div>
            </div>

            <!-- Multa e Honorários -->
            <div class="form-row">
                <div class="form-item">
                    <label for="valor_multa">Valor da multa:</label>
                    <input type="number" step="0.01" name="valor_multa" value="<?= $op['valor_multa'] ?>">
                </div>
                <div class="form-item">
                    <label for="valor_honorarios">Valor dos honorários:</label>
                    <input type="number" step="0.01" name="valor_honorarios" value="<?= $op['valor_honorarios'] ?>">
                </div>
            </div>

            <!-- Observação -->
            <div class="form-item">
                <label for="observacao">Observação:</label>
                <textarea name="observacao" placeholder=""><?= htmlspecialchars($op['observacao']) ?></textarea>
            </div>

            <div class="button-group">
                <button type="submit">Salvar alterações</button>
            </div>
            <div class="register-link">
                <p><a href="../../pages/operacoes/listar.php">Voltar para operações</a></p>
            </div>
        </form>
    </div>
</body>

</html>
