<?php
session_start();

// EL GUARDIÁN: Si NO estás logeado, te expulsa al login inmediatamente
if (!isset($_SESSION['admin_logeado']) || $_SESSION['admin_logeado'] !== true) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración | Supplex</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .panel-header { display: flex; justify-content: space-between; align-items: center; background: #111; color: #fff; padding: 15px 30px; border-radius: 8px; margin-bottom: 20px; }
        .panel-header h2 { margin: 0; font-weight: 900; }
        .panel-header h2 span { color: #a3e635; }
        .btn-salir { background: #ff3b30; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; transition: 0.3s;}
        .btn-salir:hover { background: #cc2e26; }
        .panel-contenido { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); min-height: 400px;}
    </style>
</head>
<body>

    <div class="panel-header">
        <h2>★ SUPPLEX <span>ADMIN</span></h2>
        <a href="logout.php" class="btn-salir">Cerrar Sesión</a>
    </div>
    
    <div class="panel-contenido">
        <h3>¡Bienvenido al administrador, <?php echo $_SESSION['admin_usuario']; ?>!</h3>
        <p>Acá es donde próximamente vamos a poner el formulario para cargar, editar y borrar tus productos de la base de datos.</p>
    </div>

</body>
</html>