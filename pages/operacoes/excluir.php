<?php
    require_once '../../includes/auto_check.php';
    require_once '../../includes/connect_app.php';

    $id = $_GET['id'] ?? null;

    if (!$id) {
        echo "ID não informado.";
        exit;
    }

    $mysqli->query("DELETE FROM lancamentos WHERE operacao_id = $id");

    $stmt = $mysqli->prepare("DELETE FROM operacoes WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: listar.php?sucesso=3");
        exit;
    } else {
        echo "Erro ao excluir operação: " . $stmt->error;
    }
