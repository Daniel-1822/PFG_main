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

// Comprobar sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$transcription = isset($input['transcription']) ? $input['transcription'] : null;
$summary = isset($input['summary']) ? $input['summary'] : null;
$filename = isset($input['filename']) ? $input['filename'] : null;

if (!$transcription) {
    http_response_code(400);
    echo json_encode(['error' => 'Transcription required']);
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

// Crear tabla si no existe
$create_sql = "CREATE TABLE IF NOT EXISTS transcriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) DEFAULT NULL,
    transcript LONGTEXT,
    summary LONGTEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($create_sql);

// Si la tabla existía con columna 'filename', intentar migrar a 'title'
$check_col = $conn->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='". $conn->real_escape_string($dbname) ."' AND TABLE_NAME='transcriptions' AND COLUMN_NAME='title'");
$hasTitle = false;
if ($check_col) {
    $rowc = $check_col->fetch_assoc();
    $hasTitle = intval($rowc['cnt']) > 0;
}
if (!$hasTitle) {
    // Añadir columna title
    $conn->query("ALTER TABLE transcriptions ADD COLUMN title VARCHAR(255) DEFAULT NULL");
    // Si existe columna filename, migrar datos
    $check_filename = $conn->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='". $conn->real_escape_string($dbname) ."' AND TABLE_NAME='transcriptions' AND COLUMN_NAME='filename'");
    if ($check_filename) {
        $rf = $check_filename->fetch_assoc();
        if (intval($rf['cnt']) > 0) {
            $conn->query("UPDATE transcriptions SET title = filename WHERE (title IS NULL OR title = '')");
        }
    }
}
$user_id = intval($_SESSION['user_id']);

// Insertar usando 'title'
$title = $filename; // si se pasó filename lo usamos como title
$stmt = $conn->prepare("INSERT INTO transcriptions (user_id, title, transcript, summary) VALUES (?, ?, ?, ?)");
$stmt->bind_param('isss', $user_id, $title, $transcription, $summary);
if ($stmt->execute()) {
    $insert_id = $stmt->insert_id;
    $res = $conn->query("SELECT id, user_id, title, transcript, summary, created_at FROM transcriptions WHERE id = " . intval($insert_id));
    $row = $res->fetch_assoc();
    echo json_encode(['success' => true, 'record' => $row]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Insert failed']);
}

$stmt->close();
$conn->close();

?>
