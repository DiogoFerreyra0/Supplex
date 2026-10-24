<?php
session_start();

// 1. EL GUARDIÁN: Si NO estás logeado, te expulsa al login
if (!isset($_SESSION['admin_logeado']) || $_SESSION['admin_logeado'] !== true) {
    header("Location: login.php");
    exit();
}

$url_lauti = "http://100.93.175.113:8000";
$archivo_meta = __DIR__ . "/catalogo_web.json";
$archivo_pedidos = __DIR__ . "/pedidos_web.json";
$carpeta_img = __DIR__ . "/img/productos/";
$carpeta_excel = __DIR__ . "/ventas_excel/";

if (!file_exists($carpeta_img)) mkdir($carpeta_img, 0777, true);
if (!file_exists($carpeta_excel)) mkdir($carpeta_excel, 0777, true);

// Cargar metadatos locales (imágenes y descripciones web)
$meta_web = file_exists($archivo_meta) ? json_decode(file_get_contents($archivo_meta), true) : [];
if (!is_array($meta_web)) $meta_web = [];
if (!isset($meta_web['variantes'])) $meta_web['variantes'] = [];
if (!isset($meta_web['productos_img'])) $meta_web['productos_img'] = [];
if (!isset($meta_web['eliminados'])) $meta_web['eliminados'] = [];

$mensaje = "";
$tipo_alerta = "ok";

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

// 2. PROCESAR FORMULARIO (NUEVO PRODUCTO PADRE / NUEVA VARIANTE / EDITAR VARIANTE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion_form'])) {
    $modo = $_POST['accion_form']; // 'crear_producto_padre', 'crear_variante' o 'editar_variante'

    // A) CREAR NUEVO PRODUCTO PADRE (Solo Nombre obligatorio + Marca y Categoría existentes)
    if ($modo === 'crear_producto_padre') {
        $nombre_padre = trim($_POST['nombre_padre'] ?? '');
        $marca_padre = trim($_POST['marca_padre'] ?? '');
        $categoria_padre = trim($_POST['categoria_padre'] ?? '');

        if ($nombre_padre === '' || $marca_padre === '' || $categoria_padre === '') {
            $mensaje = "⚠️ Para crear un Nuevo Producto Padre debés completar el Nombre y elegir Marca y Categoría.";
            $tipo_alerta = "error";
        } else {
            $resp = enviar_a_lauti($url_lauti, [
                "accion" => "crear_producto_padre",
                "nombre" => $nombre_padre,
                "marca" => $marca_padre,
                "categoria" => $categoria_padre
            ]);

            if ($resp && !empty($resp['success'])) {
                $mensaje = "✅ Producto Padre '$nombre_padre' ($marca_padre - $categoria_padre) creado en la base de datos de Lauti (ID: {$resp['producto_id']}). Ahora ya podés seleccionarlo para crearle variantes.";
            } else {
                $mensaje = "❌ Error al conectar con la base de datos de Lauti para crear el producto padre. Verificá que api_productos.py esté corriendo.";
                $tipo_alerta = "error";
            }
        }
    }

    // B) CREAR NUEVA VARIANTE O EDITAR VARIANTE EXISTENTE
    elseif ($modo === 'crear_variante' || $modo === 'editar_variante') {
        $variante_id = (int)($_POST['variante_id'] ?? 0);
        $producto_id = (int)($_POST['producto_id'] ?? 0);
        $nombre = trim($_POST['nombre_oculto'] ?? '');
        $marca = trim($_POST['marca_oculta'] ?? '');
        $categoria = trim($_POST['categoria_oculta'] ?? '');
        $presentacion = trim($_POST['presentacion'] ?? '');
        $sabor = trim($_POST['sabor'] ?? '');
        $precio = (float)($_POST['precio'] ?? 0);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $aplicar_img_grupo = isset($_POST['aplicar_img_grupo']);

        // Subida de imagen
        $ruta_imagen_guardada = trim($_POST['imagen_actual'] ?? '');
        if (isset($_FILES['imagen_archivo']) && $_FILES['imagen_archivo']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['imagen_archivo']['tmp_name'];
            $nombre_orig = $_FILES['imagen_archivo']['name'];
            $ext = strtolower(pathinfo($nombre_orig, PATHINFO_EXTENSION));
            $ext_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (in_array($ext, $ext_permitidas)) {
                $nombre_limpio = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($nombre ?: 'suplemento'));
                $nombre_archivo = "prod_" . substr($nombre_limpio, 0, 25) . "_" . time() . "." . $ext;
                if (move_uploaded_file($tmp_name, $carpeta_img . $nombre_archivo)) {
                    $ruta_imagen_guardada = "img/productos/" . $nombre_archivo;
                }
            }
        }

        if ($modo === 'crear_variante') {
            $resp = enviar_a_lauti($url_lauti, [
                "accion" => "crear_variante",
                "producto_id" => $producto_id,
                "presentacion" => $presentacion,
                "sabor" => $sabor,
                "precio" => $precio,
                "descripcion" => $descripcion
            ]);
            if ($resp && isset($resp['variante_id'])) {
                $variante_id = (int)$resp['variante_id'];
                $mensaje = "✅ Nueva variante de '$nombre' ($presentacion - $sabor) creada con éxito en la base de Lauti.";
            } else {
                $mensaje = "❌ No se pudo crear la variante en la base de Lauti.";
                $tipo_alerta = "error";
            }
        } else {
            $resp = enviar_a_lauti($url_lauti, [
                "accion" => "editar_variante",
                "variante_id" => $variante_id,
                "presentacion" => $presentacion,
                "sabor" => $sabor,
                "precio" => $precio,
                "descripcion" => $descripcion
            ]);
            $mensaje = "✅ Variante '$nombre' ($presentacion - $sabor) actualizada correctamente.";
        }

        if ($variante_id > 0) {
            $img_previa = $meta_web['variantes'][$variante_id]['img'] ?? '';
            $img_final = $ruta_imagen_guardada !== '' ? $ruta_imagen_guardada : $img_previa;

            $meta_web['variantes'][$variante_id] = [
                "presentacion" => $presentacion,
                "sabor" => $sabor,
                "precio" => $precio,
                "desc" => $descripcion,
                "img" => $img_final
            ];

            $clave_producto = mb_strtolower($nombre . "|" . $marca, 'UTF-8');
            if ($aplicar_img_grupo && $img_final !== '') {
                $meta_web['productos_img'][$clave_producto] = $img_final;
            }
            file_put_contents($archivo_meta, json_encode($meta_web, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}

// 3. PROCESAR DESACTIVACIÓN DE VARIANTE (Activa = 0)
if (isset($_GET['borrar'])) {
    $id_borrar = (int)$_GET['borrar'];
    enviar_a_lauti($url_lauti, ["accion" => "borrar", "variante_id" => $id_borrar]);
    $meta_web['eliminados'][$id_borrar] = true;
    file_put_contents($archivo_meta, json_encode($meta_web, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header("Location: panel.php?msj=borrado");
    exit();
}

if (isset($_GET['msj']) && $_GET['msj'] == 'borrado') {
    $mensaje = "🗑️ Variante desactivada (Activa = 0) y ocultada de la tienda.";
}

// 4. CONSULTAR DATOS A LA API DE LAUTI (Catálogo, Productos Padre, Marcas, Categorías y Pedidos)
$inventario = [];
$productos_padre = [];
$marcas_sistema = [];
$categorias_sistema = [];
$pedidos_bd = [];
$conexion_bd_ok = false;

$json_panel = @file_get_contents($url_lauti . "/?recurso=panel");
if ($json_panel !== FALSE) {
    $data_panel = json_decode($json_panel, true);
    if (is_array($data_panel) && isset($data_panel['variantes'])) {
        $conexion_bd_ok = true;
        $datos_bd = $data_panel['variantes'];
        $productos_padre = $data_panel['productos_padre'] ?? [];
        $marcas_sistema = $data_panel['marcas'] ?? [];
        $categorias_sistema = $data_panel['categorias'] ?? [];
        $pedidos_bd = $data_panel['pedidos'] ?? [];
    } elseif (is_array($data_panel) && !isset($data_panel['error_python'])) {
        // Fallback si Lauti aún no reinició el script con ?recurso=panel
        $conexion_bd_ok = true;
        $datos_bd = $data_panel;
    } else {
        $datos_bd = [];
    }

    foreach ($datos_bd as $index => $fila) {
        $f = array_change_key_case($fila, CASE_LOWER);
        $id = (int)($f["varianteid"] ?? $f["id"] ?? ($index + 1));
        $prod_id = (int)($f["productoid"] ?? 0);

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

        if (isset($meta_web['variantes'][$id])) {
            $ov = $meta_web['variantes'][$id];
            if (isset($ov['presentacion'])) $pres = $ov['presentacion'];
            if (isset($ov['sabor'])) $sabor = $ov['sabor'];
            if (isset($ov['precio'])) $precio = (float)$ov['precio'];
            if (!empty($ov['desc'])) $desc = $ov['desc'];
        }

        $clave_prod = mb_strtolower($nombre . "|" . $marca, 'UTF-8');
        $img = $meta_web['variantes'][$id]['img'] ?? ($meta_web['productos_img'][$clave_prod] ?? '');

        $inventario[$id] = [
            "id" => $id,
            "producto_id" => $prod_id,
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

        if ($prod_id > 0 && !isset($productos_padre_map[$prod_id])) {
            $productos_padre_map[$prod_id] = [
                "ProductoId" => $prod_id,
                "Nombre" => $nombre,
                "Marca" => $marca,
                "Categoria" => $cat
            ];
        }
    }
}

// Si la consulta de padres vino vacía, reconstruimos desde las variantes
if (empty($productos_padre) && !empty($productos_padre_map)) {
    $productos_padre = array_values($productos_padre_map);
}
if (empty($marcas_sistema) && !empty($inventario)) {
    $marcas_sistema = array_values(array_unique(array_filter(array_column($inventario, 'marca'))));
    sort($marcas_sistema);
}
if (empty($categorias_sistema) && !empty($inventario)) {
    $categorias_sistema = array_values(array_unique(array_filter(array_column($inventario, 'cat'))));
    sort($categorias_sistema);
}

// 5. SINCRONIZAR PRESUPUESTOS (VIGENCIA 25 MIN) Y COMPROBANTES DE VENTA CONFIRMADOS
$ahora = time();
$registro_pedidos = file_exists($archivo_pedidos) ? json_decode(file_get_contents($archivo_pedidos), true) : [];
if (!is_array($registro_pedidos)) $registro_pedidos = [];

// Actualizar estados locales con los estados manejados por Lauti en SQL Server
if (!empty($pedidos_bd)) {
    $codigos_en_bd = [];
    foreach ($pedidos_bd as $p_bd) {
        $cod = $p_bd['CodigoPedido'];
        $codigos_en_bd[$cod] = true;
        $estado_lau = mb_strtolower(trim($p_bd['Estado'] ?? 'pendiente'), 'UTF-8');
        $seg_transcurridos = (int)($p_bd['SegundosTranscurridos'] ?? 0);

        if (!isset($registro_pedidos[$cod])) {
            $registro_pedidos[$cod] = [
                "orderId" => $cod,
                "nombre" => $p_bd['ClienteNombre'] ?? 'Cliente',
                "direccion" => $p_bd['ClienteDireccion'] ?? '',
                "cupon" => $p_bd['CuponAplicado'] ?? '',
                "subtotal" => (float)($p_bd['Subtotal'] ?? 0),
                "descuento" => (float)($p_bd['Descuento'] ?? 0),
                "total" => (float)($p_bd['Total'] ?? 0),
                "articulos" => $p_bd['articulos'] ?? [],
                "estado" => $estado_lau,
                "fecha" => $p_bd['FechaCreacion'] ?? date("Y-m-d H:i:s"),
                "timestamp" => $ahora - $seg_transcurridos,
                "archivo_csv" => "{$cod}_Supplex.csv"
            ];
        } else {
            $registro_pedidos[$cod]['estado'] = $estado_lau;
            $registro_pedidos[$cod]['timestamp'] = $ahora - $seg_transcurridos;
        }
    }
}

// Eliminar presupuestos en estado 'pendiente' que superen los 25 minutos (1500 segundos)
$presupuestos_pendientes = [];
$ventas_confirmadas = [];

foreach ($registro_pedidos as $cod => $ped) {
    $est = mb_strtolower(trim($ped['estado'] ?? 'pendiente'), 'UTF-8');
    $segundos = $ahora - (int)($ped['timestamp'] ?? $ahora);

    if ($est === 'pendiente') {
        if ($segundos >= 1500) {
            // Expiró a los 25 minutos: borramos el CSV y el registro
            if (!empty($ped['archivo_csv']) && file_exists($carpeta_excel . $ped['archivo_csv'])) {
                @unlink($carpeta_excel . $ped['archivo_csv']);
            }
            unset($registro_pedidos[$cod]);
        } else {
            $ped['segundos_restantes'] = max(0, 1500 - $segundos);
            $presupuestos_pendientes[$cod] = $ped;
        }
    } else {
        // Estado 'confirmada/en camino' o 'confirmado/entregado' manejado desde el sistema de Lau
        $ventas_confirmadas[$cod] = $ped;
    }
}

file_put_contents($archivo_pedidos, json_encode($registro_pedidos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
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
            --gris-bloqueado: #d4d4d8;
            --borde: #e4e4e7;
            --alerta: #ef4444;
        }
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; background: var(--gris); margin: 0; padding: 20px; color: #27272a; }
        .contenedor { max-width: 1360px; margin: 0 auto; }
        
        .panel-header { display: flex; justify-content: space-between; align-items: center; background: var(--oscuro); color: #fff; padding: 18px 28px; border-radius: 12px; margin-bottom: 24px; flex-wrap: wrap; gap: 15px; }
        .panel-header h2 { margin: 0; font-weight: 900; letter-spacing: 0.5px; display: flex; align-items: center; gap: 10px; }
        .panel-header h2 span { color: var(--primario); }
        .estado-bd { font-size: 0.78rem; padding: 5px 12px; border-radius: 20px; font-weight: 700; background: #27272a; color: #a3e635; }
        .header-acciones { display: flex; gap: 12px; align-items: center; }
        .btn-ver-web { color: #fff; text-decoration: none; font-size: 0.88rem; border: 1px solid #52525b; padding: 9px 16px; border-radius: 6px; font-weight: 700; transition: 0.2s; }
        .btn-ver-web:hover { background: #fff; color: var(--oscuro); }
        .btn-salir { background: var(--alerta); color: white; padding: 9px 18px; text-decoration: none; border-radius: 6px; font-weight: 800; font-size: 0.88rem; }
        
        .alerta { background: var(--oscuro); color: var(--primario); padding: 15px 20px; border-radius: 8px; margin-bottom: 24px; font-weight: 700; border-left: 5px solid var(--primario); }
        .alerta--error { color: #fca5a5; border-left-color: var(--alerta); }

        .panel-grilla { display: grid; grid-template-columns: 400px 1fr; gap: 24px; align-items: start; }
        .caja { background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); border: 1px solid var(--borde); margin-bottom: 24px; }
        .caja h3 { margin-top: 0; font-weight: 900; text-transform: uppercase; border-bottom: 2px solid var(--oscuro); padding-bottom: 10px; margin-bottom: 16px; font-size: 1rem; display: flex; justify-content: space-between; align-items: center; }

        /* Selector Check Nuevo Producto vs Nueva Variante */
        .selector-tipo-alta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; background: #f4f4f5; padding: 6px; border-radius: 8px; margin-bottom: 18px; border: 1px solid var(--borde); }
        .opcion-check { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 9px 10px; border-radius: 6px; font-size: 0.78rem; font-weight: 800; cursor: pointer; color: #52525b; transition: 0.2s; text-align: center; }
        .opcion-check input { accent-color: var(--oscuro); cursor: pointer; }
        .opcion-check--activa { background: var(--oscuro); color: var(--primario); }

        .campo { margin-bottom: 14px; }
        .campo-doble { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .campo label { display: block; font-weight: 800; font-size: 0.8rem; margin-bottom: 5px; color: #27272a; }
        .campo input[type="text"], .campo input[type="number"], .campo select, .campo textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--borde); border-radius: 8px; font-family: inherit; font-size: 0.9rem; background: #fafafa; color: #111; }
        .campo input:focus, .campo select:focus, .campo textarea:focus { outline: none; border-color: var(--oscuro); background: #fff; }

        /* Estilo gris bloqueado idéntico a la imagen de referencia */
        .input-bloqueado {
            background-color: var(--gris-bloqueado) !important;
            color: #52525b !important;
            border-color: #cbd5e1 !important;
            cursor: not-allowed !important;
            pointer-events: none;
            font-weight: 700;
            border-radius: 14px !important;
        }
        
        .caja-foto { border: 2px dashed var(--borde); padding: 14px; border-radius: 8px; text-align: center; background: #fafafa; margin-bottom: 14px; }
        .preview-img { width: 90px; height: 90px; object-fit: contain; border-radius: 6px; border: 1px solid var(--borde); background: #fff; display: none; margin: 0 auto 10px; }
        .check-grupo { display: flex; align-items: center; gap: 8px; font-size: 0.8rem; font-weight: 700; color: #3f3f46; margin-top: 8px; text-align: left; cursor: pointer; }

        .btn-guardar { width: 100%; padding: 14px; background: var(--oscuro); color: #fff; border: none; border-radius: 8px; font-weight: 900; text-transform: uppercase; cursor: pointer; transition: 0.2s; font-size: 0.9rem; }
        .btn-guardar:hover { background: var(--primario); color: var(--oscuro); }
        .btn-cancelar-edicion { display: none; width: 100%; padding: 10px; margin-top: 8px; background: #e4e4e7; color: #27272a; border: none; border-radius: 8px; font-weight: 800; cursor: pointer; font-size: 0.82rem; }

        /* Tarjetas de Presupuestos y Comprobantes de Venta */
        .grilla-pedidos { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 12px; }
        .tarjeta-pedido { background: #fafafa; border: 1px solid var(--borde); padding: 14px; border-radius: 10px; display: flex; flex-direction: column; gap: 8px; }
        .tarjeta-pedido__top { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
        .badge-estado { padding: 4px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 900; text-transform: uppercase; }
        .badge-estado--pendiente { background: #fef08a; color: #854d0e; border: 1px solid #fde047; }
        .badge-estado--camino { background: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
        .badge-estado--entregado { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .timer-presupuesto { font-size: 0.76rem; font-weight: 800; color: #dc2626; background: #fee2e2; padding: 3px 8px; border-radius: 6px; }
        .btn-descargar { background: var(--oscuro); color: var(--primario); text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; text-align: center; display: inline-block; }
        .btn-descargar:hover { background: var(--primario); color: var(--oscuro); }

        .buscador-tabla { width: 100%; padding: 12px 14px; border: 1px solid var(--borde); border-radius: 8px; margin-bottom: 16px; font-size: 0.92rem; }
        .tabla-scroll { max-height: 600px; overflow-y: auto; border: 1px solid var(--borde); border-radius: 8px; }
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

        @media (max-width: 980px) { .panel-grilla { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="contenedor">
    
    <div class="panel-header">
        <h2>
            ★ SUPPLEX <span>CONTROL</span>
            <span class="estado-bd">
                <?php echo $conexion_bd_ok ? "🟢 Sincronizado con Sistema de Lauti (" . count($inventario) . " variantes)" : "🟠 Sin conexión con API de Lauti"; ?>
            </span>
        </h2>
        <div class="header-acciones">
            <a href="index.html" target="_blank" class="btn-ver-web">Ver Inicio ↗</a>
            <a href="pages/productos.html" target="_blank" class="btn-ver-web">Ver Catálogo ↗</a>
            <a href="logout.php" class="btn-salir">Cerrar Sesión</a>
        </div>
    </div>

    <?php if ($mensaje != ""): ?>
        <div class="alerta <?php echo $tipo_alerta === 'error' ? 'alerta--error' : ''; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    <div class="panel-grilla">
        
        <!-- ================= COLUMNA IZQUIERDA: FORMULARIO INTELIGENTE ================= -->
        <div class="caja" style="position: sticky; top: 20px;">
            <h3 id="tituloFormulario">➕ NUEVO PRODUCTO / VARIANTE</h3>

            <!-- CHECK DIFERENCIADOR: NUEVA VARIANTE vs NUEVO PRODUCTO PADRE -->
            <div class="selector-tipo-alta" id="selectorTipoAlta">
                <label class="opcion-check opcion-check--activa" id="labelCheckVariante">
                    <input type="radio" name="tipo_alta_check" value="variante" id="radioNuevaVariante" checked>
                    Nueva Variante
                </label>
                <label class="opcion-check" id="labelCheckPadre">
                    <input type="radio" name="tipo_alta_check" value="padre" id="radioNuevoPadre">
                    Nuevo Producto (Padre)
                </label>
            </div>
            
            <form action="panel.php" method="POST" enctype="multipart/form-data" id="formProducto">
                <input type="hidden" name="accion_form" id="accionForm" value="crear_variante">
                <input type="hidden" name="variante_id" id="inputVarianteId" value="0">
                <input type="hidden" name="imagen_actual" id="inputImagenActual" value="">
                <input type="hidden" name="nombre_oculto" id="inputNombreOculto" value="">
                <input type="hidden" name="marca_oculta" id="inputMarcaOculta" value="">
                <input type="hidden" name="categoria_oculta" id="inputCategoriaOculta" value="">

                <!-- BLOQUE A: CAMPOS PARA NUEVO PRODUCTO PADRE -->
                <div id="bloqueProductoPadre" style="display: none;">
                    <div class="campo">
                        <label>Nombre del Suplemento (Obligatorio):</label>
                        <input type="text" name="nombre_padre" id="inputNombrePadre" placeholder="Ej: CREATINA MONOHIDRATO">
                    </div>
                    <div class="campo-doble">
                        <div class="campo">
                            <label>Marca (Del Sistema):</label>
                            <select name="marca_padre" id="selectMarcaPadre">
                                <option value="">Seleccionar marca...</option>
                                <?php foreach ($marcas_sistema as $m): ?>
                                    <option value="<?php echo htmlspecialchars($m); ?>"><?php echo htmlspecialchars($m); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="campo">
                            <label>Categoría (Del Sistema):</label>
                            <select name="categoria_padre" id="selectCategoriaPadre">
                                <option value="">Seleccionar categoría...</option>
                                <?php foreach ($categorias_sistema as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <p style="font-size: 0.78rem; color: #52525b; background: #f4f4f5; padding: 10px; border-radius: 6px; margin-bottom: 14px;">
                        ℹ️ El <strong>Producto Padre</strong> registra el suplemento base en <code>dbo.Productos</code> con su marca y categoría. Luego podrás crearle todas sus variantes de peso y sabor.
                    </p>
                </div>

                <!-- BLOQUE B: CAMPOS PARA NUEVA VARIANTE Y MODO EDITAR -->
                <div id="bloqueVariante">
                    <div class="campo">
                        <label>Nombre del Suplemento:</label>
                        <!-- En modo "Nueva Variante" se elige el producto padre de la lista -->
                        <select name="producto_id" id="selectProductoPadre" required>
                            <option value="">Seleccionar producto padre...</option>
                            <?php foreach ($productos_padre as $pp): ?>
                                <option value="<?php echo (int)$pp['ProductoId']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($pp['Nombre']); ?>"
                                        data-marca="<?php echo htmlspecialchars($pp['Marca']); ?>"
                                        data-cat="<?php echo htmlspecialchars($pp['Categoria']); ?>">
                                    <?php echo htmlspecialchars($pp['Nombre']); ?> (<?php echo htmlspecialchars($pp['Marca']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <!-- En modo "editar" se muestra bloqueado en gris tal como en la imagen -->
                        <input type="text" id="inputNombreBloqueado" class="input-bloqueado" style="display: none;" readonly tabindex="-1">
                    </div>

                    <div class="campo-doble">
                        <div class="campo">
                            <label>Marca:</label>
                            <input type="text" id="inputMarcaVista" class="input-bloqueado" placeholder="Auto (No editable)" readonly tabindex="-1">
                        </div>
                        <div class="campo">
                            <label>Categoría:</label>
                            <input type="text" id="inputCategoriaVista" class="input-bloqueado" placeholder="Auto (No editable)" readonly tabindex="-1">
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
                            <!-- Siempre bloqueado en gris porque responde a MovimientosStock -->
                            <input type="text" id="inputStockBloqueado" class="input-bloqueado" value="0 (Por Movimientos)" readonly tabindex="-1">
                        </div>
                    </div>

                    <div class="campo">
                        <label>Descripción para la Card Flotante:</label>
                        <textarea name="descripcion" id="inputDescripcion" rows="2" placeholder="Beneficios, modo de uso, detalles del suplemento..." required></textarea>
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
                </div>

                <button type="submit" class="btn-guardar" id="btnSubmitForm">GUARDAR EN BASE DE DATOS</button>
                <button type="button" class="btn-cancelar-edicion" id="btnCancelarEdicion">Cancelar edición y volver a Nuevo</button>
            </form>
        </div>

        <!-- ================= COLUMNA DERECHA: PRESUPUESTOS, RECEPCIONES DE VENTA E INVENTARIO ================= -->
        <div>
            
            <!-- 1. SECCIÓN PRESUPUESTOS (DURACIÓN 25 MINUTOS - ESTADO: "pendiente") -->
            <div class="caja">
                <h3>
                    <span>⏳ Presupuestos Activos (Duración: 25 minutos)</span>
                    <span style="font-size: 0.75rem; color: #71717a; font-weight: 700;">Estado: PENDIENTE</span>
                </h3>
                <p style="font-size: 0.82rem; color: #52525b; margin-top: -8px; margin-bottom: 14px;">
                    Cada pedido iniciado en la web genera un presupuesto en estado <strong>"pendiente"</strong> que viaja al sistema de Lauti. Si no se confirma dentro de los 25 minutos, se elimina automáticamente.
                </p>

                <?php if (count($presupuestos_pendientes) > 0): ?>
                    <div class="grilla-pedidos">
                        <?php foreach ($presupuestos_pendientes as $pres): ?>
                            <div class="tarjeta-pedido" data-expira="<?php echo (int)$pres['segundos_restantes']; ?>">
                                <div class="tarjeta-pedido__top">
                                    <strong>📄 <?php echo htmlspecialchars($pres['orderId']); ?></strong>
                                    <span class="badge-estado badge-estado--pendiente">pendiente</span>
                                </div>
                                <div style="font-size: 0.82rem; color: #3f3f46;">
                                    👤 <strong><?php echo htmlspecialchars($pres['nombre']); ?></strong><br>
                                    📍 <?php echo htmlspecialchars($pres['direccion']); ?><br>
                                    💰 Total: <strong>$ <?php echo number_format((float)$pres['total'], 0, ',', '.'); ?></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                                    <span class="timer-presupuesto js-timer-presupuesto" data-segundos="<?php echo (int)$pres['segundos_restantes']; ?>">
                                        ⏱️ <?php echo floor($pres['segundos_restantes'] / 60) . "m " . ($pres['segundos_restantes'] % 60) . "s"; ?>
                                    </span>
                                    <?php if (!empty($pres['archivo_csv']) && file_exists($carpeta_excel . $pres['archivo_csv'])): ?>
                                        <a href="ventas_excel/<?php echo htmlspecialchars($pres['archivo_csv']); ?>" download class="btn-descargar">Descargar Presupuesto</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #71717a; font-size: 0.85rem; margin: 0;">No hay presupuestos pendientes en este momento.</p>
                <?php endif; ?>
            </div>

            <!-- 2. SECCIÓN RECEPCIONES DE VENTA / COMPROBANTES DE VENTA CONCRETADOS -->
            <div class="caja">
                <h3>
                    <span>✅ Recepciones y Comprobantes de Venta (Pedidos Concretados)</span>
                    <span style="font-size: 0.75rem; color: #166534; font-weight: 700;">Sincronizado con Sistema de Lau</span>
                </h3>
                <p style="font-size: 0.82rem; color: #52525b; margin-top: -8px; margin-bottom: 14px;">
                    Cuando Lauti confirma la compra desde su sistema, el presupuesto pasa a esta lista de ventas concretadas mostrando su estado actual (<strong>"confirmada/en camino"</strong> o <strong>"confirmado/entregado"</strong>).
                </p>

                <?php if (count($ventas_confirmadas) > 0): ?>
                    <div class="grilla-pedidos">
                        <?php foreach ($ventas_confirmadas as $venta): 
                            $est_actual = mb_strtolower(trim($venta['estado']), 'UTF-8');
                            $clase_badge = (strpos($est_actual, 'entregado') !== false) ? 'badge-estado--entregado' : 'badge-estado--camino';
                        ?>
                            <div class="tarjeta-pedido">
                                <div class="tarjeta-pedido__top">
                                    <strong>🧾 <?php echo htmlspecialchars($venta['orderId']); ?></strong>
                                    <span class="badge-estado <?php echo $clase_badge; ?>">
                                        <?php echo htmlspecialchars($venta['estado']); ?>
                                    </span>
                                </div>
                                <div style="font-size: 0.82rem; color: #3f3f46;">
                                    👤 <strong><?php echo htmlspecialchars($venta['nombre']); ?></strong><br>
                                    📍 <?php echo htmlspecialchars($venta['direccion']); ?><br>
                                    📅 Fecha: <?php echo htmlspecialchars($venta['fecha']); ?><br>
                                    💰 Total Cobrado: <strong>$ <?php echo number_format((float)$venta['total'], 0, ',', '.'); ?></strong>
                                </div>
                                <div style="display: flex; justify-content: flex-end; margin-top: 4px;">
                                    <?php if (!empty($venta['archivo_csv']) && file_exists($carpeta_excel . $venta['archivo_csv'])): ?>
                                        <a href="ventas_excel/<?php echo htmlspecialchars($venta['archivo_csv']); ?>" download class="btn-descargar">📥 Descargar Comprobante</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #71717a; font-size: 0.85rem; margin: 0;">Aún no hay ventas confirmadas desde el sistema de Lauti.</p>
                <?php endif; ?>
            </div>

            <!-- 3. SECCIÓN TABLA DE INVENTARIO -->
            <div class="caja">
                <h3>
                    <span>📦 Inventario Sincronizado (<?php echo count($inventario); ?> variantes)</span>
                </h3>
                
                <input type="text" id="buscadorPanel" class="buscador-tabla" placeholder="🔍 Buscar por nombre, marca, categoría o sabor...">

                <div class="tabla-scroll">
                    <table id="tablaInventario">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Producto / Variante</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Stock (Movimientos)</th>
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
                                        <td><strong><?php echo (int)$prod['stock']; ?> u.</strong></td>
                                        <td>
                                            <div class="acciones-celda">
                                                <button type="button" class="btn-editar" onclick='cargarParaEditar(<?php echo $json_prod; ?>)'>✏️ Editar / Foto</button>
                                                <a href="panel.php?borrar=<?php echo (int)$prod['id']; ?>" class="btn-borrar" onclick="return confirm('¿Seguro que querés desactivar esta variante de la tienda?');">Borrar</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 30px; color: #71717a;">
                                        No hay productos cargados.
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
    const radioVariante = document.getElementById('radioNuevaVariante');
    const radioPadre = document.getElementById('radioNuevoPadre');
    const labelVariante = document.getElementById('labelCheckVariante');
    const labelPadre = document.getElementById('labelCheckPadre');
    const selectorTipoAlta = document.getElementById('selectorTipoAlta');

    const bloquePadre = document.getElementById('bloqueProductoPadre');
    const bloqueVariante = document.getElementById('bloqueVariante');
    const accionForm = document.getElementById('accionForm');
    const tituloFormulario = document.getElementById('tituloFormulario');

    const selectProductoPadre = document.getElementById('selectProductoPadre');
    const inputNombreBloqueado = document.getElementById('inputNombreBloqueado');
    const inputMarcaVista = document.getElementById('inputMarcaVista');
    const inputCategoriaVista = document.getElementById('inputCategoriaVista');
    const inputStockBloqueado = document.getElementById('inputStockBloqueado');

    const inputNombreOculto = document.getElementById('inputNombreOculto');
    const inputMarcaOculta = document.getElementById('inputMarcaOculta');
    const inputCategoriaOculta = document.getElementById('inputCategoriaOculta');

    // Cambiar entre "Nueva Variante" y "Nuevo Producto (Padre)"
    function actualizarModoAlta() {
        if (radioPadre.checked) {
            labelPadre.classList.add('opcion-check--activa');
            labelVariante.classList.remove('opcion-check--activa');
            accionForm.value = 'crear_producto_padre';
            bloquePadre.style.display = 'block';
            bloqueVariante.style.display = 'none';

            // Obligatorios de Producto Padre
            document.getElementById('inputNombrePadre').required = true;
            document.getElementById('selectMarcaPadre').required = true;
            document.getElementById('selectCategoriaPadre').required = true;

            // Desactivar required de Variante
            selectProductoPadre.required = false;
            document.getElementById('inputPresentacion').required = false;
            document.getElementById('inputSabor').required = false;
            document.getElementById('inputPrecio').required = false;
            document.getElementById('inputDescripcion').required = false;
        } else {
            labelVariante.classList.add('opcion-check--activa');
            labelPadre.classList.remove('opcion-check--activa');
            accionForm.value = 'crear_variante';
            bloquePadre.style.display = 'none';
            bloqueVariante.style.display = 'block';

            document.getElementById('inputNombrePadre').required = false;
            document.getElementById('selectMarcaPadre').required = false;
            document.getElementById('selectCategoriaPadre').required = false;

            selectProductoPadre.style.display = 'block';
            selectProductoPadre.required = true;
            inputNombreBloqueado.style.display = 'none';
            document.getElementById('inputPresentacion').required = true;
            document.getElementById('inputSabor').required = true;
            document.getElementById('inputPrecio').required = true;
            document.getElementById('inputDescripcion').required = true;
            inputStockBloqueado.value = '0 (Por Movimientos)';
        }
    }

    radioVariante.addEventListener('change', actualizarModoAlta);
    radioPadre.addEventListener('change', actualizarModoAlta);

    // Al seleccionar un Producto Padre en "Nueva Variante", autocompletar Marca y Categoría bloqueadas
    selectProductoPadre.addEventListener('change', (e) => {
        const opt = e.target.options[e.target.selectedIndex];
        const nom = opt.getAttribute('data-nombre') || '';
        const mar = opt.getAttribute('data-marca') || '';
        const cat = opt.getAttribute('data-cat') || '';

        inputMarcaVista.value = mar;
        inputCategoriaVista.value = cat;
        inputNombreOculto.value = nom;
        inputMarcaOculta.value = mar;
        inputCategoriaOculta.value = cat;
    });

    // Modo "editar" idéntico a la imagen de referencia (Nombre, Marca, Categoría y Stock en gris bloqueado)
    function cargarParaEditar(prod) {
        selectorTipoAlta.style.display = 'none';
        bloquePadre.style.display = 'none';
        bloqueVariante.style.display = 'block';

        tituloFormulario.textContent = 'editar';
        tituloFormulario.style.textTransform = 'lowercase';
        tituloFormulario.style.fontSize = '1.35rem';

        accionForm.value = 'editar_variante';
        document.getElementById('inputVarianteId').value = prod.id;

        // Ocultar el select de padres y mostrar el input gris bloqueado con el nombre del suplemento
        selectProductoPadre.style.display = 'none';
        selectProductoPadre.required = false;
        inputNombreBloqueado.style.display = 'block';
        inputNombreBloqueado.value = prod.nombre || '';

        inputMarcaVista.value = prod.marca || '';
        inputCategoriaVista.value = prod.cat || '';
        inputStockBloqueado.value = (prod.stock ?? 0) + ' u. (Por Movimientos)';

        inputNombreOculto.value = prod.nombre || '';
        inputMarcaOculta.value = prod.marca || '';
        inputCategoriaOculta.value = prod.cat || '';

        // Campos editables obligatorios
        document.getElementById('inputPresentacion').value = prod.presentacion || '';
        document.getElementById('inputPresentacion').required = true;
        document.getElementById('inputSabor').value = prod.sabor || '';
        document.getElementById('inputSabor').required = true;
        document.getElementById('inputPrecio').value = prod.precio ?? 0;
        document.getElementById('inputPrecio').required = true;
        document.getElementById('inputDescripcion').value = prod.desc || 'Suplemento deportivo de calidad garantizada.';
        document.getElementById('inputDescripcion').required = true;
        document.getElementById('inputImagenActual').value = (prod.img && prod.img !== 'IMG') ? prod.img : '';

        const preview = document.getElementById('previewImagen');
        if (prod.img && prod.img !== 'IMG' && prod.img !== 'SUPPLEX') {
            preview.src = prod.img;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }

        document.getElementById('btnSubmitForm').textContent = 'GUARDAR EN BASE DE DATOS';
        document.getElementById('btnCancelarEdicion').style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Volver al modo Nuevo Producto / Variante
    document.getElementById('btnCancelarEdicion').addEventListener('click', () => {
        document.getElementById('formProducto').reset();
        selectorTipoAlta.style.display = 'grid';
        tituloFormulario.textContent = '➕ NUEVO PRODUCTO / VARIANTE';
        tituloFormulario.style.textTransform = 'uppercase';
        tituloFormulario.style.fontSize = '1rem';

        document.getElementById('inputVarianteId').value = '0';
        document.getElementById('inputImagenActual').value = '';
        document.getElementById('previewImagen').style.display = 'none';
        document.getElementById('btnCancelarEdicion').style.display = 'none';

        radioVariante.checked = true;
        actualizarModoAlta();
    });

    // Vista previa de imagen seleccionada
    document.getElementById('inputImagenArchivo').addEventListener('change', (e) => {
        const file = e.target.files[0];
        const preview = document.getElementById('previewImagen');
        if (file) {
            preview.src = URL.createObjectURL(file);
            preview.style.display = 'block';
        }
    });

    // Buscador de la tabla de inventario
    document.getElementById('buscadorPanel').addEventListener('input', (e) => {
        const texto = e.target.value.toLowerCase().trim();
        document.querySelectorAll('.fila-inventario').forEach(fila => {
            fila.style.display = fila.textContent.toLowerCase().includes(texto) ? '' : 'none';
        });
    });

    // Cuenta regresiva en tiempo real para los Presupuestos (25 minutos)
    setInterval(() => {
        document.querySelectorAll('.js-timer-presupuesto').forEach(el => {
            let seg = parseInt(el.getAttribute('data-segundos'), 10);
            if (seg <= 1) {
                // Al llegar a 0 se recarga el panel para purgar el presupuesto vencido
                window.location.reload();
            } else {
                seg--;
                el.setAttribute('data-segundos', String(seg));
                const m = Math.floor(seg / 60);
                const s = seg % 60;
                el.textContent = `⏱️ ${m}m ${s < 10 ? '0' : ''}${s}s`;
            }
        });
    }, 1000);
</script>

</body>
</html>