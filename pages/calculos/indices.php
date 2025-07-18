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
</head>

<body>
    <div class="form-box-wide">
        <h1>Consulta de Índices Econômicos</h1>

        <?php if (!empty($mensagem)): ?>
            <div class="mensagem-atualizar"><?= $mensagem ?></div>
        <?php endif; ?>

        <form method="POST">
            <button type="submit" name="atualizar" class="login">Atualizar</button>
        </form>

        <table class="tabela">
            <thead>
                <tr>
                    <th>Índice</th>
                    <th>Data de referência</th>
                    <th>Valor</th>
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
