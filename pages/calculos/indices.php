<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/auto_check.php';
require_once '../../includes/connect_app.php';
require_once '../../includes/funcoes_indices.php';

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar'])) {
    $mensagem = atualizar_indices($mysqli);
}

$termo = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

if (!empty($termo)) {
    $stmt = $mysqli->prepare("SELECT * FROM indices WHERE nome LIKE ? ORDER BY data_referencia DESC");
    $like = '%' . $termo . '%';
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $stmt->close();
} else {
    $resultado = $mysqli->query("SELECT * FROM indices ORDER BY data_referencia DESC");
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Consulta de Índices</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
        <h1>Índices Econômicos</h1>

        <?php if (!empty($mensagem)): ?>
            <div id="alerta-msg" class="alerta-msg"><?= $mensagem ?></div>
        <?php endif; ?>

        <form method="POST">
            <button type="submit" name="atualizar" class="login botao-criar">Atualizar Índices</button>
        </form>

        <div class="botao-filtrar">
            <form method="GET" action="" style="display: flex; gap: 5px;">
                <input type="text" name="buscar" placeholder="Buscar índice..." value="<?= htmlspecialchars($termo) ?>" class="input-busca">
                <button type="submit" class="login" title="Filtrar">
                    <i class='bx bx-search'></i>
                </button>
            </form>
        </div>

        <table class="tabela">
            <thead>
                <tr>
                    <th>Índice</th>
                    <th>Data de referência</th>
                    <th>Valor (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                    <?php while ($row = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nome']) ?></td>
                            <td><?= date('d/m/Y', strtotime($row['data_referencia'])) ?></td>
                            <td><?= number_format($row['valor'], 4, ',', '.') ?>%</td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">
                            <?= empty($termo) ? 'Nenhum índice encontrado.' : 'Nenhum índice correspondente à busca.' ?>
                        </td>
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
