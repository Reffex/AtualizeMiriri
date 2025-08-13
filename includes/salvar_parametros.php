<?php
require_once __DIR__ . '/auto_check.php';
require_once __DIR__ . '/connect_app.php';

header('Content-Type: application/json');

$data = $_POST;

try {
    $tipo_juros = $data['tipo_juros'] ?? 'composto';
    $id = isset($data['id']) ? (int) $data['id'] : 0;

    $stmt = $mysqli->prepare("UPDATE operacoes SET tipo_juros = ? WHERE id = ?");
    $stmt->bind_param("si", $tipo_juros, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $stmt->error,
            'query' => $stmt->error_list,
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
