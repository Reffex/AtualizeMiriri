<?php
    require_once '../../includes/auto_check.php';
    require_once '../../includes/connect_app.php';

    $id = $_GET['id'] ?? null;
    if (!$id) {
        echo "ID da operação inválido.";
    exit;
    }

    $op = $mysqli->query("
        SELECT o.*, c.nome AS cliente_nome
        FROM operacoes o
        JOIN clientes c ON o.cliente_id = c.id
        WHERE o.id = $id
    ")->fetch_assoc();

    $lancamentos = $mysqli->query("
        SELECT * FROM lancamentos
        WHERE operacao_id = $id
        ORDER BY data ASC
    ");

    $saldo = $op['valor_inicial'];
    $lancamentos->data_seek(0);
    while ($l = $lancamentos->fetch_assoc()) {
        if ($l['tipo'] === 'credito') {
            $saldo += $l['valor'];
        } else {
            $saldo -= $l['valor'];
        }
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $data = $_POST['data'];
        $descricao = $_POST['descricao'];
        $valor = $_POST['valor'];
        $tipo = $_POST['tipo'];

        $stmt = $mysqli->prepare("INSERT INTO lancamentos (operacao_id, data, descricao, valor, tipo) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issds", $id, $data, $descricao, $valor, $tipo);
        if ($stmt->execute()) {
            header("Location: detalhes.php?id=$id");
            exit();
        }
    }
?>

<?php include_once '../../includes/header.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <style>
    .extrato-table {
        width: 100%;
        border-collapse: collapse;
        text-align: center;
    }

    .extrato-table th,
    .extrato-table td {
        padding: 10px;
        border-bottom: 1px solid #ccc;
    }

    .extrato-table thead tr {
        font-weight: bold;
        border-bottom: 2px solid #000;
    }

    .input-box select {
        width: 100%;
        height: 100%;
        background-color: transparent;
        border: 2px solid rgba(255, 255, 255, .2);
        border-radius: 40px;
        outline: none;
        font-size: 16px;
        color: #333;
        padding: 10px 45px 10px 20px;
        appearance: none;
    }
</style>
</head>
<body>
    <div class="container">
    <div class="form-box-wide">
        <h1 style="text-align:center;">Detalhes</h1>

        <div style="text-align:center; margin-bottom: 20px;">
            <p><strong>Cliente:</strong> <?= htmlspecialchars($op['cliente_nome']) ?></p>
            <p><strong>Identificador:</strong> <?= htmlspecialchars($op['identificador']) ?></p> 
            <p><strong>Indexador:</strong> <?= $op['indexador'] ?></p>
            <p><strong>Periodicidade:</strong> <?= $op['periodicidade'] ?></p>
            <p><strong>Valor Inicial:</strong> R$ <?= number_format($op['valor_inicial'], 2, ',', '.') ?></p>
            <p><strong>Saldo Atual:</strong> 
                <span style="color: <?= $saldo >= 0 ? 'green' : 'red' ?>;">
                    R$ <?= number_format($saldo, 2, ',', '.') ?>
                </span>
            </p>
        </div>

        <hr>
        <h3 style="text-align:center;">Extrato de Lançamentos</h3>

        <table class="extrato-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $lancamentos->data_seek(0);
                while ($l = $lancamentos->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($l['data'])) ?></td>
                        <td><?= htmlspecialchars($l['descricao']) ?></td>
                        <td><?= ucfirst($l['tipo']) ?></td>
                        <td>R$ <?= number_format($l['valor'], 2, ',', '.') ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <hr>
        <h3 style="text-align:center;">Adicionar Lançamento</h3>

        <form method="POST">
            <div class="input-box">
                <input type="date" name="data" required>
                <i class='bx bxs-calendar'></i>
            </div>

            <div class="input-box">
                <input type="text" name="descricao" placeholder="Descrição" required>
                <i class='bx bxs-pencil'></i>
            </div>

            <div class="input-box">
                <select name="tipo" required>
                    <option value="credito">Crédito</option>
                    <option value="debito">Débito</option>
                </select>
                <i class='bx bxs-wallet'></i>
            </div>

            <div class="input-box">
                <input type="number" step="0.01" name="valor" placeholder="Valor" required>
                <i class='bx bxs-dollar-circle'></i>
            </div>

            <button type="submit" class="login">Adicionar Lançamento</button>

            <div class="register-link">
                <p><a href="listar.php">Voltar para Operações</a></p>
            </div>
        </form>
    </div>
    </div>
</body>
</html>

<?php include_once '../../includes/footer.php'; ?>
