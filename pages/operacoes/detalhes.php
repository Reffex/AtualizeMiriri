<?php
require_once '../../includes/auto_check.php';
require_once '../../includes/connect_app.php';

function normalizarNumero($valor) {
    if (is_string($valor)) {
        $valor = str_replace(['.', ','], ['', '.'], $valor);
    }
    return $valor;
}

function existeDebitoNaOperacao($mysqli, $operacao_id) {
    $stmt = $mysqli->prepare("SELECT COUNT(*) as total FROM lancamentos WHERE operacao_id = ? AND tipo = 'debito'");
    if (!$stmt) {
        error_log("Erro na preparação: " . $mysqli->error);
        return false;
    }
    
    $stmt->bind_param("i", $operacao_id);
    if (!$stmt->execute()) {
        error_log("Erro na execução: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['total'] > 0;
}

$id = $_GET['id'] ?? null;
$mensagem = '';

if (!$id || !is_numeric($id)) {
    header("Location: listar.php?erro=1");
    exit;
}

// Obter dados da operação
$stmt = $mysqli->prepare("
    SELECT o.*, c.nome AS cliente_nome, c.documento
    FROM operacoes o
    JOIN clientes c ON c.id = o.cliente_id
    WHERE o.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$op = $result->fetch_assoc();

if (!$op) {
    header("Location: listar.php?erro=2");
    exit;
}

// Obter lançamentos
$stmt = $mysqli->prepare("SELECT * FROM lancamentos WHERE operacao_id = ? ORDER BY data ASC");
$stmt->bind_param("i", $id);
$stmt->execute();
$lancamentos = $stmt->get_result();

// Calcular operação
require_once '../../includes/calcular_operacao.php';
$valores = calcular_operacao($mysqli, $op, $lancamentos);

// Processar formulários
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Editar operação
    if (isset($_POST['editar_operacao'])) {
        try {
            $identificador = trim($_POST['identificador']);
            $indexador = $_POST['indexador'];
            $periodicidade = $_POST['periodicidade'];

            $atualizar_ate = $_POST['atualizar_ate'];
            $atualizar_dia_debito = (int) $_POST['atualizar_dia_debito'];
            $atualizar_correcao_monetaria = normalizarNumero($_POST['atualizar_correcao_monetaria']);
            $atualizar_juros_nominais = normalizarNumero($_POST['atualizar_juros_nominais']);

            $alterar_taxas_em = $_POST['alterar_taxas_em'];
            $alterar_dia_debito = (int) $_POST['alterar_dia_debito'];
            $alterar_correcao_monetaria = normalizarNumero($_POST['alterar_correcao_monetaria']);
            $alterar_juros_nominais = normalizarNumero($_POST['alterar_juros_nominais']);

            $valor_multa = ($_POST['valor_multa'] !== '') ? normalizarNumero($_POST['valor_multa']) : 0.0;
            $valor_honorarios = ($_POST['valor_honorarios'] !== '') ? normalizarNumero($_POST['valor_honorarios']) : 0.0;
            $observacao = trim($_POST['observacao']);

            if (strtotime($atualizar_ate) === false || strtotime($alterar_taxas_em) === false) {
                throw new Exception("Datas inválidas informadas");
            }

            $stmt = $mysqli->prepare("UPDATE operacoes SET 
                identificador = ?, indexador = ?, periodicidade = ?, 
                atualizar_ate = ?, atualizar_dia_debito = ?, atualizar_correcao_monetaria = ?, atualizar_juros_nominais = ?, 
                alterar_taxas_em = ?, alterar_dia_debito = ?, alterar_correcao_monetaria = ?, alterar_juros_nominais = ?, 
                valor_multa = ?, valor_honorarios = ?, observacao = ?
                WHERE id = ?
            ");

            if (!$stmt) {
                throw new Exception("Erro na preparação da query: " . $mysqli->error);
            }

            $stmt->bind_param(
                "ssssiddsidddssi",
                $identificador,
                $indexador,
                $periodicidade,
                $atualizar_ate,
                $atualizar_dia_debito,
                $atualizar_correcao_monetaria,
                $atualizar_juros_nominais,
                $alterar_taxas_em,
                $alterar_dia_debito,
                $alterar_correcao_monetaria,
                $alterar_juros_nominais,
                $valor_multa,
                $valor_honorarios,
                $observacao,
                $id
            );

            if ($stmt->execute()) {
                header("Location: detalhes.php?id=$id&sucesso=1");
                exit();
            } else {
                throw new Exception("Erro ao atualizar operação: " . $stmt->error);
            }
        } catch (Exception $e) {
            $mensagem = $e->getMessage();
        }
    }

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
    <title>Atualize Miriri</title>
</head>

<body>
    <div class="botao-voltar-topo">
        <a href="../operacoes/listar.php">
            <i class='bx bx-arrow-back'></i> Sair
        </a>
    </div>

    <div class="form-box-wide-detalhes">
        <h1 class="section-title" style="color: white;">Detalhes da operação</h1><br>

        <?php if (!empty($mensagem)): ?>
            <div id="alerta-msg" class="alerta-msg"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] == 1): ?>
            <div id="alerta-msg" class="alerta-msg">Operação atualizada com sucesso!</div>
        <?php endif; ?>

        <form method="POST" id="formEditarOperacao">
            <input type="hidden" name="editar_operacao" value="1">

            <!-- Cliente e identificador -->
            <div class="form-row">
                <div class="form-item">
                    <label for="cliente_nome" style="color: white;">Cliente:</label>
                    <input type="text" value="<?= htmlspecialchars($op['cliente_nome']) ?> (<?= htmlspecialchars($op['documento']) ?>)" disabled>
                </div>
                <div class="form-item">
                    <label for="identificador" style="color: white;">Identificador da operação:</label>
                    <input type="text" name="identificador" id="identificador" value="<?= htmlspecialchars($op['identificador']) ?>" required>
                </div>
            </div>

            <!-- Indexador e Periodicidade -->
            <div class="form-row">
                <div class="form-item">
                    <label for="indexador" style="color: white;">Indexador:</label>
                    <select name="indexador" id="indexador" required>
                        <option value="SELIC" <?= $op['indexador'] === 'SELIC' ? 'selected' : '' ?>>SELIC</option>
                        <option value="CDI (CETIP) Diário" <?= $op['indexador'] === 'CDI (CETIP) Diário' ? 'selected' : '' ?>>CDI (CETIP) Diário</option>
                        <option value="IPCA" <?= $op['indexador'] === 'IPCA' ? 'selected' : '' ?>>IPCA</option>
                        <option value="INPC" <?= $op['indexador'] === 'INPC' ? 'selected' : '' ?>>INPC</option>
                    </select>
                </div>
                <div class="form-item">
                    <label for="periodicidade" style="color: white;">Periodicidade:</label>
                    <select name="periodicidade" id="periodicidade" required>
                        <option value="Mensal" <?= $op['periodicidade'] === 'Mensal' ? 'selected' : '' ?>>Mensal</option>
                        <option value="Trimestral" <?= $op['periodicidade'] === 'Trimestral' ? 'selected' : '' ?>>Trimestral</option>
                        <option value="Semestral" <?= $op['periodicidade'] === 'Semestral' ? 'selected' : '' ?>>Semestral</option>
                        <option value="Anual" <?= $op['periodicidade'] === 'Anual' ? 'selected' : '' ?>>Anual</option>
                    </select>
                </div>
            </div>
            <br><br>

            <!-- Atualizar até -->
            <div class="form-row">
                <div class="form-item">
                    <label for="atualizar_ate" style="color: white;">Atualizar até:</label>
                    <input type="date" name="atualizar_ate" id="atualizar_ate" value="<?= $op['atualizar_ate'] ?>" required>
                </div>
                <div class="form-item">
                    <label for="atualizar_dia_debito" style="color: white;">Dia do débito:</label>
                    <input type="number" name="atualizar_dia_debito" id="atualizar_dia_debito" min="1" max="31" value="<?= $op['atualizar_dia_debito'] ?>" required>
                </div>
                <div class="form-item">
                    <label for="atualizar_correcao_monetaria" style="color: white;">Correção monetária (%):</label>
                    <input type="text" step="0.001" name="atualizar_correcao_monetaria" id="atualizar_correcao_monetaria" value="<?= number_format($op['atualizar_correcao_monetaria'], 3, ',', '') ?>" pattern="^[0-9]+([,\.][0-9]+)?$" required>
                </div>
                <div class="form-item">
                    <label for="atualizar_juros_nominais" style="color: white;">Juros nominais (%):</label>
                    <input type="text" step="0.001" name="atualizar_juros_nominais" id="atualizar_juros_nominais" value="<?= number_format($op['atualizar_juros_nominais'], 3, ',', '') ?>" pattern="^[0-9]+([,\.][0-9]+)?$" required>
                </div>
            </div>

            <!-- Alterar taxas em -->
            <div class="form-row">
                <div class="form-item">
                    <label for="alterar_taxas_em" style="color: white;">Alterar taxas em:</label>
                    <input type="date" name="alterar_taxas_em" id="alterar_taxas_em" value="<?= !empty($op['alterar_taxas_em']) ? $op['alterar_taxas_em'] : date('Y-m-d') ?>" required>
                </div>
                <div class="form-item">
                    <label for="alterar_dia_debito" style="color: white;">Dia do Débito:</label>
                    <input type="number" name="alterar_dia_debito" id="alterar_dia_debito" value="<?= $op['alterar_dia_debito'] ?>" min="1" max="31" required>
                </div>
                <div class="form-item">
                    <label for="alterar_correcao_monetaria" style="color: white;">Correção monetária (%):</label>
                    <input type="text" step="0.001" name="alterar_correcao_monetaria" id="alterar_correcao_monetaria" value="<?= number_format($op['alterar_correcao_monetaria'], 3, ',', '') ?>" pattern="^[0-9]+([,\.][0-9]+)?$" required>
                </div>
                <div class="form-item">
                    <label for="alterar_juros_nominais" style="color: white;">Juros nominais (%):</label>
                    <input type="text" step="0.001" name="alterar_juros_nominais" id="alterar_juros_nominais" value="<?= number_format($op['alterar_juros_nominais'], 3, ',', '') ?>" pattern="^[0-9]+([,\.][0-9]+)?$" required>
                </div>
            </div>

            <br><br>

            <!-- Multa e Honorários -->
            <div class="form-row">
                <div class="form-item">
                    <label for="valor_multa" style="color: white;">Valor da multa (R$):</label>
                    <input type="text" step="0.01" name="valor_multa" id="valor_multa" value="<?= number_format($op['valor_multa'], 2, ',', '') ?>" pattern="^[0-9]+([,\.][0-9]+)?$">
                </div>
                <div class="form-item">
                    <label for="valor_honorarios" style="color: white;">Valor dos honorários (R$):</label>
                    <input type="text" step="0.01" name="valor_honorarios" id="valor_honorarios" value="<?= number_format($op['valor_honorarios'], 2, ',', '') ?>" pattern="^[0-9]+([,\.][0-9]+)?$">
                </div>
            </div>

            <!-- Observação -->
            <div class="form-item">
                <label for="observacao" style="color: white;">Observação:</label>
                <textarea name="observacao" id="observacao" placeholder="Opcional"><?= htmlspecialchars($op['observacao']) ?></textarea>
            </div>

            <div class="button-group">
                <button type="submit" class="btn-criar">Salvar alterações</button>
                <button type="button" onclick="document.getElementById('parametrosModal').style.display = 'flex'" class="btn-criar">Parâmetros da Operação</button>
            </div>
        </form>

        <hr class="linha-transparente">
        <br>

        <h1 class="section-title" style="color: white;">Lançamentos</h1>

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
                            <button type="submit" style="background: none; border: none; cursor: pointer;">
                                <i class='bx bx-save' style="font-size: 20px; color: #333; transition: 0.3s;"
                                    onmouseover="this.style.color='#AEF0FF'"
                                    onmouseout="this.style.color='#333'"></i>
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
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="lancamento_id" value="<?= $l['id'] ?>">
                                    <button type="submit" name="excluir_lancamento" style="background: none; border: none; cursor: pointer;">
                                        <i class='bx bx-trash' style="font-size: 20px; color: #333; transition: 0.3s;"
                                            onmouseover="this.style.color='red'"
                                            onmouseout="this.style.color='#333'"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h1 class="section-title" style="color: white;">Extrato do Cálculo</h1>

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
                    <th>Dias corridos</th>
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
                        <td><?= htmlspecialchars($linha['dias_corridos'] ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <br>
    </div>

    <!-- Modal de edição -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3>Editar Lançamento</h3>
            <form id="editForm" method="POST">
                <input type="hidden" name="editar_lancamento" value="1">
                <input type="hidden" name="lancamento_id" id="editLancamentoId">

                <div style="margin-bottom: 15px;">
                    <label>Data</label>
                    <input type="date" name="data" id="editData" required style="width: 100%; padding: 8px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label>Descrição</label>
                    <input type="text" name="descricao" id="editDescricao" required style="width: 100%; padding: 8px;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label>Valor</label>
                    <input type="text" name="valor" id="editValor" placeholder="0,00" required
                        style="width: 100%; padding: 8px;" oninput="formatarValor(this)">
                </div>

                <div style="margin-bottom: 15px;">
                    <label>Tipo</label>
                    <select name="tipo" id="editTipo" required style="width: 100%; padding: 8px;">
                        <option value="debito">Débito</option>
                        <option value="credito">Crédito</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" onclick="document.getElementById('editModal').style.display = 'none'"
                        class="modal-button modal-cancel">Cancelar</button>
                    <button type="submit" class="modal-button modal-save">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Parâmetros da Operação -->
    <div id="parametrosModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <h3>Parâmetros da operação</h3>

            <div style="margin-bottom: 5px;">
                <label><strong>Divisor de Juros</strong></label><br>
                <input type="radio" id="ano360" name="divisor_juros" value="360" <?= ($op['divisor_juros'] ?? 365) == 360 ? 'checked' : '' ?>>
                <label for="ano360">Ano com 360 dias</label><br>
                <input type="radio" id="ano365" name="divisor_juros" value="365" <?= ($op['divisor_juros'] ?? 365) == 365 ? 'checked' : '' ?>>
                <label for="ano365">Ano com 365 dias</label>
            </div>

            <div style="margin-bottom: 5px;">
                <label><strong>Tipo de Juros</strong></label><br>
                <input type="radio" id="juros_simples" name="tipo_juros" value="simples" <?= ($op['tipo_juros'] ?? 'composto') == 'simples' ? 'checked' : '' ?>>
                <label for="juros_simples">Simples</label><br>
                <input type="radio" id="juros_composto" name="tipo_juros" value="composto" <?= ($op['tipo_juros'] ?? 'composto') == 'composto' ? 'checked' : '' ?>>
                <label for="juros_composto">Composto</label>
            </div>

            <div style="margin-bottom: 5px;">
                <label><strong>Tipo de Lançamento</strong></label><br>
                <input type="radio" id="moeda_original" name="tipo_lancamento" value="original" <?= ($op['tipo_lancamento'] ?? 'reais') == 'original' ? 'checked' : '' ?>>
                <label for="moeda_original">Em moeda original</label><br>
                <input type="radio" id="moeda_reais" name="tipo_lancamento" value="reais" <?= ($op['tipo_lancamento'] ?? 'reais') == 'reais' ? 'checked' : '' ?>>
                <label for="moeda_reais">Em reais</label>
            </div>

            <div style="margin-bottom: 5px;">
                <label><strong>Modo de Operação</strong></label><br>
                <input type="radio" id="modo_outros" name="modo_operacao" value="outros" <?= ($op['modo_operacao'] ?? 'credito_rural') == 'outros' ? 'checked' : '' ?>>
                <label for="modo_outros">Outros</label><br>
                <input type="radio" id="modo_credito_rural" name="modo_operacao" value="credito_rural" <?= ($op['modo_operacao'] ?? 'credito_rural') == 'credito_rural' ? 'checked' : '' ?>>
                <label for="modo_credito_rural">Crédito Rural</label>
            </div>

            <div style="margin-bottom: 5px;">
                <label><strong>Resolução 2.471?</strong></label><br>
                <input type="radio" id="resolucao_sim" name="resolucao_2471" value="1" <?= ($op['resolucao_2471'] ?? 0) == 1 ? 'checked' : '' ?>>
                <label for="resolucao_sim">Sim</label><br>
                <input type="radio" id="resolucao_nao" name="resolucao_2471" value="0" <?= ($op['resolucao_2471'] ?? 0) == 0 ? 'checked' : '' ?>>
                <label for="resolucao_nao">Não</label>
            </div>

            <div style="margin-bottom: 5px;">
                <label><strong>Indexadores mensais</strong></label><br>
                <input type="radio" id="indexador_mes_atual" name="indexador_mensal" value="mes_atual" <?= ($op['indexador_mensal'] ?? 'mes_anterior') == 'mes_atual' ? 'checked' : '' ?>>
                <label for="indexador_mes_atual">Utilizar próprio mês</label><br>
                <input type="radio" id="indexador_mes_anterior" name="indexador_mensal" value="mes_anterior" <?= ($op['indexador_mensal'] ?? 'mes_anterior') == 'mes_anterior' ? 'checked' : '' ?>>
                <label for="indexador_mes_anterior">Utilizar mês anterior</label>
            </div>

            <div style="margin-bottom: 5px;">
                <label><strong>Não utilizar valores negativos (deflação)?</strong></label><br>
                <input type="radio" id="valores_negativos_sim" name="valores_negativos" value="1" <?= ($op['valores_negativos'] ?? 0) == 1 ? 'checked' : '' ?>>
                <label for="valores_negativos_sim">Sim</label><br>
                <input type="radio" id="valores_negativos_nao" name="valores_negativos" value="0" <?= ($op['valores_negativos'] ?? 0) == 0 ? 'checked' : '' ?>>
                <label for="valores_negativos_nao">Não</label>
            </div>

            <div style="margin-bottom: 5px;">
                <label><strong>Corrigir apenas os saldos devedores (Conta Corrente)?</strong></label><br>
                <input type="radio" id="corrigir_saldos_sim" name="corrigir_saldos" value="1" <?= ($op['corrigir_saldos'] ?? 0) == 1 ? 'checked' : '' ?>>
                <label for="corrigir_saldos_sim">Sim</label><br>
                <input type="radio" id="corrigir_saldos_nao" name="corrigir_saldos" value="0" <?= ($op['corrigir_saldos'] ?? 0) == 0 ? 'checked' : '' ?>>
                <label for="corrigir_saldos_nao">Não</label>
            </div>

            <div class="modal-actions">
                <button type="button" onclick="document.getElementById('parametrosModal').style.display = 'none'" class="modal-button modal-cancel">Cancelar</button>
                <button type="button" onclick="salvarParametros()" class="modal-button modal-save">Salvar</button>
            </div>
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
            if (event.target === document.getElementById('parametrosModal')) {
                document.getElementById('parametrosModal').style.display = 'none';
            }
        });

        // Auto-fechar mensagens de alerta
        setTimeout(function() {
            const msg = document.getElementById('alerta-msg');
            if (msg) {
                msg.style.transition = 'opacity 1s';
                msg.style.opacity = 0;
                setTimeout(() => msg.remove(), 1000);
            }
        }, 3000);

        // Função para salvar parâmetros
        function salvarParametros() {
            const formData = new FormData();
            formData.append('id', <?= $id ?>);
            formData.append('divisor_juros', document.querySelector('input[name="divisor_juros"]:checked').value);
            formData.append('tipo_juros', document.querySelector('input[name="tipo_juros"]:checked').value);
            formData.append('tipo_lancamento', document.querySelector('input[name="tipo_lancamento"]:checked').value);
            formData.append('modo_operacao', document.querySelector('input[name="modo_operacao"]:checked').value);
            formData.append('resolucao_2471', document.querySelector('input[name="resolucao_2471"]:checked').value);
            formData.append('indexador_mensal', document.querySelector('input[name="indexador_mensal"]:checked').value);
            formData.append('valores_negativos', document.querySelector('input[name="valores_negativos"]:checked').value);
            formData.append('corrigir_saldos', document.querySelector('input[name="corrigir_saldos"]:checked').value);

            fetch('../../includes/salvar_parametros.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Parâmetros salvos com sucesso!');
                    document.getElementById('parametrosModal').style.display = 'none';
                    location.reload();
                } else {
                    alert('Erro ao salvar parâmetros: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao salvar parâmetros');
            });
        }

        // Validação do primeiro débito
        document.getElementById('formNovoLancamento').addEventListener('submit', function(e) {
            const tipo = this.querySelector('select[name="tipo"]').value;
            const existeDebito = <?= existeDebitoNaOperacao($mysqli, $id) ? 'true' : 'false' ?>;
            
            if (!existeDebito && tipo !== 'debito') {
                alert('ATENÇÃO: O primeiro lançamento deve ser um DÉBITO');
                e.preventDefault();
                return false;
            }
            
            // Validação adicional para valor positivo
            const valorInput = this.querySelector('input[name="valor"]');
            const valor = parseFloat(valorInput.value.replace(',', '.'));
            
            if (isNaN(valor) || valor <= 0) {
                alert('O valor deve ser um número positivo!');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
