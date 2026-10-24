<?php
session_start();

// Credenciales oficiales sincronizadas con dbo.Usuarios (UsuarioId = 8 - PAGINA WEB)
$usuarios_permitidos = [
    "supplexcorp@gmail.com",
    "pagina web",
    "admin"
];
$password_correcta = "Supplex2026";
$id_usuario_auditoria = 8;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = mb_strtolower(trim($_POST['usuario'] ?? ''), 'UTF-8');
    $pass = trim($_POST['password'] ?? '');

    if (in_array($user, $usuarios_permitidos, true) && $pass === $password_correcta) {
        $_SESSION['admin_logeado'] = true;
        $_SESSION['admin_usuario'] = "supplexcorp@gmail.com";
        $_SESSION['admin_nombre'] = "PAGINA WEB";
        $_SESSION['admin_usuario_id'] = $id_usuario_auditoria;
        
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