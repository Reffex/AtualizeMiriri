<?php
require_once '../../includes/auto_check.php';
require_once '../../includes/connect_app.php';

$termo = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

if (!empty($termo)) {
    $stmt = $mysqli->prepare("SELECT * FROM clientes WHERE nome LIKE ? OR documento LIKE ? ORDER BY id DESC");
    $like = '%' . $termo . '%';
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $clientes = $stmt->get_result();
    $stmt->close();
} else {
    $clientes = $mysqli->query("SELECT * FROM clientes ORDER BY id DESC");
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="../../assets/css/styles.css" />
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
        <div class="form-box">
            <h1 class="clientes-titulo">Clientes</h1>

            <?php if (isset($_GET['sucesso'])): ?>
                <div id="alerta-msg">
                    <?php if ($_GET['sucesso'] == 1): ?>
                        Cliente cadastrado!
                    <?php elseif ($_GET['sucesso'] == 3): ?>
                        Cliente excluído!
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="botao-centralizado" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="cadastrar.php">
                    <button class="login botao-criar">Criar Cliente</button>
                </a>
            </div>

            <div class="botao-filtrar" style="margin-top: -20px;">
                <form method="GET" action="" style="display: flex; gap: 5px;">
                    <input type="text" name="buscar" placeholder="Buscar cliente..." value="<?= htmlspecialchars($termo) ?>" class="input-busca">
                    <button type="submit" class="login" title="Filtrar">
                        <i class='bx bx-search'></i>
                    </button>
                </form>
            </div>

            <table class="tabela">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Documento</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($clientes->num_rows > 0): ?>
                        <?php while ($cliente = $clientes->fetch_assoc()): ?>
                            <tr class="linha-borda">
                                <td><?= htmlspecialchars($cliente['nome']) ?></td>
                                <td><?= htmlspecialchars($cliente['documento']) ?></td>
                                <td>
                                    <a href="editar.php?id=<?= $cliente['id'] ?>" title="Editar" class="link-sem-decoracao">
                                        <i class='bx bx-edit icone-acao editar'></i>
                                    </a>
                                    <a href="excluir.php?id=<?= $cliente['id'] ?>" onclick="return confirm('Deseja excluir?')" title="Excluir" class="link-sem-decoracao">
                                        <i class='bx bx-trash icone-acao excluir'></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center" style="padding:20px;">Nenhum cliente encontrado.</td>
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
