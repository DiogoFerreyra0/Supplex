<?php
session_start();

// Cambiá estos valores por el usuario y contraseña que vos quieras usar
$usuario_correcto = "admin";
$password_correcta = "supplex123";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['usuario'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if ($user === $usuario_correcto && $pass === $password_correcta) {
        $_SESSION['admin_logeado'] = true;
        $_SESSION['admin_usuario'] = $user;
        header("Location: panel.php");
        exit();
    } else {
        header("Location: login.php?error=1");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>