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
            margin-top: -135px;
            margin-bottom: 75px;
            text-align: center;
            color: #000;
        }

        .menu-row {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .menu-link {
            flex: 1 1 calc(20% - 20px);
            max-width: calc(20% - 20px);
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
            background-color: transparent;
            border: 2px solid rgba(255, 255, 255, 0.2);
            padding: 20px;
            text-align: center;
            border-radius: 10px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .menu-box strong {
            color: #000;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .menu-box .menu-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
            color: #000;
            transition: color 0.3s ease;
        }

        .menu-box:hover {
            background-color: transparent;
            border: 2px solid rgba(255, 255, 255, .2);
            transition: 0.5s;
        }

        .menu-box:hover strong {
            color: #fff;
        }

        .menu-box:hover .menu-icon {
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        <h1 class="text-center">Bem-Vindo, <?= $_SESSION['usuario_nome'] ?? 'Usuário' ?>!</h1>
        <div class="menu-row">
            <a href="pages/clientes/listar.php" class="menu-link">
                <div class="menu-box">
                    <div class="menu-icon"><i class='bx bx-group'></i></div>
                    <strong>Clientes</strong>
                </div>
            </a>
            <a href="pages/operacoes/criar.php" class="menu-link">
                <div class="menu-box">
                    <div class="menu-icon"><i class='bx bx-file'></i></div>
                    <strong>Nova Operação</strong>
                </div>
            </a>
            <a href="pages/operacoes/listar.php" class="menu-link">
                <div class="menu-box">
                    <div class="menu-icon"><i class='bx bx-folder'></i></div>
                    <strong>Operações</strong>
                </div>
            </a>
            <a href="pages/calculos/indices.php" class="menu-link">
                <div class="menu-box">
                    <div class="menu-icon"><i class='bx bx-search'></i></div>
                    <strong>Consultar Indices</strong>
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
