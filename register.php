<?php
$mensagem = '';

if (isset($_POST['submit'])) {
    include_once(__DIR__ . '/includes/connect_app.php');

    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    $result = mysqli_query($mysqli, "INSERT INTO usuarios(nome, email, senha) VALUES ('$nome', '$email', '$senha')");

    if ($result) {
        $mensagem = "Registrado com sucesso!";
    } else {
        if (strpos(mysqli_error($mysqli),' Duplicate entry') !== false) {
          $mensagem="Este e-mail já esta em uso!";
        } else {
          $mensagem="Erro ao se registrar.";
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
    <title>Cadastro - Atualize Miriri</title>
</head>
<body>
    <main class="container">
    <?php if (!empty($mensagem)): ?>
      <p style="text-align:center; color: <?= strpos($mensagem, 'Erro') !== false ? 'red' : 'green' ?>;">
        <?= $mensagem ?>
      </p>
    <?php endif; ?>

    <form action="register.php" method="POST">
      <h1>Cadastre-se</h1>

      <div class="input-box">
        <input type="text" name="nome" placeholder="Nome Completo" required>
        <i class='bx bxs-user'></i>
      </div>

      <div class="input-box">
        <input type="email" name="email" placeholder="Email" required>
        <i class='bx bxs-envelope'></i>
      </div>

      <div class="input-box">
        <input type="password" name="senha" placeholder="Senha" required>
        <i class='bx bxs-lock-alt'></i>
      </div>

      <button type="submit" name="submit" class="login">Cadastrar</button>

      <div class="register-link">
        <p>Já tem uma conta? <a href="login.php">Voltar ao Login</a></p>
      </div>
    </form>
  </main>
</body>
</html>
