<?php
require_once '../../includes/auto_check.php';
require_once '../../includes/connect_app.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    echo "ID da operação inválido.";
    exit;
}

// Obter dados da operação
$stmt = $mysqli->prepare("SELECT o.*, c.nome AS cliente_nome FROM operacoes o JOIN clientes c ON o.cliente_id = c.id WHERE o.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$op = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Obter lançamentos
$stmt = $mysqli->prepare("SELECT * FROM lancamentos WHERE operacao_id = ? ORDER BY data ASC");
$stmt->bind_param("i", $id);
$stmt->execute();
$lancamentos = $stmt->get_result();
$stmt->close();

// Calcular operação
require_once '../../includes/calcular_operacao.php';
$valores = calcular_operacao($mysqli, $op, $lancamentos);

// Processar formulários
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Adicionar novo lançamento
    if (isset($_POST['adicionar_lancamento'])) {
        $data = $_POST['data'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $valor = floatval(str_replace(',', '.', $_POST['valor'] ?? '0'));
        $tipo = $_POST['tipo'] ?? '';

        if ($data && $descricao && $valor > 0 && in_array($tipo, ['credito', 'debito'])) {
            $stmt = $mysqli->prepare("INSERT INTO lancamentos (operacao_id, data, descricao, valor, tipo) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issds", $id, $data, $descricao, $valor, $tipo);
            $stmt->execute();
            $stmt->close();
            header("Location: detalhes.php?id=$id");
            exit;
        }
    }

    // Editar lançamento existente
    if (isset($_POST['editar_lancamento'])) {
        $lancamento_id = $_POST['lancamento_id'] ?? null;
        $data = $_POST['data'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $valor = floatval(str_replace(',', '.', $_POST['valor'] ?? '0'));
        $tipo = $_POST['tipo'] ?? '';

        if ($lancamento_id && $data && $descricao && $valor > 0 && in_array($tipo, ['credito', 'debito'])) {
            $stmt = $mysqli->prepare("UPDATE lancamentos SET data = ?, descricao = ?, valor = ?, tipo = ? WHERE id = ?");
            $stmt->bind_param("ssdsi", $data, $descricao, $valor, $tipo, $lancamento_id);
            $stmt->execute();
            $stmt->close();
            header("Location: detalhes.php?id=$id");
            exit;
        }
    }

    // Excluir lançamento
    if (isset($_POST['excluir_lancamento'])) {
        $lancamento_id = $_POST['lancamento_id'] ?? null;

        if ($lancamento_id) {
            $stmt = $mysqli->prepare("DELETE FROM lancamentos WHERE id = ?");
            $stmt->bind_param("i", $lancamento_id);
            $stmt->execute();
            $stmt->close();
            header("Location: detalhes.php?id=$id");
            exit;
        }
    }
}

// Garantir que as chaves existem para evitar warnings
$correcao = $valores['correcao'] ?? 0.0;
$juros = $valores['juros'] ?? 0.0;
$multa = $valores['multa'] ?? 0.0;
$honorarios = $valores['honorarios'] ?? 0.0;
$saldo_atualizado = $valores['saldo_atualizado'] ?? 0.0;
$movimentacao = $valores['movimentacao'] ?? 0.0;
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <title>Detalhes da Operação</title>
</head>

<body class="detalhes-operacao">
    <div class="form-box-wide detalhes-operacao">
        <div class="info-header">
            <h1 class="section-title">Lançamentos</h1>

            <!-- Tabela de Lançamentos Editável -->
            <table class="tabela-extrato">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th>Tipo</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Formulário para novo lançamento -->
                    <tr>
                        <form method="POST" id="formNovoLancamento">
                            <input type="hidden" name="adicionar_lancamento" value="1">
                            <td>
                                <input type="date" name="data" required class="table-input">
                            </td>
                            <td>
                                <input type="text" name="descricao" placeholder="Descrição" required class="table-input">
                            </td>
                            <td>
                                <input type="text" name="valor" placeholder="0,00" required class="table-input" oninput="formatarValor(this)">
                            </td>
                            <td>
                                <select name="tipo" required class="table-select">
                                    <option value="debito">Débito</option>
                                    <option value="credito">Crédito</option>
                                </select>
                            </td>
                            <td>
                                <button type="submit" class="btn-icon">
                                    <i class='bx bx-save'></i>
                                </button>
                            </td>
                        </form>
                    </tr>

                    <!-- Lançamentos existentes -->
                    <?php
                    $lancamentos->data_seek(0);
                    while ($l = $lancamentos->fetch_assoc()):
                    ?>
                        <tr data-id="<?= $l['id'] ?>">
                            <td class="editable" data-field="data"><?= date('d/m/Y', strtotime($l['data'])) ?></td>
                            <td class="editable" data-field="descricao"><?= htmlspecialchars($l['descricao']) ?></td>
                            <td class="editable" data-field="valor">R$ <?= number_format($l['valor'], 2, ',', '.') ?></td>
                            <td class="editable-select" data-field="tipo">
                                <?= $l['tipo'] === 'debito' ? 'Débito' : 'Crédito' ?>
                            </td>
                            <td>
                                <div class="action-icons">
                                    <form method="POST" class="form-inline">
                                        <input type="hidden" name="lancamento_id" value="<?= $l['id'] ?>">
                                        <button type="submit" name="excluir_lancamento" class="btn-icon">
                                            <i class='bx bx-trash'></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <h1 class="section-title">Extrato do Cálculo</h1>

            <!-- Tabela de Resumo do Extrato -->
            <table class="tabela-extrato">
                <thead>
                    <tr>
                        <th>Movimentação<br>acumulada no<br>período</th>
                        <th>Correção<br>monetária<br>acumulada no<br>período</th>
                        <th>Juros<br>acumulados<br>no período</th>
                        <th>Multa</th>
                        <th>Honorários</th>
                        <th>Saldo total<br>atualizado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>R$ <?= number_format($movimentacao, 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($correcao, 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($juros, 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($multa, 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($honorarios, 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($saldo_atualizado, 2, ',', '.') ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Tabela de Extrato Detalhado -->
            <table class="tabela-extrato">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Débito</th>
                        <th>Crédito</th>
                        <th>Saldo</th>
                        <th>Índices</th>
                        <th>Dias úteis</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($valores['extrato_detalhado'] as $linha): ?>
                        <tr>
                            <td><?= htmlspecialchars($linha['data']) ?></td>
                            <td><?= htmlspecialchars($linha['descricao']) ?></td>
                            <td class="debito"><?= $linha['debito'] ? 'R$ ' . number_format($linha['debito'], 2, ',', '.') : '' ?></td>
                            <td class="credito"><?= $linha['credito'] ? 'R$ ' . number_format($linha['credito'], 2, ',', '.') : '' ?></td>
                            <td>R$ <?= number_format($linha['saldo'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($linha['indice']) ?></td>
                            <td><?= htmlspecialchars($linha['dias_uteis']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="register-link">
                <p><a href="../../pages/operacoes/listar.php">Voltar para operações</a></p>
            </div>
        </div>
    </div>

    <!-- Modal de edição -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3>Editar Lançamento</h3>
            <form id="editForm" method="POST">
                <input type="hidden" name="editar_lancamento" value="1">
                <input type="hidden" name="lancamento_id" id="editLancamentoId">

                <div class="input-box">
                    <label>Data</label>
                    <input type="date" name="data" id="editData" required>
                </div>

                <div class="input-box">
                    <label>Descrição</label>
                    <input type="text" name="descricao" id="editDescricao" required>
                </div>

                <div class="input-box">
                    <label>Valor</label>
                    <input type="text" name="valor" id="editValor" placeholder="0,00" required oninput="formatarValor(this)">
                </div>

                <div class="input-box">
                    <label>Tipo</label>
                    <select name="tipo" id="editTipo" required>
                        <option value="debito">Débito</option>
                        <option value="credito">Crédito</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" onclick="document.getElementById('editModal').style.display = 'none'" class="modal-button modal-cancel">Cancelar</button>
                    <button type="submit" class="modal-button modal-save">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Função para formatar valores monetários
        function formatarValor(input) {
            let valor = input.value.replace(/[^\d,]/g, '');

            if ((valor.match(/,/g) || []).length > 1) {
                valor = valor.substring(0, valor.lastIndexOf(','));
            }

            if (valor.indexOf(',') === -1 && valor.length > 0) {
                valor += ',00';
            }

            if (valor.indexOf(',') !== -1) {
                const partes = valor.split(',');
                if (partes[1].length === 0) {
                    valor += '00';
                } else if (partes[1].length === 1) {
                    valor += '0';
                } else if (partes[1].length > 2) {
                    valor = partes[0] + ',' + partes[1].substring(0, 2);
                }
            }

            input.value = valor;
        }

        // Função para edição ao clicar na célula
        document.querySelectorAll('.editable').forEach(cell => {
            cell.addEventListener('click', function() {
                const row = this.parentElement;
                const id = row.getAttribute('data-id');
                const field = this.getAttribute('data-field');
                const value = this.textContent.trim();

                document.getElementById('editLancamentoId').value = id;

                if (field === 'data') {
                    const parts = value.split('/');
                    const dateValue = `${parts[2]}-${parts[1]}-${parts[0]}`;
                    document.getElementById('editData').value = dateValue;
                } else if (field === 'valor') {
                    document.getElementById('editValor').value = value.replace('R$ ', '');
                } else {
                    document.getElementById('editDescricao').value = value;
                }

                document.getElementById('editModal').style.display = 'flex';
            });
        });

        // Função para edição do tipo
        document.querySelectorAll('.editable-select').forEach(cell => {
            cell.addEventListener('click', function() {
                const row = this.parentElement;
                const id = row.getAttribute('data-id');
                const currentType = this.textContent.trim().toLowerCase() === 'débito' ? 'debito' : 'credito';

                document.getElementById('editLancamentoId').value = id;
                document.getElementById('editTipo').value = currentType;

                const dataCell = row.querySelector('[data-field="data"]');
                const descricaoCell = row.querySelector('[data-field="descricao"]');
                const valorCell = row.querySelector('[data-field="valor"]');

                const parts = dataCell.textContent.trim().split('/');
                document.getElementById('editData').value = `${parts[2]}-${parts[1]}-${parts[0]}`;

                document.getElementById('editDescricao').value = descricaoCell.textContent.trim();
                document.getElementById('editValor').value = valorCell.textContent.trim().replace('R$ ', '');

                document.getElementById('editModal').style.display = 'flex';
            });
        });

        // Fechar modal ao clicar fora
        window.addEventListener('click', function(event) {
            if (event.target === document.getElementById('editModal')) {
                document.getElementById('editModal').style.display = 'none';
            }
        });
    </script>
</body>

</html>
