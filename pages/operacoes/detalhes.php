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
            background-color: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            backdrop-filter: blur(20px);
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
            margin: 20px 0;
        }
        
        .tabela-lancamentos {
            width: 100%;
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
            margin-top: 20px;
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
        
        .resumo-extrato {
            margin-bottom: 20px;
            background-color: rgba(255, 255, 255, 0.7);
            padding: 15px;
            border-radius: 5px;
        }
        
        .resumo-extrato table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .resumo-extrato th,
        .resumo-extrato td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        
        .input-box {
            margin: 15px 0;
        }
        
        .input-box select,
        .input-box input {
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.7);
            border: 2px solid rgba(255, 255, 255, .2);
            border-radius: 40px;
            outline: none;
            font-size: 16px;
            color: #333;
            padding: 10px 45px 10px 20px;
        }
        
        .input-box select {
            appearance: none;
        }
        
        .button-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
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
             <h2 class="section-title">Lançamentos</h2>

        <form method="POST">
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

            <div class="button-group">
                <button type="submit">Salvar</button>
                <button type="button" onclick="window.location.href='listar.php'">Cancelar</button>
            </div>
        </form>

        <table class="tabela-lancamentos">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Valor</th>
                    <th>Tipo</th>
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
            </tbody>1zz
        </table>

        <h2 class="section-title">Extrato do Cálculo</h2>
        <div class="resumo-extrato">
            <table>
                <thead>
                    <tr>
                        <th>Movimentação acumulada</th>
                        <th>Correção monetária acumulada</th>
                        <th>Juros acumulados</th>
                        <th>Multa</th>
                        <th>Honorários</th>
                        <th>Saldo total atualizado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>R$ <?= number_format($saldo, 2, ',', '.') ?></td>
                        <td>R$ 0,00</td>
                        <td>R$ 0,00</td>
                        <td>R$ 0,00</td>
                        <td>R$ 0,00</td>
                        <td>R$ <?= number_format($saldo, 2, ',', '.') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <table class="tabela-extrato">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Débito</th>
                    <th>Crédito</th>
                    <th>Saldo</th>
                    <th>Índice</th>
                    <th>Dias úteis</th>
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
                        <td><?= $debito ?></td>
                        <td><?= $credito ?></td>
                        <td>R$ <?= number_format($saldo_parcial, 2, ',', '.') ?></td>
                        <td>-</td>
                        <td>-</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <div class="button-group" style="margin-top: 30px;">
            <button onclick="window.location.href='listar.php'">Voltar para Operações</button>
        </div>
    </div>
</body>
</html>
