<?php
require_once '../includes/connect_app.php';

$indexador = $_GET['indexador'] ?? null;
$data = $_GET['data'] ?? null;

if (!$indexador || !$data) {
    http_response_code(400);
    echo json_encode(['erro' => 'Parâmetros ausentes']);
    exit;
}

$stmt = $mysqli->prepare("SELECT valor FROM indices WHERE nome = ? AND data_referencia <= ? ORDER BY data_referencia DESC LIMIT 1");
$stmt->bind_param("ss", $indexador, $data);
$stmt->execute();

$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'indexador' => $indexador,
        'data' => $data,
        'valor' => floatval($row['valor'])
    ]);
} else {
    echo json_encode(['erro' => 'Índice não encontrado']);
}
