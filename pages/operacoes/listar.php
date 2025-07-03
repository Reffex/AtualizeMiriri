<?php
    require_once '../../includes/auto_check.php';
    require_once '../../includes/connect_app.php';

    $mensagem = '';
        if (isset($_GET['sucesso'])) {
    $mensagem = "Operação cadastrada com sucesso!";
}

    $sql = "SELECT o.*, c.nome AS cliente_nome
        FROM operacoes o
        JOIN clientes c ON o.cliente_id = c.id
        ORDER BY o.data_criacao DESC";

    $operacoes = $mysqli->query($sql);

    if ($operacoes === false) {
        die("Erro na consulta SQL: " . $mysqli->error);
    }
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
            max-width: 1200px; 
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
        <h1 style="text-align:center;">Operações</h1>

        <?php if (!empty($mensagem)): ?>
            <p style="text-align:center; color:green; font-weight:bold;"><?= $mensagem ?></p>
        <?php endif; ?>

        <div style="text-align:center;">
    <a href="criar.php">
        <button class="login" style="width: 300px;">Nova Operação</button>
    </a>
</div>

        <?php if ($operacoes->num_rows > 0): ?>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Identificador</th>
                            <th>Indexador</th>
                            <th>Periodicidade</th>
                            <th>Valor Inicial</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($op = $operacoes->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($op['cliente_nome']) ?></td>
                                <td><?= htmlspecialchars($op['identificador']) ?></td>
                                <td><?= htmlspecialchars($op['indexador']) ?></td>
                                <td><?= htmlspecialchars($op['periodicidade']) ?></td>
                                <td>R$ <?= number_format($op['valor_inicial'], 2, ',', '.') ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($op['data_criacao'])) ?></td>
                                <td>
                                    <a href="detalhes.php?id=<?= $op['id'] ?>" style="color: #00A8FF;">Ver</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="text-align:center; padding: 20px;">Nenhuma operação cadastrada.</p>
        <?php endif; ?>
        <div class="register-link">
                <p><a href="../../index.php">Voltar para o menu</a></p>
        </div>
    </div>
</body>
</html>
