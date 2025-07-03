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
    <style>
        .form-box-wide {
            width: 100%;
            max-width: 700px; 
            margin: 20px auto;
            padding: 30px;
            background-color: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            backdrop-filter: blur(20px);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: rgba(255, 255, 255, 0.7);
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: rgba(0, 0, 0, 0.1);
            font-weight: 600;
        }

        tr:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .login {
            padding: 10px 25px;
            margin: 10px 0;
        }

        @media (max-width: 768px) {
            .form-box-wide {
                padding: 15px;
            }
            th, td {
                padding: 8px 10px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="form-box-wide">
    <div class="form-box"> 
        <h1 style="text-align:center;">Listar Clientes</h1>

        <?php if (isset($_GET['sucesso'])): ?>
            <p style="text-align:center; color:green; font-weight:bold;">Cliente cadastrado com sucesso!</p>
        <?php endif; ?>

        <div style="text-align:center; margin-bottom: 20px;">
    <a href="cadastrar.php">
        <button class="login" style="width: 350px;">Novo Cliente</button>
    </a>
        </div>

        <table style="width:100%; text-align:center; border-collapse: collapse;">
            <thead>
                <tr style="font-weight:bold; border-bottom: 2px solid #000;">
                    <th style="padding: 10px;">ID</th>
                    <th style="padding: 10px;">NOME</th>
                    <th style="padding: 10px;">DOCUMENTO</th>
                    <th style="padding: 10px;">DATA</th>
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
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="padding: 20px;">Nenhum cliente cadastrado.</td></tr>
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
