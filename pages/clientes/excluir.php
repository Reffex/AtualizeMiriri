<?php
require_once '../../includes/auto_check.php';
require_once '../../includes/connect_app.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    echo "ID não informado.";
    exit;
}

$stmt = $mysqli->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Cliente não encontrado.";
    exit;
}

$stmt = $mysqli->prepare("DELETE FROM clientes WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    header("Location: listar.php?sucesso=3");
    exit;
} else {
    echo "Erro ao excluir cliente: " . $stmt->error;
}

$res = $mysqli->query("SELECT COUNT(*) AS total FROM operacoes WHERE cliente_id = $id");
$temOperacoes = $res->fetch_assoc()['total'];

if ($temOperacoes > 0) {
    header("Location: listar.php?erro=cliente_usado");
    exit;
}
