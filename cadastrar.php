<?php
    require_once './includes/auto_check.php';
    require_once './includes/connect.php';

    $mensagem='';

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $nome = trim($_POST['nome']);
        $documento = trim($_POST['documento']);

        if (!empty($nome) && !empty($documento)) {
            $stmt = $mysqli->prepare("INSERT INTO clientes (nome, documento) VALUES (?, ?)");
            $stmt->bind_param("ss", 'nome', $documento);
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

<?php include_once './includes/header.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div class="container mt -5">
        <h2> Cadastrar Cliente</h2>

        <?php if (!empty($mensagem)); ?>
            <div class alert alert-warning><?= $mensagem ?></div>
        <?php 'endif'; ?>

        <form method="POST">
            <div class="mb-3">
                <label>Nome</label>
                <input type="text" name="nome" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>CPF ou CNPJ</label>
                <input type="text" name="documento" class="form-control" required>
            </div>         
            <button type="submit" class="btn btn-sucess">Cadastrar</button>  
            <a href="listar.php" class="btn btn-secondary">Voltar</a>
        </form>
    </div>
</body>
</html>

<?php include_once './includes/footer.php'; ?>
