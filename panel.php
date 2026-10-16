<?php
session_start();

// 1. EL GUARDIÁN: Si NO estás logeado, te expulsa al login
if (!isset($_SESSION['admin_logeado']) || $_SESSION['admin_logeado'] !== true) {
    header("Location: login.php");
    exit();
}

require 'conexion.php';
$mensaje = "";

// 2. LÓGICA PARA AGREGAR UN PRODUCTO NUEVO (CREATE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'crear') {
    $nombre = trim($_POST['nombre']);
    $marca = trim($_POST['marca']);
    $tipo = trim($_POST['tipo']);
    $precio = (float)$_POST['precio'];
    $stock = (int)$_POST['stock'];
    $desc = trim($_POST['descripcion']);

    $stmt = $conn->prepare("INSERT INTO productos (nombre, marca, tipo, descripcion, precio, stock) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssdi", $nombre, $marca, $tipo, $desc, $precio, $stock);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Producto '$nombre' agregado con éxito al catálogo.";
    } else {
        $mensaje = "❌ Error al agregar el producto.";
    }
    $stmt->close();
}

// 3. LÓGICA PARA BORRAR UN PRODUCTO (DELETE)
if (isset($_GET['borrar'])) {
    $id_borrar = (int)$_GET['borrar'];
    $conn->query("DELETE FROM productos WHERE id = $id_borrar");
    header("Location: panel.php?msj=borrado");
    exit();
}

if (isset($_GET['msj']) && $_GET['msj'] == 'borrado') {
    $mensaje = "🗑️ Producto eliminado correctamente.";
}

// 4. OBTENER TODOS LOS PRODUCTOS PARA MOSTRAR EN LA TABLA
$resultado_productos = $conn->query("SELECT * FROM productos ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control | Supplex</title>
    <style>
        :root {
            --primario: #a3e635;
            --oscuro: #111111;
            --gris: #f4f4f4;
            --borde: #dddddd;
            --alerta: #ff3b30;
        }
        body { font-family: 'Inter', system-ui, sans-serif; background: var(--gris); margin: 0; padding: 20px; color: #333; }
        .contenedor { max-width: 1200px; margin: 0 auto; }
        
        /* Encabezado */
        .panel-header { display: flex; justify-content: space-between; align-items: center; background: var(--oscuro); color: #fff; padding: 20px 30px; border-radius: 12px; margin-bottom: 25px; }
        .panel-header h2 { margin: 0; font-weight: 900; letter-spacing: 1px; }
        .panel-header h2 span { color: var(--primario); }
        .header-acciones { display: flex; gap: 15px; align-items: center; }
        .btn-ver-web { color: #fff; text-decoration: none; font-size: 0.9rem; border: 1px solid #555; padding: 8px 15px; border-radius: 6px; transition: 0.2s; }
        .btn-ver-web:hover { background: #fff; color: var(--oscuro); }
        .btn-salir { background: var(--alerta); color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 0.9rem; }
        
        /* Alertas */
        .alerta { background: var(--oscuro); color: var(--primario); padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: bold; border-left: 5px solid var(--primario); }

        /* Grilla Principal */
        .panel-grilla { display: grid; grid-template-columns: 350px 1fr; gap: 25px; align-items: start; }
        .caja { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); border: 1px solid var(--borde); margin-bottom: 25px; }
        .caja h3 { margin-top: 0; font-weight: 900; text-transform: uppercase; border-bottom: 2px solid var(--oscuro); padding-bottom: 10px; margin-bottom: 20px; }

        /* Formulario */
        .campo { margin-bottom: 15px; }
        .campo label { display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 5px; }
        .campo input, .campo select, .campo textarea { width: 100%; padding: 10px; border: 1px solid var(--borde); border-radius: 6px; font-family: inherit; box-sizing: border-box; background: #fafafa; }
        .btn-guardar { width: 100%; padding: 14px; background: var(--oscuro); color: #fff; border: none; border-radius: 6px; font-weight: 900; text-transform: uppercase; cursor: pointer; transition: 0.2s; }
        .btn-guardar:hover { background: var(--primario); color: var(--oscuro); }

        /* Tabla de Productos */
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; }
        th, td { padding: 12px 10px; border-bottom: 1px solid var(--borde); }
        th { background: #fafafa; font-weight: 800; text-transform: uppercase; font-size: 0.75rem; color: #666; }
        .badge-cat { background: #eee; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }
        .precio-tabla { font-weight: 900; color: var(--oscuro); }
        .btn-borrar { color: var(--alerta); text-decoration: none; font-weight: bold; font-size: 0.85rem; padding: 5px 10px; border: 1px solid var(--alerta); border-radius: 4px; transition: 0.2s; }
        .btn-borrar:hover { background: var(--alerta); color: #fff; }

        /* Lista de Excels */
        .lista-excel { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; }
        .item-excel { background: #f9f9f9; border: 1px solid var(--borde); padding: 12px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; font-weight: 600; }
        .btn-descargar { background: #25d366; color: #fff; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }

        @media (max-width: 900px) { .panel-grilla { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="contenedor">
    
    <div class="panel-header">
        <h2>★ SUPPLEX <span>CONTROL</span></h2>
        <div class="header-acciones">
            <a href="index.html" target="_blank" class="btn-ver-web">Ver Tienda ↗</a>
            <a href="logout.php" class="btn-salir">Cerrar Sesión</a>
        </div>
    </div>

    <?php if ($mensaje != ""): ?>
        <div class="alerta"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <div class="panel-grilla">
        
        <!-- COLUMNA IZQUIERDA: FORMULARIO DE CARGA -->
        <div class="caja">
            <h3>➕ Nuevo Producto</h3>
            <form action="panel.php" method="POST">
                <input type="hidden" name="accion" value="crear">
                
                <div class="campo">
                    <label>Nombre del Suplemento:</label>
                    <input type="text" name="nombre" placeholder="Ej: 100% Whey Gold" required>
                </div>

                <div class="campo">
                    <label>Marca:</label>
                    <input type="text" name="marca" placeholder="Ej: Optimum Nutrition" required>
                </div>

                <div class="campo">
                    <label>Categoría (Tipo):</label>
                    <select name="tipo" required>
                        <option value="Proteína">Proteína</option>
                        <option value="Creatina">Creatina</option>
                        <option value="Pre-entreno">Pre-entreno</option>
                        <option value="Aminoácidos">Aminoácidos</option>
                        <option value="Vitaminas">Vitaminas</option>
                        <option value="Combo">Combo</option>
                    </select>
                </div>

                <div class="campo">
                    <label>Precio ($):</label>
                    <input type="number" name="precio" placeholder="Ej: 45000" required min="1">
                </div>

                <div class="campo">
                    <label>Stock disponible:</label>
                    <input type="number" name="stock" value="10" required min="0">
                </div>

                <div class="campo">
                    <label>Descripción para la Vista Rápida:</label>
                    <textarea name="descripcion" rows="3" placeholder="Detalles, peso, beneficios..." required></textarea>
                </div>

                <button type="submit" class="btn-guardar">Guardar en Base de Datos</button>
            </form>
        </div>

        <!-- COLUMNA DERECHA: INVENTARIO Y EXCELS -->
        <div>
            
            <!-- SECCIÓN DESCARGA DE EXCELS -->
            <div class="caja">
                <h3>📊 Comprobantes de Venta (Excel)</h3>
                <p style="font-size: 0.85rem; color: #666; margin-top: -10px; margin-bottom: 15px;">Cada vez que un cliente finaliza una compra en la web, el archivo aparecerá acá:</p>
                
                <ul class="lista-excel">
                    <?php
                    $carpeta_excel = 'ventas_excel/';
                    if (is_dir($carpeta_excel)) {
                        $archivos = array_diff(scandir($carpeta_excel), array('.', '..'));
                        if (count($archivos) > 0) {
                            foreach ($archivos as $archivo) {
                                echo "<li class='item-excel'>
                                        <span>📄 $archivo</span>
                                        <a href='{$carpeta_excel}{$archivo}' download class='btn-descargar'>Descargar</a>
                                      </li>";
                            }
                        } else {
                            echo "<li style='color: #888; font-size: 0.9rem;'>Aún no se registraron compras.</li>";
                        }
                    } else {
                        echo "<li style='color: #888; font-size: 0.9rem;'>Aún no se registraron compras.</li>";
                    }
                    ?>
                </ul>
            </div>

            <!-- SECCIÓN TABLA DE INVENTARIO -->
            <div class="caja">
                <h3>📦 Inventario Actual en la Web</h3>
                <?php if ($resultado_productos && $resultado_productos->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Producto + Marca</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($prod = $resultado_productos->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $prod['nombre']; ?></strong><br>
                                        <span style="font-size: 0.8rem; color: #777;"><?php echo $prod['marca']; ?></span>
                                    </td>
                                    <td><span class="badge-cat"><?php echo $prod['tipo']; ?></span></td>
                                    <td class="precio-tabla">$ <?php echo number_format($prod['precio'], 0, ',', '.'); ?></td>
                                    <td><?php echo $prod['stock']; ?> u.</td>
                                    <td>
                                        <a href="panel.php?borrar=<?php echo $prod['id']; ?>" class="btn-borrar" onclick="return confirm('¿Seguro que querés eliminar este producto de la tienda?');">Borrar</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #888; text-align: center; padding: 20px;">No hay productos cargados en la base de datos. ¡Usá el formulario de la izquierda para crear el primero!</p>
                <?php endif; ?>
            </div>

        </div>

    </div>
</div>

</body>
</html>