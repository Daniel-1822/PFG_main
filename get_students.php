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

// Obtener alumnos
$stmt = $conn->prepare("SELECT id, username, first_name, last_name FROM users WHERE role = 'alumno' ORDER BY first_name ASC");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en la consulta']);
    exit();
}

$stmt->execute();
$result = $stmt->get_result();
$students = [];

while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

$stmt->close();
$conn->close();

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => true, 'students' => $students]);
?>
