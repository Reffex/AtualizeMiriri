<?php
require_once '../../includes/auto_check.php';
require_once '../../includes/connect_app.php';


$id = $_GET['id'] ?? null;
if (!$id) {
    echo "ID da operação inválido.";
    exit;
}

$op = $mysqli->query("
        SELECT o.*, c.nome AS cliente_nome
        FROM operacoes o
        JOIN clientes c ON o.cliente_id = c.id
        WHERE o.id = $id
    ")->fetch_assoc();

$lancamentos = $mysqli->query("
        SELECT * FROM lancamentos
        WHERE operacao_id = $id
        ORDER BY data ASC
    ");

require_once '../../includes/calcular_operacao.php';

$valores = calcular_operacao($mysqli, $op, $lancamentos);

// Cálculo do saldo
$saldo = $op['valor_inicial'];
$lancamentos->data_seek(0);
while ($l = $lancamentos->fetch_assoc()) {
    if ($l['tipo'] === 'credito') {
        $saldo += $l['valor'];
    } else {
        $saldo -= $l['valor'];
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = $_POST['data'];
    $descricao = $_POST['descricao'];
    $valor = $_POST['valor'];
    $tipo = $_POST['tipo'];

    $stmt = $mysqli->prepare("INSERT INTO lancamentos (operacao_id, data, descricao, valor, tipo) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issds", $id, $data, $descricao, $valor, $tipo);
    if ($stmt->execute()) {
        header("Location: detalhes.php?id=$id");
        exit();
    }
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
            max-width: 1000px;
            margin: 20px auto;
            padding: 30px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            color: #000;
        }

        .info-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .info-header p {
            margin: 5px 0;
        }

        .section-title {
            text-align: center;
        }

        .tabela-lancamentos {
            width: 100%;
            margin: 20px auto;
            border-collapse: collapse;
            margin-bottom: 30px;
            background-color: rgba(255, 255, 255, 0.7);
        }

        .tabela-lancamentos th,
        .tabela-lancamentos td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .tabela-lancamentos th {
            background-color: rgba(0, 0, 0, 0.1);
            font-weight: 600;
        }

        .tabela-lancamentos tr:nth-child(even) {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .tabela-extrato {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            margin-bottom: 30px;
            background-color: rgba(255, 255, 255, 0.7);
        }

        .tabela-extrato th,
        .tabela-extrato td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .tabela-extrato th {
            background-color: rgba(0, 0, 0, 0.1);
            font-weight: 600;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: -10px;
        }

        .input-box {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 200px;
        }

        .input-box select,
        .input-box input {
            width: 100%;
            height: 45px;
            background-color: rgba(255, 255, 255, 0.7);
            border: 2px solid rgba(255, 255, 255, .2);
            border-radius: 40px;
            font-size: 14px;
            color: #333;
            padding: 10px;
        }

        .input-box select {
            appearance: none;
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 10px;
            margin-bottom: 40px;
        }

        .button-group button {
            padding: 10px 20px;
            border-radius: 40px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            background-color: #fff;
        }

        .button-group button:hover {
            background-color: transparent;
            border: 2px solid rgba(255, 255, 255, .2);
            color: #fff;
            transition: 0.5s;
        }

        .debito {
            color: red;
        }

        .credito {
            color: green;
        }
    </style>
</head>

<body>
    <div class="form-box-wide">
        <div class="info-header">
            <h1 class="section-title">Lançamentos</h1>

            <form method="POST">
                <div class="form-row">
                    <div class="input-box">
                        <input type="date" name="data" required>
                    </div>

                    <div class="input-box">
                        <input type="text" name="descricao" placeholder="Descrição" required>
                    </div>

                    <div class="input-box">
                        <select name="tipo" required>
                            <option value="debito">Débito</option>
                            <option value="credito">Crédito</option>
                        </select>
                    </div>

                    <div class="input-box">
                        <input type="number" step="0.01" name="valor" placeholder="Valor" required>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit">Salvar</button>
                </div>
            </form>

            <table class="tabela-lancamentos">
                <thead>
                    <tr style="font-weight:bold; border-bottom: 2px solid #000;">
                        <th>DATA</th>
                        <th>DESCRIÇÃO</th>
                        <th>VALOR</th>
                        <th>TIPO</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $lancamentos->data_seek(0);
                    while ($l = $lancamentos->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($l['data'])) ?></td>
                            <td><?= htmlspecialchars($l['descricao']) ?></td>
                            <td class="<?= $l['tipo'] === 'debito' ? 'debito' : 'credito' ?>">
                                R$ <?= number_format($l['valor'], 2, ',', '.') ?>
                            </td>
                            <td><?= ucfirst($l['tipo']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <h1 class="section-title">Extrato do Cálculo</h1>

            <table class="tabela-extrato">
                <thead>
                    <tr style="font-weight:bold; border-bottom: 2px solid #000;">
                        <th>MOVIMENTAÇÃO <br> ACUMULADA</th>
                        <th>CORREÇÃO <br> MONETÁRIA <br> ACUMULADA </th>
                        <th>JUROS <br> ACUMULADOS</th>
                        <th>MULTA</th>
                        <th>HONORÁRIOS</th>
                        <th>SALDO <br> TOTAL <br> ATUALIZADO</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>R$ <?= number_format($valores['movimentacao'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($valores['correcao'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($valores['juros'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($valores['multa'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($valores['honorarios'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($valores['saldo_atualizado'], 2, ',', '.') ?></td>
                    </tr>
                </tbody>
            </table>

            <table class="tabela-extrato">
                <thead>
                    <tr style="font-weight:bold; border-bottom: 2px solid #000;">
                        <th>DATA</th>
                        <th>DESCRIÇÃO</th>
                        <th>DÉBITO</th>
                        <th>CRÉDITO</th>
                        <th>SALDO</th>
                        <th>ÍNDICES</th>
                        <th>DIAS ÚTEIS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $lancamentos->data_seek(0);
                    $saldo_parcial = $op['valor_inicial'];
                    while ($l = $lancamentos->fetch_assoc()):
                        if ($l['tipo'] === 'debito') {
                            $debito = number_format($l['valor'], 2, ',', '.');
                            $credito = '';
                            $saldo_parcial -= $l['valor'];
                        } else {
                            $debito = '';
                            $credito = number_format($l['valor'], 2, ',', '.');
                            $saldo_parcial += $l['valor'];
                        }
                    ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($l['data'])) ?></td>
                            <td><?= htmlspecialchars($l['descricao']) ?></td>
                            <td class="debito"><?= $debito ?></td>
                            <td class="credito"><?= $credito ?></td>
                            <td>R$ <?= number_format($saldo_parcial, 2, ',', '.') ?></td>
                            <td>-</td>
                            <td>-</td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="register-link">
                <p><a href="../../pages/operacoes/listar.php">Voltar para operações</a></p>
            </div>
        </div>
    </div>
</body>

</html>
