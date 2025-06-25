<?php 
    require_once '../../includes/auto_check.php';
    require_once '../../includes/connect_app.php';

    $mensagem = '';

    $clientes = $mysqli->query("SELECT id, nome FROM clientes ORDER BY nome");

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $cliente_id = $_POST['cliente_id'];
        $identificador = $_POST['identificador'];
        $indexador = $_POST['indexador'];
        $periodicidade = $_POST['periodicidade'];
        $valor_inicial = $_POST['valor_inicial'];

        $stmt = $mysqli->prepare("INSERT INTO operacoes (cliente_id, identificador, indexador, periodicidade, valor_inicial) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issss", $cliente_id, $identificador, $indexador, $periodicidade, $valor_inicial);
            if ($stmt->execute()) {
                header("Location: listar.php?sucesso=1");
                exit();
            } else {
                $mensagem = "Erro ao cadastrar operação: " . $stmt->error;
            }
        } else {
            $mensagem = "Erro na query: " . $mysqli->error;
        }
    }
?>

<?php include_once '../../includes/header.php'; ?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../assets/css/styles.css">
</head>
<body>
    <div class="container">
    <div class="form-box">
        <h1 style="text-align:center;">Nova Operação</h1>

        <?php if (!empty($mensagem)): ?>
            <p style="text-align:center; color:red; font-weight:bold;"><?= $mensagem ?></p>
        <?php endif; ?>

        <form method="POST">

            <div class="input-box">
                <select name="cliente_id" required class="form-control" style="width:100%; padding: 10px; border-radius: 40px;">
                    <option value="">Selecione um cliente</option>
                    <?php while ($cliente = $clientes->fetch_assoc()): ?>
                        <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nome']) ?></option>
                    <?php endwhile; ?>
                </select>
                <i class='bx bxs-user'></i>
            </div>

            <div class="input-box">
                <input type="text" name="identificador" placeholder="Identificador da operação" required>
                <i class='bx bxs-id-card'></i>
            </div>

            <div class="input-box">
                <select name="indexador" required class="form-control" style="width:100%; padding: 10px; border-radius: 40px;">
                    <option value="CDI">CDI</option>
                    <option value="IPCA">IPCA</option>
                    <option value="Manual">Manual</option>
                </select>
                <i class='bx bxs-bar-chart-square'></i>
            </div>

            <div class="input-box">
                <select name="periodicidade" required class="form-control" style="width:100%; padding: 10px; border-radius: 40px;">
                    <option value="diário">Diário</option>
                    <option value="mensal">Mensal</option>
                </select>
                <i class='bx bxs-calendar'></i>
            </div>

            <div class="input-box">
                <input type="number" step="0.01" name="valor_inicial" placeholder="Valor inicial da operação" required>
                <i class='bx bxs-dollar-circle'></i>
            </div>

            <button type="submit" class="login">Cadastrar Operação</button>

            <div class="register-link">
                <p><a href="listar.php">Voltar para lista</a></p>
            </div>
        </form>
    </div>
    </div>
</body>
</html>

<?php include_once '../../includes/footer.php'; ?>
