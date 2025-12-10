<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? intval($input['id']) : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid id']);
    exit();
}

// DB config
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "login_db";

$conn = new mysqli($servername, $username_db, $password_db, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Verificar propiedad
$stmt = $conn->prepare("SELECT id FROM transcriptions WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'No permitido o no encontrado']);
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

$del = $conn->prepare("DELETE FROM transcriptions WHERE id = ? AND user_id = ?");
$del->bind_param('ii', $id, $user_id);
if ($del->execute()) {
    echo json_encode(['success' => true, 'deleted_id' => $id]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Delete failed']);
}

$del->close();
$conn->close();

?>
