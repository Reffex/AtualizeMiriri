<?php
require_once 'includes/auto_check.php';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Início</title>
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css">
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
      <a href="pages/operacoes/listar.php" class="menu-link">
        <div class="menu-box">
          <div class="menu-icon"><i class='bx bx-folder'></i></div>
          <strong>Operações</strong>
        </div>
      </a>
      <a href="pages/calculos/indices.php" class="menu-link">
        <div class="menu-box">
          <div class="menu-icon"><i class='bx bx-search'></i></div>
          <strong>Consultar Índices</strong>
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
