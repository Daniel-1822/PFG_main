<?php
session_start();

header('Content-Type: application/json');
// CORS - support local origins
$allowed = ['http://localhost:3000', 'http://localhost'];
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $allowed)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: http://localhost');
}
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
$title = isset($input['title']) ? trim($input['title']) : null;
$transcript = isset($input['transcript']) ? trim($input['transcript']) : null;
$summary = isset($input['summary']) ? trim($input['summary']) : null;

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

// Fetch record owner
$stmt = $conn->prepare("SELECT user_id FROM transcriptions WHERE id = ?");
$stmt->bind_param('i', $id);
stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    $stmt->close();
    $conn->close();
    exit();
}
$row = $res->fetch_assoc();
$owner_id = intval($row['user_id']);
$stmt->close();

$current_user = intval($_SESSION['user_id']);
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Permission logic:
// - docentes ('docente') can edit title, transcript, summary for any record
// - regular users can only edit their own title
if ($user_role !== 'docente') {
    // regular user
    if ($owner_id !== $current_user) {
        http_response_code(403);
        echo json_encode(['error' => 'No permitido']);
        $conn->close();
        exit();
    }
    // disallow updating transcript or summary
    if ($transcript !== null || $summary !== null) {
        http_response_code(403);
        echo json_encode(['error' => 'Sólo docentes pueden editar transcripción o resumen']);
        $conn->close();
        exit();
    }
}

// Ensure title column exists
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

// Build dynamic update
$fields = [];
$params = [];
$types = '';
if ($title !== null) { $fields[] = 'title = ?'; $params[] = $title; $types .= 's'; }
if ($transcript !== null) { $fields[] = 'transcript = ?'; $params[] = $transcript; $types .= 's'; }
if ($summary !== null) { $fields[] = 'summary = ?'; $params[] = $summary; $types .= 's'; }

if (count($fields) === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'No fields to update']);
    $conn->close();
    exit();
}

$setClause = implode(', ', $fields);
// If user is docente allow updating any; otherwise ensure WHERE includes user_id
if ($user_role === 'docente') {
    $sql = "UPDATE transcriptions SET $setClause WHERE id = ?";
} else {
    $sql = "UPDATE transcriptions SET $setClause WHERE id = ? AND user_id = ?";
}

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Prepare failed']);
    $conn->close();
    exit();
}

// bind params
// append id and possibly user_id
foreach ($params as $p) {
    // binding done later
}

if ($user_role === 'docente') {
    $types .= 'i';
    $params[] = $id;
} else {
    $types .= 'ii';
    $params[] = $id;
    $params[] = $current_user;
}

// Use call_user_func_array for dynamic binding
$bind_names[] = $types;
for ($i=0; $i<count($params); $i++) {
    $bind_name = 'bind' . $i;
    $$bind_name = $params[$i];
    $bind_names[] = &$$bind_name;
}

call_user_func_array(array($stmt, 'bind_param'), $bind_names);

if ($stmt->execute()) {
    // return updated record
    $res2 = $conn->prepare("SELECT id, title, transcript, summary, created_at FROM transcriptions WHERE id = ?");
    $res2->bind_param('i', $id);
    $res2->execute();
    $r = $res2->get_result()->fetch_assoc();
    echo json_encode(['success' => true, 'record' => $r]);
    $res2->close();
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Update failed', 'detail' => $stmt->error]);
}

$stmt->close();
$conn->close();

?>