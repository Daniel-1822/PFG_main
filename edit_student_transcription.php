<?php
session_start();

// Validar autenticación y rol
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'docente') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "login_db";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

// Recibir datos
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

if (!isset($data['id']) || !isset($data['field']) || !isset($data['value'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos requeridos']);
    exit();
}

$id = intval($data['id']);
$field = $data['field'];
$value = $data['value'];

// Validar campo
if (!in_array($field, ['title', 'transcript', 'summary'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Campo no válido']);
    exit();
}

// Actualizar campo
$stmt = $conn->prepare("UPDATE transcriptions SET $field = ? WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en la consulta']);
    exit();
}

$stmt->bind_param("si", $value, $id);

if ($stmt->execute()) {
    // Obtener registro actualizado
    $select_stmt = $conn->prepare("SELECT id, title, transcript, summary, created_at FROM transcriptions WHERE id = ?");
    $select_stmt->bind_param("i", $id);
    $select_stmt->execute();
    $result = $select_stmt->get_result();
    $record = $result->fetch_assoc();
    $select_stmt->close();
    
    $stmt->close();
    $conn->close();
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'record' => $record]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
    exit();
}
?>
