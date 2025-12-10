<?php
session_start();

// Destruir la sesión
session_destroy();

// Redirigir al login
header('Location: /tfg/index.php');
exit();
?>
