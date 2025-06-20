<?php
    require_once 'includes/auto_check.php';
    require_once 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="./assets/css/styles.css">
    <style>
         body {
        padding: 40px;
        background-color: #f4f4f4;
    }

    .main-wrapper {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    h1 {
        margin-bottom: 40px;
        text-align: center;
    }

    .menu-row {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 20px;
    }

    .menu-link {
        flex: 1 1 calc(25% - 20px);
        max-width: calc(25% - 20px);
        text-decoration: none;
        color: #000000;
        display: flex;
    }

    .menu-box {
        flex: 1;
        width: 100%;
        min-height: 130px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        background-color: #ffffffcc;
        border: 2px solid;
        padding: 20px;
        text-align: center;
        border-radius: 10px;
        transition: all 0.3s ease;
        backdrop-filter: blur(20px);
    }

    .menu-box:hover {
        background-color: transparent;
        border: 2px solid rgba(255, 255, 255, .2);
        color: #fff;
    }

    .menu-icon {
        font-size: 2.5em;
        margin-bottom: 10px;
    }
   
    </style>
</head>
<body>
<div class="main-wrapper">
    <h1 class="text-center">Bem-Vindo, <?= $_SESSION['usuario_nome'] ?? 'Usuário' ?>!</h1>
    <div class="menu-row">
        <a href="clientes.php" class="menu-link">
            <div class="menu-box">
                <div class="menu-icon">👤</div>
                <strong>Clientes</strong>
            </div>
        </a>
        <a href="operacoes.php" class="menu-link">
            <div class="menu-box">
                <div class="menu-icon">💼</div>
                <strong>Operações</strong>
            </div>
        </a>
        <a href="nova_operacao.php" class="menu-link">
            <div class="menu-box">
                <div class="menu-icon">➕</div>
                <strong>Nova Operação</strong>
            </div>
        </a>
        <a href="logout.php" class="menu-link">
            <div class="menu-box">
                <div class="menu-icon">🚪</div>
                <strong>Sair</strong>
            </div>
        </a>
    </div>
</div>
</body>
</html>

<?php
    require_once 'includes/footer.php';
?>
