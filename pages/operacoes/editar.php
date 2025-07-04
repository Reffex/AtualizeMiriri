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
    <title>Editar Operação</title>
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
        <h1 style="text-align:center; margin-bottom: 25px;">Editar Operação</h1>
        <?php if (!empty($mensagem)): ?>
            <p style="color:red; text-align:center;"><?= $mensagem ?></p>
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
                <button type="button" onclick="window.location.href='listar.php'">Cancelar</button>
            </div>
        </form>
    </div>
</body>
</html>
