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

// Recibir ID del alumno
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

if (!isset($data['student_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de alumno requerido']);
    exit();
}

$student_id = intval($data['student_id']);

// Obtener transcripciones del alumno
$stmt = $conn->prepare("SELECT id, title, transcript, summary, created_at FROM transcriptions WHERE user_id = ? ORDER BY created_at DESC");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en la consulta']);
    exit();
}

$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$transcriptions = [];

while ($row = $result->fetch_assoc()) {
    $transcriptions[] = $row;
}

$stmt->close();
$conn->close();

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'records' => $transcriptions]);
?>
