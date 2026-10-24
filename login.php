<?php
session_start();
if (isset($_SESSION['admin_logeado']) && $_SESSION['admin_logeado'] === true) {
    header("Location: panel.php");
    exit();
}
$error = isset($_GET['error']) ? "⚠️ Usuario o contraseña incorrectos." : "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Restringido | Supplex Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            background-color: var(--color-gris-claro); 
            margin: 0;
        }
        .login-caja { 
            background: var(--color-claro); 
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: var(--sombra-suave); 
            width: 100%; 
            max-width: 400px; 
            text-align: center; 
            border: 1px solid var(--color-borde); 
        }
        .login-logo { 
            font-size: 2rem; 
            font-weight: 900; 
            color: var(--color-oscuro); 
            margin-bottom: 10px; 
            letter-spacing: 1px; 
        }
        .login-logo span { color: var(--color-primario); background: var(--color-oscuro); padding: 2px 8px; border-radius: 4px; font-size: 1.2rem; }
        .login-sub { font-size: 0.85rem; color: var(--color-gris-medio); margin-bottom: 25px; }
        .login-error { background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 6px; font-size: 0.85rem; font-weight: 700; margin-bottom: 20px; }
        .login-campo { margin-bottom: 20px; text-align: left; }
        .login-campo label { display: block; font-weight: 800; font-size: 0.85rem; margin-bottom: 8px; color: var(--color-texto); }
        .login-campo input { width: 100%; padding: 12px; border: 1px solid var(--color-borde); border-radius: 6px; font-family: inherit; font-size: 1rem; background: #fafafa; }
        .login-campo input:focus { outline: none; border-color: var(--color-oscuro); background: #fff; }
        .login-boton { width: 100%; padding: 14px; background: var(--color-oscuro); color: var(--color-claro); border: none; border-radius: 6px; font-weight: 900; font-size: 1rem; cursor: pointer; text-transform: uppercase; transition: 0.2s; margin-top: 10px; }
        .login-boton:hover { background: var(--color-primario); color: var(--color-oscuro); }
        .login-volver { display: inline-block; margin-top: 20px; font-size: 0.85rem; color: var(--color-gris-medio); font-weight: 600; }
        .login-volver:hover { color: var(--color-oscuro); }
    </style>
</head>
<body>

    <div class="login-caja">
        <div class="login-logo">★ SUPPLEX <span>ADMIN</span></div>
        <p class="login-sub">Panel de gestión de catálogo, precios e imágenes</p>

        <?php if ($error !== ""): ?>
            <div class="login-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form action="validar_login.php" method="POST">
            <div class="login-campo">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" placeholder="Ingresá tu usuario" required autocomplete="off">
            </div>
            
            <div class="login-campo">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="login-boton">Entrar al Panel</button>
        </form>

        <a href="index.html" class="login-volver">← Volver a la tienda</a>
    </div>

</body>
</html>