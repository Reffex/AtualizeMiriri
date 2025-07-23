<?php
require_once '../../includes/auto_check.php';
require_once '../../includes/connect_app.php';

$clientes = $mysqli->query("SELECT * FROM clientes ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <script>
        setTimeout(function() {
            const msg = document.getElementById('alerta-msg');
            if (msg) {
                msg.style.transition = 'opacity 1s';
                msg.style.opacity = 0;
                setTimeout(() => msg.remove(), 1000);
            }
        }, 3000);
    </script>
</head>

<body>
    <div class="form-box-wide">
        <div class="form-box-wide excluir-page">
            <h1 style="text-align:center;">Listar Clientes</h1>

            <?php if (isset($_GET['sucesso'])): ?>
                <div id="alerta-msg" style="text-align:center; color:green; font-weight:bold;">
                    <?php if ($_GET['sucesso'] == 1): ?>
                        Cliente cadastrado com sucesso!
                    <?php elseif ($_GET['sucesso'] == 3): ?>
                        Cliente excluído com sucesso!
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div style="text-align:center; margin-bottom: 20px;">
                <a href="cadastrar.php">
                    <button class="login" style="width: 350px;">Novo Cliente</button>
                </a>
            </div>

            <table class="tabela">
                <thead>
                    <tr style="font-weight:bold; border-bottom: 2px solid #000;">
                        <th style="padding: 10px;">ID</th>
                        <th style="padding: 10px;">NOME</th>
                        <th style="padding: 10px;">DOCUMENTO</th>
                        <th style="padding: 10px;">DATA</th>
                        <th style="padding: 10px;">AÇÕES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($clientes->num_rows > 0): ?>
                        <?php while ($cliente = $clientes->fetch_assoc()): ?>
                            <tr style="border-bottom: 1px solid #ccc;">
                                <td style="padding: 10px;"><?= $cliente['id'] ?></td>
                                <td style="padding: 10px;"><?= htmlspecialchars($cliente['nome']) ?></td>
                                <td style="padding: 10px;"><?= htmlspecialchars($cliente['documento']) ?></td>
                                <td style="padding: 10px;"><?= date('d/m/Y H:i', strtotime($cliente['data_cadastro'])) ?></td>
                                <td style="padding: 10px;">
                                    <a href="editar.php?id=<?= $cliente['id'] ?>" title="Editar" style="text-decoration: none;">
                                        <i class='bx bx-edit' style="font-size: 20px; color: #333; margin-right: 10px; transition: 0.3s;" onmouseover="this.style.color='#AEF0FF'" onmouseout="this.style.color='#333'"></i>
                                    </a>
                                    <a href="excluir.php?id=<?= $cliente['id'] ?>" onclick="return confirm('Deseja excluir?')" title="Excluir" style="text-decoration: none;">
                                        <i class='bx bx-trash' style="font-size: 20px; color: #333; transition: 0.3s;" onmouseover="this.style.color='red'" onmouseout="this.style.color='#333'"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="padding: 20px;">Nenhum cliente cadastrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="register-link">
                <p><a href="../../index.php">Voltar para o menu</a></p>
            </div>
        </div>
    </div>
</body>

</html>
