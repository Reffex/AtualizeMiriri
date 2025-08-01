<?php
require_once __DIR__ . '/auto_check.php';
require_once __DIR__ . '/connect_app.php';

header('Content-Type: application/json');

$data = $_POST;

try {
    // Converter valores para os tipos corretos
    $divisor_juros = (int) $data['divisor_juros'];
    $tipo_juros = $data['tipo_juros'];
    $tipo_lancamento = $data['tipo_lancamento'];
    $modo_operacao = $data['modo_operacao'];
    $resolucao_2471 = (int) $data['resolucao_2471'];
    $indexador_mensal = $data['indexador_mensal'];
    $valores_negativos = (int) $data['valores_negativos'];
    $corrigir_saldos = (int) $data['corrigir_saldos'];
    $id = (int) $data['id'];

    $stmt = $mysqli->prepare("UPDATE operacoes SET 
        divisor_juros = ?,
        tipo_juros = ?,
        tipo_lancamento = ?,
        modo_operacao = ?,
        resolucao_2471 = ?,
        indexador_mensal = ?,
        valores_negativos = ?,
        corrigir_saldos = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "isssisiii",
        $divisor_juros,
        $tipo_juros,
        $tipo_lancamento,
        $modo_operacao,
        $resolucao_2471,
        $indexador_mensal,
        $valores_negativos,
        $corrigir_saldos,
        $id
    );

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
