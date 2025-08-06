<?php
require_once __DIR__ . '/auto_check.php';
require_once __DIR__ . '/connect_app.php';

header('Content-Type: application/json');

$data = $_POST;

try {
    // Converter valores para os tipos corretos
    $divisor_juros = isset($data['divisor_juros']) ? (int) $data['divisor_juros'] : 360;
    $tipo_juros = $data['tipo_juros'] ?? 'composto';
    $tipo_lancamento = $data['tipo_lancamento'] ?? 'original';
    $modo_operacao = $data['modo_operacao'] ?? 'outros';
    $resolucao_2471 = isset($data['resolucao_2471']) ? (int) $data['resolucao_2471'] : 0;
    $indexador_mensal = $data['indexador_mensal'] ?? 'mes_atual';
    $valores_negativos = isset($data['valores_negativos']) ? (int) $data['valores_negativos'] : 0;
    $corrigir_saldos = isset($data['corrigir_saldos']) ? (int) $data['corrigir_saldos'] : 0;
    $id = isset($data['id']) ? (int) $data['id'] : 0;


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
