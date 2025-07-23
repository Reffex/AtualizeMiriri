<?php
require_once '../../includes/auto_check.php';
require_once '../../includes/connect_app.php';

$id = $_GET['id'] ?? null;
$mensagem = '';

if (!$id) {
    echo "ID do cliente não foi informado.";
    exit;
}

$stmt = $mysqli->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$cliente = $resultado->fetch_assoc();

if (!$cliente) {
    echo "Cliente não encontrado.";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST['nome']);
    $documento = trim($_POST['documento']);

    if (!empty($nome) && !empty($documento)) {
        $stmt = $mysqli->prepare("UPDATE clientes SET nome = ?, documento = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nome, $documento, $id);
        if ($stmt->execute()) {
            header("Location: listar.php?sucesso=2");
            exit;
        } else {
            $mensagem = "Erro ao atualizar: " . $stmt->error;
        }
    } else {
        $mensagem = "Preencha todos os campos!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>Atualize Miriri</title>
</head>

<body>
    <div class="container">
        <div class="form-box">
            <h1 style="text-align:center;">Editar Cliente</h1>

            <?php if (!empty($mensagem)): ?>
                <p style="text-align:center; color:red; font-weight:bold;">
                    <?= $mensagem ?>
                </p>
            <?php endif; ?>

            <form method="POST">
                <div class="input-box">
                    <input type="text" name="nome" placeholder="Nome Completo" value="<?= htmlspecialchars($cliente['nome']) ?>" required>
                    <i class='bx bxs-user'></i>
                </div>

                <div class="input-box">
                    <input type="text" name="documento" placeholder="CPF ou CNPJ" value="<?= htmlspecialchars($cliente['documento']) ?>" required>
                    <i class='bx bxs-id-card'></i>
                </div>

                <button type="submit" class="login">Salvar Alterações</button>

                <div class="register-link">
                    <p><a href="listar.php">Voltar para a lista</a></p>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
