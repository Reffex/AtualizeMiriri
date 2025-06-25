<?php 
    require_once '../../includes/auto_check.php';
    require_once '../../includes/connect_app.php';

    $clientes = $mysqli->query("SELECT * FROM clientes ORDER BY id DESC");
?>

<?php require_once '../../includes/header.php'; ?>

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
        <h1 style="text-align:center; margin-bottom: 30px;">Listar Clientes</h1>

        <?php if (isset($_GET['sucesso'])): ?>
            <p style="text-align:center; color:green; font-weight:bold;">Cliente cadastrado com sucesso!</p>
        <?php endif; ?>

        <div style="text-align:center; margin-bottom: 20px;">
            <a href="cadastrar.php">
                <button class="login">Novo Cliente</button>
            </a>
        </div>

        <table style="width:100%; text-align:center; border-collapse: collapse;">
            <thead>
                <tr style="font-weight:bold; border-bottom: 2px solid #000;">
                    <th style="padding: 10px;">ID</th>
                    <th style="padding: 10px;">Nome</th>
                    <th style="padding: 10px;">Documento</th>
                    <th style="padding: 10px;">Data</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($clientes->num_rows > 0): ?>
                    <?php while ($cliente = $clientes->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #ccc;">
                            <td style="padding: 10px;"><?= $cliente['id'] ?></td>
                            <td style="padding: 10px;"><?= htmlspecialchars($cliente['nome']) ?></td>
                            <td style="padding: 10px;"><?= htmlspecialchars($cliente['documento']) ?></td>
                            <td style="padding: 10px;"><?= date('d/m/Y H:i', strtotime($cliente['data_cadastro'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="padding: 20px;">Nenhum cliente cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    </div>
</body>
</html>

<?php require_once '../../includes/footer.php' ?>
