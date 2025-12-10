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
$title = isset($input['title']) ? trim($input['title']) : '';

if ($id <= 0 || $title === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
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

// Asegurarse de que existe la columna 'title' (compatibilidad)
$check_col = $conn->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='". $conn->real_escape_string($dbname) ."' AND TABLE_NAME='transcriptions' AND COLUMN_NAME='title'");
$hasTitle = false;
if ($check_col) {
    $rowc = $check_col->fetch_assoc();
    $hasTitle = intval($rowc['cnt']) > 0;
}
if (!$hasTitle) {
    $conn->query("ALTER TABLE transcriptions ADD COLUMN title VARCHAR(255) DEFAULT NULL");
    $conn->query("UPDATE transcriptions SET title = filename WHERE (title IS NULL OR title = '')");
}

$upd = $conn->prepare("UPDATE transcriptions SET title = ? WHERE id = ? AND user_id = ?");
$upd->bind_param('sii', $title, $id, $user_id);
if ($upd->execute()) {
    // devolver registro actualizado
    $res2 = $conn->prepare("SELECT id, title, transcript, summary, created_at FROM transcriptions WHERE id = ?");
    $res2->bind_param('i', $id);
    $res2->execute();
    $r = $res2->get_result()->fetch_assoc();
    echo json_encode(['success' => true, 'record' => $r]);
    $res2->close();
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Update failed']);
}

$upd->close();
$conn->close();

?>
