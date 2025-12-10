<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

// Config DB (match login.php)
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

$select_field = 'title';
// Comprobar si existe la columna 'title', si no usar 'filename' si existe
$check_col = $conn->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='". $conn->real_escape_string($dbname) ."' AND TABLE_NAME='transcriptions' AND COLUMN_NAME='title'");
$hasTitle = false;
if ($check_col) {
    $rowc = $check_col->fetch_assoc();
    $hasTitle = intval($rowc['cnt']) > 0;
}
if (!$hasTitle) {
    $check_filename = $conn->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='". $conn->real_escape_string($dbname) ."' AND TABLE_NAME='transcriptions' AND COLUMN_NAME='filename'");
    if ($check_filename) {
        $rf = $check_filename->fetch_assoc();
        if (intval($rf['cnt']) > 0) {
            $select_field = 'filename AS title';
        } else {
            $select_field = "NULL AS title";
        }
    }
}

$query = "SELECT id, $select_field, transcript, summary, created_at FROM transcriptions WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$rows = [];
while ($r = $result->fetch_assoc()) {
    $rows[] = $r;
}

echo json_encode(['success' => true, 'records' => $rows]);

$stmt->close();
$conn->close();

?>
