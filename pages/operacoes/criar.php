<?php
require_once '../../includes/auto_check.php';
require_once '../../includes/connect_app.php';

function normalizarNumero($valor)
{
    if (is_string($valor)) {
        $valor = str_replace(['.', ','], ['', '.'], $valor);
    }
    return $valor;
}

$mensagem = '';
$clientes = $mysqli->query("SELECT id, nome, documento FROM clientes ORDER BY nome");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $cliente_id = (int) $_POST['cliente_id'];
        $identificador = trim($_POST['identificador']);
        $indexador = $_POST['indexador'];
        $periodicidade = $_POST['periodicidade'];
        $data_criacao = date('Y-m-d');

        $atualizar_ate = $_POST['atualizar_ate'];
        $atualizar_dia_debito = (int) $_POST['atualizar_dia_debito'];
        $atualizar_correcao_monetaria = normalizarNumero($_POST['atualizar_correcao_monetaria']);
        $atualizar_juros_nominais = normalizarNumero($_POST['atualizar_juros_nominais']);

        $valor_multa = ($_POST['valor_multa'] !== '') ? normalizarNumero($_POST['valor_multa']) : 0.0;
        $valor_honorarios = ($_POST['valor_honorarios'] !== '') ? normalizarNumero($_POST['valor_honorarios']) : 0.0;
        $observacao = trim($_POST['observacao']);

        if (strtotime($atualizar_ate) === false) {
            throw new Exception("Datas inválidas informadas");
        }

        $stmt = $mysqli->prepare("INSERT INTO operacoes (
    cliente_id, identificador, indexador, periodicidade,
    atualizar_ate, atualizar_dia_debito, atualizar_correcao_monetaria, atualizar_juros_nominais,
    valor_multa, valor_honorarios, observacao, data_criacao
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if (!$stmt) {
            throw new Exception("Erro na preparação da query: " . $mysqli->error);
        }

        $stmt->bind_param(
            "issdssidddss",
            $cliente_id,
            $identificador,
            $indexador,
            $periodicidade,
            $atualizar_ate,
            $atualizar_dia_debito,
            $atualizar_correcao_monetaria,
            $atualizar_juros_nominais,
            $valor_multa,
            $valor_honorarios,
            $observacao,
            $data_criacao
        );

        if ($stmt->execute()) {
            header("Location: listar.php?sucesso=1");
            exit();
        } else {
            throw new Exception("Erro ao cadastrar operação: " . $stmt->error);
        }
    } catch (Exception $e) {
        $mensagem = $e->getMessage();
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
    <title>Atualize Miriri</title>
</head>

<body>

    <div class="botao-voltar-topo">
        <a href="../operacoes/listar.php">
            <i class='bx bx-arrow-back'></i> Sair
        </a>
    </div>

    <div class="form-box nova-operacao-box">
        <h1 class="nova-operacao-titulo">Nova Operação</h1><br>
        <?php if (!empty($mensagem)): ?>
            <p class="mensagem-erro"><?= htmlspecialchars($mensagem) ?></p>
        <?php endif; ?>
        <form method="POST">

            <!-- Cliente e identificador -->
            <div class="form-row">
                <div class="form-item">
                    <label for="cliente_id">Cliente:</label>
                    <select name="cliente_id" id="cliente_id" required>
                        <option value="">Selecione um cliente</option>
                        <?php while ($cliente = $clientes->fetch_assoc()): ?>
                            <option value="<?= $cliente['id'] ?>">
                                <?= htmlspecialchars($cliente['nome']) ?> (<?= htmlspecialchars($cliente['documento']) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-item">
                    <label for="identificador">Identificador da operação:</label>
                    <input type="text" name="identificador" id="identificador" required>
                </div>
            </div>

            <!-- Indexador e Periodicidade -->
            <div class="form-row">
                <div class="form-item">
                    <label for="indexador">Indexador:</label>
                    <select name="indexador" id="indexador" required>
                        <option value="CDI (CETIP) Diário">CDI (CETIP) Diário</option>
                        <option value="IPCA">IPCA</option>
                    </select>
                </div>
                <div class="form-item">
                    <label for="periodicidade">Periodicidade:</label>
                    <select name="periodicidade" id="periodicidade" required>
                        <option value="Mensal" selected>Mensal</option>
                    </select>
                </div>
            </div>
            <br><br>

            <!-- Atualizar até -->
            <div class="form-row">
                <div class="form-item">
                    <label for="atualizar_ate">Atualizar até:</label>
                    <input type="date" name="atualizar_ate" id="atualizar_ate" required>
                </div>
                <div class="form-item">
                    <label for="atualizar_dia_debito">Dia do débito:</label>
                    <input type="number" name="atualizar_dia_debito" id="atualizar_dia_debito" min="1" max="31" value="1" required>
                </div>
                <div class="form-item">
                    <label for="atualizar_correcao_monetaria">Correção monetária (%):</label>
                    <input type="text" step="0.001" name="atualizar_correcao_monetaria" id="atualizar_correcao_monetaria" value="100,000" pattern="^[0-9]+([,\.][0-9]+)?$" required>
                </div>
                <div class="form-item">
                    <label for="atualizar_juros_nominais">Juros nominais (%):</label>
                    <input type="text" step="0.001" name="atualizar_juros_nominais" id="atualizar_juros_nominais" value="12,000" pattern="^[0-9]+([,\.][0-9]+)?$" required>
                </div>
            </div>

            <br><br>

            <!-- Multa e Honorários -->
            <div class="form-row">
                <div class="form-item">
                    <label for="valor_multa">Valor da multa (R$):</label>
                    <input type="text" step="0.01" name="valor_multa" id="valor_multa" placeholder="0,00" pattern="^[0-9]+([,\.][0-9]+)?$">
                </div>
                <div class="form-item">
                    <label for="valor_honorarios">Valor dos honorários (R$):</label>
                    <input type="text" step="0.01" name="valor_honorarios" id="valor_honorarios" placeholder="0,00" pattern="^[0-9]+([,\.][0-9]+)?$">
                </div>
            </div>

            <!-- Observação -->
            <div class="form-item">
                <label for="observacao">Observação:</label>
                <textarea name="observacao" id="observacao" placeholder="Opcional"></textarea>
            </div>

            <div class="button-group">
                <button type="submit" class="btn-criar">Criar operação</button>
            </div>

        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hoje = new Date();
            const dia = String(hoje.getDate()).padStart(2, '0');
            const mes = String(hoje.getMonth() + 1).padStart(2, '0');
            const ano = hoje.getFullYear();
            const dataHoje = `${ano}-${mes}-${dia}`;

            document.getElementById('atualizar_ate').value = dataHoje;
        });
    </script>

</body>

</html>
