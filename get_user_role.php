<?php
session_start();

// Validar autenticación
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'role' => $_SESSION['role']]);
?>
