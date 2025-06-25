<?php
    require_once '../../includes/auto_check.php';
    require_once '../../includes/connect_app.php';

    $mensagem = '';
    if (isset($_GET['sucesso'])) {
        $mensagem = "Operação cadastrada com sucesso!";
    }

    $sql = "
        SELECT o.*, c.nome AS cliente_nome
        FROM operacoes o
        JOIN clientes c ON o.cliente_id = c.id
        ORDER BY o.data_criacao DESC
    ";

    $operacoes = $mysqli->query($sql);
?>

<?php include_once '../../includes/header.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <div class="container">
    <div class="form-box">
        <h1 style="text-align:center;">Operações</h1>

        <?php if (!empty($mensagem)): ?>
            <p style="text-align:center; color:green; font-weight:bold;"><?= $mensagem ?></p>
        <?php endif; ?>

        <div style="text-align:center; margin-bottom: 20px;">
            <a href="criar.php">
                <button class="login">Nova Operação</button>
            </a>
        </div>

        <table style="width:100%; text-align:center; border-collapse: collapse;">
            <thead>
                <tr style="font-weight:bold; border-bottom: 2px solid #000;">
                    <th style="padding: 10px;">Cliente</th>
                    <th style="padding: 10px;">Identificador</th>
                    <th style="padding: 10px;">Indexador</th>
                    <th style="padding: 10px;">Periodicidade</th>
                    <th style="padding: 10px;">Valor Inicial</th>
                    <th style="padding: 10px;">Data</th>
                    <th style="padding: 10px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($operacoes->num_rows > 0): ?>
                    <?php while ($op = $operacoes->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #ccc;">
                            <td style="padding: 10px;"><?= htmlspecialchars($op['cliente_nome']) ?></td>
                            <td style="padding: 10px;"><?= htmlspecialchars($op['identificador']) ?></td>
                            <td style="padding: 10px;"><?= htmlspecialchars($op['indexador']) ?></td>
                            <td style="padding: 10px;"><?= htmlspecialchars($op['periodicidade']) ?></td>
                            <td style="padding: 10px;">R$ <?= number_format($op['valor_inicial'], 2, ',', '.') ?></td>
                            <td style="padding: 10px;"><?= date('d/m/Y H:i', strtotime($op['data_criacao'])) ?></td>
                            <td style="padding: 10px;">
                                <a href="detalhes.php?id=<?= $op['id'] ?>" class="btn btn-sm btn-info">Ver</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="padding: 20px;">Nenhuma operação cadastrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    </div>
</body>
</html>

<?php include_once '../../includes/footer.php'; ?>
