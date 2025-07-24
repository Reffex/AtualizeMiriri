<?php
require_once '../../includes/auto_check.php';
require_once '../../includes/connect_app.php';

$mensagem = '';
if (isset($_GET['sucesso'])) {
    $mensagem = "Operação cadastrada com sucesso!";
}

$termo = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

if (!empty($termo)) {
    $stmt = $mysqli->prepare("SELECT o.*, c.nome AS cliente_nome 
                             FROM operacoes o 
                             JOIN clientes c ON o.cliente_id = c.id 
                             WHERE c.nome LIKE ? OR o.identificador LIKE ?
                             ORDER BY o.data_criacao DESC");
    $like = '%' . $termo . '%';
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $operacoes = $stmt->get_result();
    $stmt->close();
} else {
    $operacoes = $mysqli->query("SELECT o.*, c.nome AS cliente_nome 
                                FROM operacoes o 
                                JOIN clientes c ON o.cliente_id = c.id 
                                ORDER BY o.data_criacao DESC");
}

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

<body class="listar-operacoes">
    <div class="form-box-wide listar-operacoes">
        <h1 class="titulo-centralizado">Operações</h1>

        <?php if (!empty($mensagem)): ?>
            <div id="alerta-msg" class="alerta-msg">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>

        <div class="botao-centralizado" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="criar.php">
                <button class="login botao-criar">Criar Operação</button>
            </a>
        </div>

        <div class="botao-filtrar" style="margin-top: -10px;">
            <form method="GET" action="" style="display: flex; gap: 5px;">
                <input type="text" name="buscar" placeholder="Buscar operação..." value="<?= htmlspecialchars($termo) ?>" class="input-busca">
                <button type="submit" class="login" title="Filtrar">
                    <i class='bx bx-search'></i>
                </button>
            </form>
        </div>

        <div style="overflow-x:auto;">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Identificador</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($operacoes->num_rows > 0): ?>
                        <?php while ($op = $operacoes->fetch_assoc()): ?>
                            <tr class="linha-clicavel" data-id="<?= $op['id'] ?>">
                                <td><?= htmlspecialchars($op['cliente_nome']) ?></td>
                                <td><?= htmlspecialchars($op['identificador']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($op['data_criacao'])) ?></td>
                                <td>
                                    <div class="action-icons">
                                        <a href="editar.php?id=<?= $op['id'] ?>" title="Editar">
                                            <i class='bx bx-edit icone-acao'></i>
                                        </a>
                                        <a href="excluir.php?id=<?= $op['id'] ?>" onclick="return confirm('Deseja excluir?')" title="Excluir">
                                            <i class='bx bx-trash icone-acao icone-excluir'></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center" style="padding:20px;">
                                <?= empty($termo) ? 'Nenhuma operação cadastrada.' : 'Nenhuma operação encontrada.' ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="register-link">
            <p><a href="../../index.php">Voltar para o menu</a></p>
        </div>
    </div>
    <script>
        document.querySelectorAll('.linha-clicavel').forEach(function(linha) {
            linha.addEventListener('click', function(e) {
                if (!e.target.closest('a')) {
                    const id = this.getAttribute('data-id');
                    if (id) {
                        window.location.href = 'detalhes.php?id=' + id;
                    }
                }
            });
        });
    </script>
</body>

</html>
