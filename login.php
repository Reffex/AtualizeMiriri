<?php
session_start();
include_once 'includes/connect_app.php';

$erro = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $erro = "Preencha todos os campos!";
    } else {
        $stmt = $mysqli->prepare("SELECT id, nome, senha FROM usuarios WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $resultado = $stmt->get_result();
            if ($resultado->num_rows === 1) {
                $usuario = $resultado->fetch_assoc();
                if (password_verify($senha, $usuario['senha'])) {
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nome'] = $usuario['nome'];
                    header("Location: index.php");
                    exit();
                } else {
                    $erro = "Senha incorreta!";
                }
            } else {
                $erro = "E-mail não encontrado!";
            }
        } else {
            $erro = "Erro na consulta: " . $mysqli->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="./assets/css/styles.css">
    <title>Atualize Miriri - Login</title>
</head>

<body>
    <main class="container">
        <form action="login.php" method="POST">
            <h1>Atualize Miriri</h1>

            <?php if (!empty($erro)): ?>
                <p style="color: red; text-align: center;"><?= $erro ?></p>
            <?php endif; ?>

            <div class="input-box">
                <input type="email" name="email" placeholder="Email" required>
                <i class="bx bxs-user"></i>
            </div>

            <div class="input-box">
                <input type="password" name="senha" placeholder="Senha" required>
                <i class="bx bxs-lock-alt"></i>
            </div>

            <div class="remember-forgot">
                <label><input type="checkbox">Lembrar Senha</label>
                <a href="forgot.php">Esqueci a senha</a>
            </div>

            <button type="submit" class="login">Login</button>

            <div class="register-link">
                <p>Não tem uma conta? <a href="register.php">Cadastre-se</a></p>
            </div>
        </form>
    </main>
</body>

</html>
