<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Restringido | Supplex Admin</title>
    <!-- Vinculamos tu CSS global para usar las mismas variables de color -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Estilos exclusivos para la pantalla de Login */
        body { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            background-color: var(--color-fondo-gris); 
            margin: 0;
        }
        .login-caja { 
            background: var(--color-claro); 
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
            width: 100%; 
            max-width: 400px; 
            text-align: center; 
            border: 1px solid var(--color-borde); 
        }
        .login-logo { 
            font-size: 2rem; 
            font-weight: 900; 
            color: var(--color-oscuro); 
            margin-bottom: 30px; 
            letter-spacing: 1px; 
        }
        .login-logo span { color: var(--color-primario); }
        .login-campo { 
            margin-bottom: 20px; 
            text-align: left; 
        }
        .login-campo label { 
            display: block; 
            font-weight: 800; 
            font-size: 0.85rem; 
            margin-bottom: 8px; 
            color: var(--color-texto); 
        }
        .login-campo input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid var(--color-borde); 
            border-radius: 6px; 
            font-family: inherit; 
            font-size: 1rem; 
            background: #fafafa;
        }
        .login-campo input:focus { 
            outline: none; 
            border-color: var(--color-oscuro); 
        }
        .login-boton { 
            width: 100%; 
            padding: 14px; 
            background: var(--color-oscuro); 
            color: var(--color-claro); 
            border: none; 
            border-radius: 6px; 
            font-weight: 900; 
            font-size: 1rem; 
            cursor: pointer; 
            text-transform: uppercase; 
            transition: 0.2s; 
            margin-top: 10px;
        }
        .login-boton:hover { 
            background: var(--color-primario); 
            color: var(--color-oscuro); 
        }
    </style>
</head>
<body>

    <div class="login-caja">
        <div class="login-logo">★ SUPPLEX <span>ADMIN</span></div>
        
        <!-- Este formulario enviará los datos a validar_login.php -->
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
    </div>

</body>
</html>