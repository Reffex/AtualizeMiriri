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

        <div class="botao-centralizado">
            <a href="criar.php">
                <button class="login botao-criar">Criar Nova Operação</button>
            </a>
        </div>

        <div style="overflow-x:auto;">
            <table class="tabela">
                <thead>
                    <tr class="linha-destaque">
                        <th>Cliente</th>
                        <th>Identificador</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($operacoes->num_rows > 0): ?>
                        <?php while ($op = $operacoes->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($op['cliente_nome']) ?></td>
                                <td><?= htmlspecialchars($op['identificador']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($op['data_criacao'])) ?></td>
                                <td>
                                    <div class="action-icons">
                                        <a href="detalhes.php?id=<?= $op['id'] ?>" title="Detalhes">
                                            <i class='bx bx-detail icone-acao'></i>
                                        </a>
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
                            <td colspan="4" class="text-left" style="padding:20px;">Nenhuma operação cadastrada.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="register-link">
            <p><a href="../../index.php">Voltar para o menu</a></p>
        </div>
    </div>
</body>

</html>
