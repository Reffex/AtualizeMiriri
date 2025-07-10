<?php
require_once '../../includes/auto_check.php';
require_once '../../includes/connect_app.php';
require_once '../../includes/funcoes_indices.php';

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar'])) {
    $indices = [
        'IPCA' => 10844,
        'CDI'  => 4390,
        'SELIC' => 1178
    ];

    foreach ($indices as $nome => $codigo) {
        $mensagem .= atualizar_indices($mysqli, $nome, $codigo) . "<br>";
    }
}

$resultado = $mysqli->query("SELECT * FROM indices ORDER BY data_referencia DESC");
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Consulta de Índices</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .form-box-wide {
            width: 100%;
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: rgba(255, 255, 255, 0.7);
        }

        th,
        td {
            padding: 12px 15px;
            text-align: center;
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
            margin: 20px 0;
            padding: 10px 25px;
        }

        .tabela {
            width: 100%;
            border-collapse: collapse;
            background-color: rgba(255, 255, 255, 0.7);
        }

        .tabela th,
        .tabela td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .tabela th {
            background-color: rgba(0, 0, 0, 0.1);
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="form-box-wide">
        <h1 style="text-align:center;">Consulta de Índices Econômicos</h1>
        <?php if (!empty($mensagem)): ?>

            <div style="text-align:center; color:green; margin: -35px 0;"><?= $mensagem ?></div>
        <?php endif; ?>

        <form method="POST" style="text-align:center;">
            <button type="submit" name="atualizar" class="login" style="width: 250px; font-size: 14px;">Atualizar Índices</button>
        </form>

        <table class="tabela">
            <thead>
                <tr style="font-weight:bold; border-bottom: 2px solid #000;">
                    <th>ÍNDICE</th>
                    <th>DATA DE REFERÊNCIA</th>
                    <th>VALOR(%)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                    <?php while ($row = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nome']) ?></td>
                            <td><?= date('m/Y', strtotime($row['data_referencia'])) ?></td>
                            <td><?= number_format($row['valor'], 4, ',', '.') ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">Nenhum índice encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="register-link">
            <p><a href="../../index.php">Voltar para o menu</a></p>
        </div>
    </div>
</body>

</html>
