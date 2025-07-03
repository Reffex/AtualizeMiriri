<?php
    require_once '../../includes/auto_check.php';
    require_once '../../includes/connect_app.php';

    $mensagem='';

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $nome = trim($_POST['nome']);
        $documento = trim($_POST['documento']);

        if (!empty($nome) && !empty($documento)) {
            $stmt = $mysqli->prepare("INSERT INTO clientes (nome, documento) VALUES (?, ?)");
            $stmt->bind_param("ss", $nome, $documento);
            if ($stmt->execute()) {
                header("Location: listar.php?sucesso=1");
                exit();
            } else {
                if (strpos($mysqli->error, 'Duplicate entry') !== false) {
                    $mensagem="Documento já cadastrado!";
                } else {
                    $mensagem="Erro: " . $mysqli->error;
                }
            }
        } else {
            $mensagem="Preencha todos os campos!";
        }
    }
?>

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
        <h1 style="text-align:center;">Cadastrar Cliente</h1>

        <?php if (!empty($mensagem)): ?>
            <p style="text-align:center; color:<?= strpos($mensagem, 'sucesso') !== false ? 'green' : 'red' ?>; font-weight:bold;">
                <?= $mensagem ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <div class="input-box">
                <input type="text" name="nome" placeholder="Nome Completo" required>
                <i class='bx bxs-user'></i>
            </div>

            <div class="input-box">
                <input type="text" name="documento" placeholder="CPF, CNPJ ou Código" required>
                <i class='bx bxs-id-card'></i>
            </div>

            <button type="submit" class="login">Cadastrar</button>

            <div class="register-link">
                <p><a href="listar.php">Voltar para a lista</a></p>
            </div>
        </form>
    </div>
    </div>
</body>
</html>
