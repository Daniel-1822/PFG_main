<?php
// Configuración de la base de datos
$servername = "localhost";
$username_db = "root";
$password_db = "";
$dbname = "login_db";

// Crear conexión
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Procesar formulario si se envió
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validaciones básicas
    $errors = [];
    if (empty($username) || strlen($username) < 3) {
        $errors[] = "El usuario debe tener al menos 3 caracteres.";
    }
    if (empty($first_name) || strlen($first_name) < 2) {
        $errors[] = "El nombre es obligatorio y debe tener al menos 2 caracteres.";
    }
    if (empty($last_name) || strlen($last_name) < 2) {
        $errors[] = "Los apellidos son obligatorios y deben tener al menos 2 caracteres.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email inválido.";
    }
    if (!in_array($role, ['docente', 'alumno'])) {
        $errors[] = "Rol inválido.";
    }
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "La contraseña debe tener al menos 6 caracteres.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Las contraseñas no coinciden.";
    }

    // Verificar si el usuario ya existe
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $errors[] = "El usuario ya existe.";
    }
    $check_stmt->close();

    if (empty($errors)) {
        // Hashear contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insertar usuario
        $stmt = $conn->prepare("INSERT INTO users (username, first_name, last_name, email, role, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $username, $first_name, $last_name, $email, $role, $hashed_password);

        if ($stmt->execute()) {
            // Redirigir a login con mensaje de éxito
            header("Location: index.php?success=1");
            exit();
        } else {
            $errors[] = "Error al registrar: " . $conn->error;
        }
        $stmt->close();
    }

    // Mostrar errores en la página de registro
    if (!empty($errors)) {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error en Registro</title>
            <link href="https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&display=swap" rel="stylesheet">
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Ubuntu', sans-serif; }
                body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #1a1a1a; }
                .error-container { background: #2c2c2c; padding: 2rem; border-radius: 26px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3); width: 100%; max-width: 400px; text-align: center; }
                .error-container h2 { color: #e0e0e0; font-size: 1.8rem; font-weight: 700; margin-bottom: 1.5rem; }
                .error-message { background: #ff4444; color: white; padding: 1rem; border-radius: 26px; margin-bottom: 1.5rem; font-size: 0.9rem; }
                .back-btn { background: #555; color: #e0e0e0; padding: 0.8rem; border: none; border-radius: 26px; width: 100%; cursor: pointer; font-size: 1rem; }
                .back-btn:hover { background: #666; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h2>Error en el Registro</h2>
                <?php foreach ($errors as $error) { ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php } ?>
                <button class="back-btn" onclick="window.location.href='index.php'">Volver</button>
            </div>
        </body>
        </html>
        <?php
    }
}

$conn->close();
?>