<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="./assets/css/styles.css">
    <title>Atualize Miriri</title>
</head>
<body>
    <main class="container">
        <form>
            <h1>Atualize Miriri</h1>
            <div class="input-box">
                <input placeholder="Email" type="email">
                <i class="bx bxs-user"></i>
            </div>
              <div class="input-box">
                <input placeholder="Senha" type="password">
                <i class="bx bxs-lock-alt"></i>
            </div>

            <div class="remember-forgot">
                <label>
                    <input type="checkbox">
                    Lembrar Senha
                </label>
                <a href="forgot.php">Esqueci a Senha</a>
            </div>

            <button type="submit" class="login">Login</button>

            <div class="register-link">
                <p>Não tem uma conta? <a href="register.php">Cadastre-se</a></p>
            </div>
        </form>
    </main>

</body>
</html>
