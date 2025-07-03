<?php
    require_once 'includes/auto_check.php';
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
            color: #000; /* Garante que o título fique preto */
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
            display: flex;
        }

        .menu-box {
            flex: 1;
            width: 100%;
            min-height: 130px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background-color:transparent;
            border: 2px solid rgba(255, 255, 255, .2); /* Borda transparente */
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            transition: all 0.3s ease;
            backdrop-filter: blur(20px);
        }

        .menu-box strong {
            color: #000; /* Texto em preto */
            font-weight: 600;
        }

        .menu-box:hover {
            background-color: rgba(255, 255, 255, 0.7);
            border: 2px solid rgba(0, 0, 0, 0.1); /* Borda sutil no hover */
        }

        .menu-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
            color: #000; /* Ícones pretos */
        }

        .menu-box:hover .menu-icon {
            color: #000; /* Mantém ícones pretos no hover */
        }

        .menu-box:hover strong {
            color: #000; /* Mantém texto preto no hover */
        }
        
    </style>
</head>
<body>
<div class="main-wrapper">
    <h1 class="text-center">Bem-Vindo, <?= $_SESSION['usuario_nome'] ?? 'Usuário' ?>!</h1>
    <div class="menu-row">
        <a href="pages/clientes/listar.php" class="menu-link">
            <div class="menu-box">
                <div class="menu-icon"><i class='bx bx-group'></i></i></div>
                <strong>Clientes</strong>
            </div>
        </a>
        <a href="pages/operacoes/listar.php" class="menu-link">
            <div class="menu-box">
                <div class="menu-icon"><i class='bx bx-folder'></i></i></div>
                <strong>Operações</strong>
            </div>
        </a>
        <a href="pages/operacoes/criar.php" class="menu-link">
            <div class="menu-box">
                <div class="menu-icon"><i class='bx bx-file'></i></i></div>
                <strong>Nova Operação</strong>
            </div>
        </a>
        <a href="logout.php" class="menu-link">
            <div class="menu-box">
                <div class="menu-icon"><i class='bx bx-log-out'></i></div>
                <strong>Sair</strong>
            </div>
        </a>
    </div>
</div>
</body>
</html>
