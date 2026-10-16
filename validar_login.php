<?php
session_start(); // Iniciamos el sistema de sesiones de PHP

// Acá definís tu usuario y contraseña maestra (podés cambiarlos por los que quieras)
$usuario_correcto = "admin";
$password_correcta = "supplex123";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['usuario']);
    $pass = trim($_POST['password']);

    if ($user === $usuario_correcto && $pass === $password_correcta) {
        // Credenciales correctas: Creamos la sesión VIP
        $_SESSION['admin_logeado'] = true;
        $_SESSION['admin_usuario'] = $user;
        
        // Te redirige automáticamente al panel
        header("Location: panel.php");
        exit();
    } else {
        // Falló: Te avisa y te devuelve al login
        echo "<script>
                alert('⚠️ Usuario o contraseña incorrectos'); 
                window.location.href='login.php';
              </script>";
    }
}
?>