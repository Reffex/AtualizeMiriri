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

$indices_disponiveis = ['IPCA', 'CDI (CETIP) Diário'];

$indice_selecionado = isset($_GET['indice']) ? trim($_GET['indice']) : '';
$data_inicio = isset($_GET['data_inicio']) ? trim($_GET['data_inicio']) : '';
$data_fim = isset($_GET['data_fim']) ? trim($_GET['data_fim']) : '';

$where = [];
$params = [];
$types = '';

if (!empty($indice_selecionado)) {
    $where[] = "nome = ?";
    $params[] = $indice_selecionado;
    $types .= 's';
}

if (!empty($data_inicio)) {
    $where[] = "data_referencia >= ?";
    $params[] = date('Y-m-d', strtotime(str_replace('/', '-', $data_inicio)));
    $types .= 's';
}

if (!empty($data_fim)) {
    $where[] = "data_referencia <= ?";
    $params[] = date('Y-m-d', strtotime(str_replace('/', '-', $data_fim)));
    $types .= 's';
}

$sql = "SELECT * FROM indices";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY data_referencia DESC";

$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();
$stmt->close();
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

    <div class="botao-voltar-topo">
        <a href="../../index.php">
            <i class='bx bx-arrow-back'></i> Sair
        </a>
    </div>

    <div class="form-box-wide-indices">
        <h1>Índices Econômicos</h1><br>

        <?php if (!empty($mensagem)): ?>
            <div id="alerta-msg" class="alerta-msg"><?= $mensagem ?></div>
        <?php endif; ?>

        <form method="POST">
            <button type="submit" name="atualizar" class="login botao-criar">Atualizar Índices</button>
        </form>

        <br>

        <div class="filtro-container">
            <form method="GET" action="" class="filtro-form">
                <div class="filtro-linha-campos">
                    <div class="form-item">
                        <label for="indice" style="color: white;">Índice:</label>
                        <select id="indice" name="indice">
                            <option value="">Todos os índices</option>
                            <?php foreach ($indices_disponiveis as $indice): ?>
                                <option value="<?= $indice ?>" <?= $indice === $indice_selecionado ? 'selected' : '' ?>>
                                    <?= $indice ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-item">
                        <label for="data_inicio" style="color: white;">Data inicial:</label>
                        <input type="date" id="data_inicio" name="data_inicio" value="<?= htmlspecialchars($data_inicio) ?>">
                    </div>

                    <div class="form-item">
                        <label for="data_fim" style="color: white;">Data final:</label>
                        <input type="date" id="data_fim" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>">
                    </div>
                </div>

                <div class="filtro-item">
                    <button type="submit" class="login">
                        <i class='bx bx-filter-alt'></i> Filtrar
                    </button>
                    <?php if (!empty($indice_selecionado) || !empty($data_inicio) || !empty($data_fim)): ?>
                        <a href="?" class="login" style="padding: 8px 15px; margin-left: 5px;">Limpar</a>
                    <?php endif; ?>
                </div>
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
                            Nenhum índice encontrado.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>
</body>

</html>
