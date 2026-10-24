<?php
session_start();

// 1. EL GUARDIÁN: Si NO estás logeado, te expulsa al login
if (!isset($_SESSION['admin_logeado']) || $_SESSION['admin_logeado'] !== true) {
    header("Location: login.php");
    exit();
}

$url_lauti = "http://100.93.175.113:8000";
$archivo_meta = __DIR__ . "/catalogo_web.json";
$carpeta_img = __DIR__ . "/img/productos/";

// Crear carpeta de imágenes si no existe
if (!file_exists($carpeta_img)) {
    mkdir($carpeta_img, 0777, true);
}

// Cargar metadatos locales (imágenes, descripciones web y respaldos)
$meta_web = file_exists($archivo_meta) ? json_decode(file_get_contents($archivo_meta), true) : [];
if (!is_array($meta_web)) $meta_web = [];
if (!isset($meta_web['variantes'])) $meta_web['variantes'] = [];
if (!isset($meta_web['productos_img'])) $meta_web['productos_img'] = [];
if (!isset($meta_web['creados_locales'])) $meta_web['creados_locales'] = [];
if (!isset($meta_web['eliminados'])) $meta_web['eliminados'] = [];

$mensaje = "";

// Función auxiliar para enviar POST al Python de Lauti
function enviar_a_lauti($url, $payload) {
    $opciones = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'timeout' => 8
        ]
    ];
    $contexto = stream_context_create($opciones);
    $resultado = @file_get_contents($url, false, $contexto);
    return $resultado ? json_decode($resultado, true) : null;
}

// 2. PROCESAR FORMULARIO (CREAR O EDITAR PRODUCTO / SUBIR IMAGEN)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion_form'])) {
    $modo = $_POST['accion_form']; // 'crear' o 'editar'
    $variante_id = (int)($_POST['variante_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $marca = trim($_POST['marca'] ?? 'Genérica');
    $categoria = trim($_POST['categoria'] ?? 'General');
    $presentacion = trim($_POST['presentacion'] ?? '');
    $sabor = trim($_POST['sabor'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $aplicar_img_grupo = isset($_POST['aplicar_img_grupo']);

    // Procesar subida de imagen si se seleccionó un archivo
    $ruta_imagen_guardada = trim($_POST['imagen_actual'] ?? '');
    if (isset($_FILES['imagen_archivo']) && $_FILES['imagen_archivo']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['imagen_archivo']['tmp_name'];
        $nombre_orig = $_FILES['imagen_archivo']['name'];
        $ext = strtolower(pathinfo($nombre_orig, PATHINFO_EXTENSION));
        $ext_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($ext, $ext_permitidas)) {
            $nombre_limpio = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($nombre));
            $nombre_archivo = "prod_" . substr($nombre_limpio, 0, 25) . "_" . time() . "." . $ext;
            if (move_uploaded_file($tmp_name, $carpeta_img . $nombre_archivo)) {
                $ruta_imagen_guardada = "img/productos/" . $nombre_archivo;
            }
        } else {
            $mensaje = "⚠️ Formato de imagen no válido. Usá JPG, PNG o WEBP.";
        }
    }

    // Enviar orden a la Base de Datos de Lauti
    $payload = [
        "accion" => $modo,
        "variante_id" => $variante_id,
        "nombre" => $nombre,
        "marca" => $marca,
        "categoria" => $categoria,
        "presentacion" => $presentacion,
        "sabor" => $sabor,
        "precio" => $precio,
        "stock" => $stock,
        "descripcion" => $descripcion
    ];

    $resp_lauti = enviar_a_lauti($url_lauti, $payload);

    if ($modo === 'crear') {
        if ($resp_lauti && isset($resp_lauti['variante_id'])) {
            $variante_id = (int)$resp_lauti['variante_id'];
        } else {
            // Si el script de Lauti aún no tiene el POST actualizado, generamos ID local de respaldo
            $variante_id = time();
            $meta_web['creados_locales'][$variante_id] = [
                "id" => $variante_id,
                "nombre" => $nombre,
                "marca" => $marca,
                "cat" => $categoria,
                "presentacion" => $presentacion,
                "sabor" => $sabor,
                "precio" => $precio,
                "stock" => $stock,
                "desc" => $descripcion,
                "img" => $ruta_imagen_guardada !== '' ? $ruta_imagen_guardada : "IMG"
            ];
        }
    }

    // Guardar imagen y datos personalizados en catalogo_web.json
    if ($variante_id > 0) {
        $img_previa = $meta_web['variantes'][$variante_id]['img'] ?? '';
        $img_final = $ruta_imagen_guardada !== '' ? $ruta_imagen_guardada : $img_previa;

        $meta_web['variantes'][$variante_id] = [
            "nombre" => $nombre,
            "marca" => $marca,
            "cat" => $categoria,
            "presentacion" => $presentacion,
            "sabor" => $sabor,
            "precio" => $precio,
            "stock" => $stock,
            "desc" => $descripcion,
            "img" => $img_final
        ];

        // Si marcó aplicar la foto a todas las variantes de este mismo producto (Ej: todos los sabores)
        $clave_producto = mb_strtolower($nombre . "|" . $marca, 'UTF-8');
        if ($aplicar_img_grupo && $img_final !== '') {
            $meta_web['productos_img'][$clave_producto] = $img_final;
        }

        // Si también estaba en creados_locales, lo actualizamos
        if (isset($meta_web['creados_locales'][$variante_id])) {
            $meta_web['creados_locales'][$variante_id] = array_merge($meta_web['creados_locales'][$variante_id], $meta_web['variantes'][$variante_id]);
        }
    }

    file_put_contents($archivo_meta, json_encode($meta_web, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $mensaje = ($modo === 'crear') 
        ? "✅ Producto '$nombre' creado y sincronizado con éxito." 
        : "✅ Producto '$nombre' actualizado correctamente.";
}

// 3. PROCESAR BORRADO / DESACTIVACIÓN DE VARIANTE
if (isset($_GET['borrar'])) {
    $id_borrar = (int)$_GET['borrar'];
    enviar_a_lauti($url_lauti, ["accion" => "borrar", "variante_id" => $id_borrar]);
    $meta_web['eliminados'][$id_borrar] = true;
    unset($meta_web['creados_locales'][$id_borrar]);
    file_put_contents($archivo_meta, json_encode($meta_web, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: panel.php?msj=borrado");
    exit();
}

if (isset($_GET['msj']) && $_GET['msj'] == 'borrado') {
    $mensaje = "🗑️ Variante desactivada y quitada del catálogo web.";
}

// 4. OBTENER INVENTARIO COMBINADO (BD DE LAUTI + IMÁGENES WEB)
$inventario = [];
$json_lauti = @file_get_contents($url_lauti);
$conexion_bd_ok = false;

if ($json_lauti !== FALSE) {
    $datos_bd = json_decode($json_lauti, true);
    if (is_array($datos_bd) && !isset($datos_bd['error_python'])) {
        $conexion_bd_ok = true;
        foreach ($datos_bd as $index => $fila) {
            $f = array_change_key_case($fila, CASE_LOWER);
            $id = (int)($f["varianteid"] ?? $f["id"] ?? ($index + 1));

            if (isset($meta_web['eliminados'][$id])) continue;

            $nombre = trim((string)($f["producto"] ?? $f["nombre"] ?? 'Suplemento'));
            $marca = trim((string)($f["marca"] ?? 'Genérica'));
            $cat = trim((string)($f["categoria"] ?? 'General'));
            $pres = trim((string)($f["presentacion"] ?? ''));
            $sabor = trim((string)($f["saborcolor"] ?? ''));
            
            $rawPrecio = $f["precioventa"] ?? $f["precio"] ?? 0;
            $precio = is_numeric($rawPrecio) ? (float)$rawPrecio : (float)preg_replace('/[^0-9.]/', '', str_replace(',', '.', (string)$rawPrecio));
            
            $rawStock = $f["stockactual"] ?? $f["stock"] ?? 0;
            $stock = (int)round((float)str_replace(',', '.', (string)$rawStock));
            $desc = trim((string)($f["observaciones"] ?? ''));

            // Aplicar ediciones y fotos subidas en el panel
            if (isset($meta_web['variantes'][$id])) {
                $ov = $meta_web['variantes'][$id];
                if (!empty($ov['nombre'])) $nombre = $ov['nombre'];
                if (!empty($ov['marca'])) $marca = $ov['marca'];
                if (!empty($ov['cat'])) $cat = $ov['cat'];
                if (isset($ov['presentacion'])) $pres = $ov['presentacion'];
                if (isset($ov['sabor'])) $sabor = $ov['sabor'];
                if (isset($ov['precio'])) $precio = (float)$ov['precio'];
                if (isset($ov['stock'])) $stock = (int)$ov['stock'];
                if (!empty($ov['desc'])) $desc = $ov['desc'];
            }

            $clave_prod = mb_strtolower($nombre . "|" . $marca, 'UTF-8');
            $img = $meta_web['variantes'][$id]['img'] ?? ($meta_web['productos_img'][$clave_prod] ?? '');

            $inventario[$id] = [
                "id" => $id,
                "nombre" => $nombre,
                "marca" => $marca,
                "cat" => $cat,
                "presentacion" => $pres,
                "sabor" => $sabor,
                "precio" => $precio,
                "stock" => $stock,
                "desc" => $desc,
                "img" => $img
            ];
        }
    }
}

// Agregar productos creados localmente si aún no figuran en el listado
foreach ($meta_web['creados_locales'] as $id_loc => $prod_loc) {
    if (!isset($inventario[$id_loc]) && !isset($meta_web['eliminados'][$id_loc])) {
        $inventario[$id_loc] = $prod_loc;
    }
}
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
            --gris: #f4f4f5;
            --borde: #e4e4e7;
            --alerta: #ef4444;
        }
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; background: var(--gris); margin: 0; padding: 20px; color: #27272a; }
        .contenedor { max-width: 1320px; margin: 0 auto; }
        
        .panel-header { display: flex; justify-content: space-between; align-items: center; background: var(--oscuro); color: #fff; padding: 18px 28px; border-radius: 12px; margin-bottom: 24px; flex-wrap: wrap; gap: 15px; }
        .panel-header h2 { margin: 0; font-weight: 900; letter-spacing: 0.5px; display: flex; align-items: center; gap: 10px; }
        .panel-header h2 span { color: var(--primario); }
        .estado-bd { font-size: 0.78rem; padding: 5px 12px; border-radius: 20px; font-weight: 700; background: #27272a; color: #a3e635; }
        .header-acciones { display: flex; gap: 12px; align-items: center; }
        .btn-ver-web { color: #fff; text-decoration: none; font-size: 0.88rem; border: 1px solid #52525b; padding: 9px 16px; border-radius: 6px; font-weight: 700; transition: 0.2s; }
        .btn-ver-web:hover { background: #fff; color: var(--oscuro); }
        .btn-salir { background: var(--alerta); color: white; padding: 9px 18px; text-decoration: none; border-radius: 6px; font-weight: 800; font-size: 0.88rem; }
        
        .alerta { background: var(--oscuro); color: var(--primario); padding: 15px 20px; border-radius: 8px; margin-bottom: 24px; font-weight: 700; border-left: 5px solid var(--primario); }

        .panel-grilla { display: grid; grid-template-columns: 390px 1fr; gap: 24px; align-items: start; }
        .caja { background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); border: 1px solid var(--borde); margin-bottom: 24px; }
        .caja h3 { margin-top: 0; font-weight: 900; text-transform: uppercase; border-bottom: 2px solid var(--oscuro); padding-bottom: 10px; margin-bottom: 18px; font-size: 1rem; display: flex; justify-content: space-between; align-items: center; }

        .campo { margin-bottom: 14px; }
        .campo-doble { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .campo label { display: block; font-weight: 800; font-size: 0.8rem; margin-bottom: 5px; color: #3f3f46; }
        .campo input[type="text"], .campo input[type="number"], .campo select, .campo textarea { width: 100%; padding: 10px; border: 1px solid var(--borde); border-radius: 6px; font-family: inherit; font-size: 0.9rem; background: #fafafa; }
        .campo input:focus, .campo select:focus, .campo textarea:focus { outline: none; border-color: var(--oscuro); background: #fff; }
        
        .caja-foto { border: 2px dashed var(--borde); padding: 14px; border-radius: 8px; text-align: center; background: #fafafa; margin-bottom: 14px; }
        .preview-img { width: 90px; height: 90px; object-fit: contain; border-radius: 6px; border: 1px solid var(--borde); background: #fff; display: none; margin: 0 auto 10px; }
        .check-grupo { display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 700; color: #3f3f46; margin-top: 8px; text-align: left; cursor: pointer; }

        .btn-guardar { width: 100%; padding: 14px; background: var(--oscuro); color: #fff; border: none; border-radius: 6px; font-weight: 900; text-transform: uppercase; cursor: pointer; transition: 0.2s; font-size: 0.9rem; }
        .btn-guardar:hover { background: var(--primario); color: var(--oscuro); }
        .btn-cancelar-edicion { display: none; width: 100%; padding: 10px; margin-top: 8px; background: #e4e4e7; color: #27272a; border: none; border-radius: 6px; font-weight: 800; cursor: pointer; font-size: 0.82rem; }

        .buscador-tabla { width: 100%; padding: 12px 14px; border: 1px solid var(--borde); border-radius: 8px; margin-bottom: 16px; font-size: 0.92rem; }
        .tabla-scroll { max-height: 640px; overflow-y: auto; border: 1px solid var(--borde); border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem; }
        th, td { padding: 12px 10px; border-bottom: 1px solid var(--borde); vertical-align: middle; }
        th { background: #f4f4f5; font-weight: 800; text-transform: uppercase; font-size: 0.72rem; color: #52525b; position: sticky; top: 0; z-index: 2; }
        .miniatura-tabla { width: 46px; height: 46px; border-radius: 6px; object-fit: contain; background: #f4f4f5; border: 1px solid var(--borde); display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 800; color: #71717a; }
        .badge-cat { background: #e4e4e7; padding: 3px 8px; border-radius: 4px; font-size: 0.72rem; font-weight: 800; }
        .precio-tabla { font-weight: 900; color: var(--oscuro); font-size: 0.95rem; }
        .acciones-celda { display: flex; gap: 6px; }
        .btn-editar { background: var(--oscuro); color: var(--primario); border: none; padding: 6px 10px; border-radius: 4px; font-weight: 800; font-size: 0.78rem; cursor: pointer; }
        .btn-editar:hover { background: var(--primario); color: var(--oscuro); }
        .btn-borrar { color: var(--alerta); text-decoration: none; font-weight: 800; font-size: 0.78rem; padding: 5px 9px; border: 1px solid var(--alerta); border-radius: 4px; }
        .btn-borrar:hover { background: var(--alerta); color: #fff; }

        .lista-excel { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 10px; }
        .item-excel { background: #fafafa; border: 1px solid var(--borde); padding: 10px 12px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; font-size: 0.82rem; font-weight: 700; }
        .btn-descargar { background: #22c55e; color: #fff; text-decoration: none; padding: 5px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: 800; }

        @media (max-width: 980px) { .panel-grilla { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="contenedor">
    
    <div class="panel-header">
        <h2>
            ★ SUPPLEX <span>CONTROL</span>
            <span class="estado-bd">
                <?php echo $conexion_bd_ok ? "🟢 Conectado a BD de Lauti (" . count($inventario) . " variantes)" : "🟠 Modo Local (API de Lauti desconectada)"; ?>
            </span>
        </h2>
        <div class="header-acciones">
            <a href="index.html" target="_blank" class="btn-ver-web">Ver Inicio ↗</a>
            <a href="pages/productos.html" target="_blank" class="btn-ver-web">Ver Catálogo ↗</a>
            <a href="logout.php" class="btn-salir">Cerrar Sesión</a>
        </div>
    </div>

    <?php if ($mensaje != ""): ?>
        <div class="alerta"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>

    <div class="panel-grilla">
        
        <!-- COLUMNA IZQUIERDA: FORMULARIO DE CARGA Y EDICIÓN -->
        <div class="caja" style="position: sticky; top: 20px;">
            <h3 id="tituloFormulario">➕ Nuevo Producto / Variante</h3>
            
            <form action="panel.php" method="POST" enctype="multipart/form-data" id="formProducto">
                <input type="hidden" name="accion_form" id="accionForm" value="crear">
                <input type="hidden" name="variante_id" id="inputVarianteId" value="0">
                <input type="hidden" name="imagen_actual" id="inputImagenActual" value="">
                
                <div class="campo">
                    <label>Nombre del Suplemento:</label>
                    <input type="text" name="nombre" id="inputNombre" placeholder="Ej: CREATINA XTRENGHT" required>
                </div>

                <div class="campo-doble">
                    <div class="campo">
                        <label>Marca:</label>
                        <input type="text" name="marca" id="inputMarca" placeholder="Ej: XTRENGHT" required>
                    </div>
                    <div class="campo">
                        <label>Categoría:</label>
                        <input type="text" name="categoria" id="inputCategoria" list="listaCategoriasSugeridas" placeholder="Ej: Creatina" required>
                        <datalist id="listaCategoriasSugeridas">
                            <option value="Proteína">
                            <option value="Creatina">
                            <option value="Pre-entreno">
                            <option value="Aminoácidos">
                            <option value="Vitaminas">
                            <option value="Barras proteicas">
                            <option value="Energy Drink">
                            <option value="Combo">
                        </datalist>
                    </div>
                </div>

                <div class="campo-doble">
                    <div class="campo">
                        <label>Presentación (Peso/Tamaño):</label>
                        <input type="text" name="presentacion" id="inputPresentacion" placeholder="Ej: 250 g, 2 lb" required>
                    </div>
                    <div class="campo">
                        <label>Sabor / Color:</label>
                        <input type="text" name="sabor" id="inputSabor" placeholder="Ej: Chocolate, Sin sabor" required>
                    </div>
                </div>

                <div class="campo-doble">
                    <div class="campo">
                        <label>Precio de Venta ($):</label>
                        <input type="number" name="precio" id="inputPrecio" placeholder="Ej: 28500" required min="0" step="0.01">
                    </div>
                    <div class="campo">
                        <label>Stock Disponible:</label>
                        <input type="number" name="stock" id="inputStock" value="10" required min="0">
                    </div>
                </div>

                <div class="campo">
                    <label>Descripción para la Card Flotante:</label>
                    <textarea name="descripcion" id="inputDescripcion" rows="2" placeholder="Beneficios, modo de uso, detalles del suplemento..."></textarea>
                </div>

                <div class="caja-foto">
                    <img id="previewImagen" class="preview-img" src="" alt="Vista previa">
                    <label for="inputImagenArchivo" style="display:block; font-weight:800; font-size:0.8rem; margin-bottom:6px; cursor:pointer;">
                        📷 Subir / Cambiar Imagen del Producto
                    </label>
                    <input type="file" name="imagen_archivo" id="inputImagenArchivo" accept="image/*" style="font-size: 0.78rem; width: 100%;">
                    <label class="check-grupo">
                        <input type="checkbox" name="aplicar_img_grupo" checked>
                        Aplicar esta foto a todos los sabores de este mismo producto
                    </label>
                </div>

                <button type="submit" class="btn-guardar" id="btnSubmitForm">Guardar en Base de Datos</button>
                <button type="button" class="btn-cancelar-edicion" id="btnCancelarEdicion">Cancelar edición y volver a "Nuevo"</button>
            </form>
        </div>

        <!-- COLUMNA DERECHA: EXCELS E INVENTARIO DE LA BASE DE DATOS -->
        <div>
            
            <!-- SECCIÓN DESCARGA DE EXCELS -->
            <div class="caja">
                <h3>📊 Comprobantes de Venta (Excel)</h3>
                <ul class="lista-excel">
                    <?php
                    $carpeta_excel = 'ventas_excel/';
                    if (is_dir($carpeta_excel)) {
                        $archivos = array_diff(scandir($carpeta_excel), array('.', '..'));
                        if (count($archivos) > 0) {
                            foreach ($archivos as $archivo) {
                                echo "<li class='item-excel'>
                                        <span>📄 " . htmlspecialchars($archivo) . "</span>
                                        <a href='{$carpeta_excel}{$archivo}' download class='btn-descargar'>Descargar</a>
                                      </li>";
                            }
                        } else {
                            echo "<li style='color: #71717a; font-size: 0.85rem;'>Aún no se registraron compras.</li>";
                        }
                    } else {
                        echo "<li style='color: #71717a; font-size: 0.85rem;'>Aún no se registraron compras.</li>";
                    }
                    ?>
                </ul>
            </div>

            <!-- SECCIÓN TABLA DE INVENTARIO CONECTADA A LAUTI -->
            <div class="caja">
                <h3>
                    <span>📦 Inventario Sincronizado (<?php echo count($inventario); ?> variantes)</span>
                </h3>
                
                <input type="text" id="buscadorPanel" class="buscador-tabla" placeholder="🔍 Buscar en el inventario por nombre, marca, categoría o sabor para editar o poner foto...">

                <div class="tabla-scroll">
                    <table id="tablaInventario">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Producto / Variante</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($inventario) > 0): ?>
                                <?php foreach ($inventario as $prod): 
                                    $tiene_img = !empty($prod['img']) && $prod['img'] !== 'IMG' && $prod['img'] !== 'SUPPLEX';
                                    $json_prod = htmlspecialchars(json_encode($prod, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                ?>
                                    <tr class="fila-inventario">
                                        <td>
                                            <?php if ($tiene_img): ?>
                                                <img src="<?php echo htmlspecialchars($prod['img']); ?>" class="miniatura-tabla" alt="Foto">
                                            <?php else: ?>
                                                <div class="miniatura-tabla">SIN FOTO</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($prod['nombre']); ?></strong><br>
                                            <span style="font-size: 0.78rem; color: #52525b;">
                                                <?php echo htmlspecialchars($prod['marca']); ?> • 
                                                <?php echo htmlspecialchars($prod['presentacion'] ?: 'Estándar'); ?>
                                                <?php echo $prod['sabor'] ? " (" . htmlspecialchars($prod['sabor']) . ")" : ""; ?>
                                            </span>
                                        </td>
                                        <td><span class="badge-cat"><?php echo htmlspecialchars($prod['cat']); ?></span></td>
                                        <td class="precio-tabla">$ <?php echo number_format((float)$prod['precio'], 0, ',', '.'); ?></td>
                                        <td><?php echo (int)$prod['stock']; ?> u.</td>
                                        <td>
                                            <div class="acciones-celda">
                                                <button type="button" class="btn-editar" onclick='cargarParaEditar(<?php echo $json_prod; ?>)'>✏️ Editar / Foto</button>
                                                <a href="panel.php?borrar=<?php echo (int)$prod['id']; ?>" class="btn-borrar" onclick="return confirm('¿Seguro que querés quitar esta variante de la tienda?');">Borrar</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 30px; color: #71717a;">
                                        No hay productos cargados. Usá el formulario de la izquierda para crear el primero.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
    // Cargar los datos de cualquier fila en el formulario izquierdo para editar o subirle foto
    function cargarParaEditar(prod) {
        document.getElementById('tituloFormulario').innerHTML = '✏️ Editando Producto';
        document.getElementById('accionForm').value = 'editar';
        document.getElementById('inputVarianteId').value = prod.id;
        document.getElementById('inputNombre').value = prod.nombre || '';
        document.getElementById('inputMarca').value = prod.marca || '';
        document.getElementById('inputCategoria').value = prod.cat || '';
        document.getElementById('inputPresentacion').value = prod.presentacion || '';
        document.getElementById('inputSabor').value = prod.sabor || '';
        document.getElementById('inputPrecio').value = prod.precio || 0;
        document.getElementById('inputStock').value = prod.stock || 0;
        document.getElementById('inputDescripcion').value = prod.desc || '';
        document.getElementById('inputImagenActual').value = (prod.img && prod.img !== 'IMG') ? prod.img : '';

        const preview = document.getElementById('previewImagen');
        if (prod.img && prod.img !== 'IMG' && prod.img !== 'SUPPLEX') {
            preview.src = prod.img;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }

        document.getElementById('btnSubmitForm').textContent = 'Guardar Cambios';
        document.getElementById('btnCancelarEdicion').style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Volver al modo "Crear Nuevo Producto"
    document.getElementById('btnCancelarEdicion').addEventListener('click', () => {
        document.getElementById('formProducto').reset();
        document.getElementById('tituloFormulario').innerHTML = '➕ Nuevo Producto / Variante';
        document.getElementById('accionForm').value = 'crear';
        document.getElementById('inputVarianteId').value = '0';
        document.getElementById('inputImagenActual').value = '';
        document.getElementById('previewImagen').style.display = 'none';
        document.getElementById('btnSubmitForm').textContent = 'Guardar en Base de Datos';
        document.getElementById('btnCancelarEdicion').style.display = 'none';
    });

    // Vista previa instantánea al elegir una foto desde la PC
    document.getElementById('inputImagenArchivo').addEventListener('change', (e) => {
        const file = e.target.files[0];
        const preview = document.getElementById('previewImagen');
        if (file) {
            preview.src = URL.createObjectURL(file);
            preview.style.display = 'block';
        }
    });

    // Buscador instantáneo dentro de la tabla del panel
    document.getElementById('buscadorPanel').addEventListener('input', (e) => {
        const texto = e.target.value.toLowerCase().trim();
        document.querySelectorAll('.fila-inventario').forEach(fila => {
            const contenido = fila.textContent.toLowerCase();
            fila.style.display = contenido.includes(texto) ? '' : 'none';
        });
    });
</script>

</body>
</html>