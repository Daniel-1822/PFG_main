<?php
session_start();

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
    $password = $_POST['password'];

    // Validaciones básicas
    $errors = [];
    if (empty($username)) {
        $errors[] = "El usuario es obligatorio.";
    }
    if (empty($password)) {
        $errors[] = "La contraseña es obligatoria.";
    }

    if (empty($errors)) {
        // Verificar si el usuario existe y la contraseña es correcta
        $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                // Iniciar sesión
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $row['role'];
                
                // Redirigir según el rol (usar la build de React)
                if ($row['role'] === 'docente') {
                    header("Location: /tfg/index.html");
                } else {
                    header("Location: /tfg/index.html");
                }
                exit();
            } else {
                $errors[] = "Contraseña incorrecta.";
            }
        } else {
            $errors[] = "Usuario no encontrado.";
        }
        $stmt->close();
    }

    // Mostrar errores en caso de fallo
    if (!empty($errors)) {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error en Login</title>
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