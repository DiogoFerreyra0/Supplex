<?php
session_start();

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

$meta_web = file_exists($archivo_meta) ? json_decode(file_get_contents($archivo_meta), true) : [];
if (!is_array($meta_web)) $meta_web = [];
if (!isset($meta_web['variantes'])) $meta_web['variantes'] = [];
if (!isset($meta_web['productos_img'])) $meta_web['productos_img'] = [];
if (!isset($meta_web['eliminados'])) $meta_web['eliminados'] = [];

$id_usuario_auditoria = (int)($_SESSION['admin_usuario_id'] ?? 8);

function enviar_a_lauti($url, $payload) {
    global $id_usuario_auditoria;
    $payload['usuario_id'] = $id_usuario_auditoria;
    $json_data = json_encode($payload, JSON_UNESCAPED_UNICODE);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $res = curl_exec($ch);
        curl_close($ch);
        if ($res) {
            $dec = json_decode($res, true);
            if (is_array($dec)) return $dec;
        }
    }

    $opciones = [
        'http' => [
            'header'  => "Content-type: application/json; charset=utf-8\r\n",
            'method'  => 'POST',
            'content' => $json_data,
            'timeout' => 20,
            'ignore_errors' => true
        ]
    ];
    $contexto = stream_context_create($opciones);
    $resultado = @file_get_contents($url, false, $contexto);
    return $resultado ? json_decode($resultado, true) : null;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion_form'])) {
    $modo = $_POST['accion_form']; 

    // A) CREAR O EDITAR PRODUCTO PADRE (Lleva la Descripción/Observaciones)
    if ($modo === 'crear_producto_padre') {
        $producto_id_padre = (int)($_POST['producto_id_padre'] ?? 0);
        $nombre_padre = trim($_POST['nombre_padre'] ?? '');
        $marca_padre = trim($_POST['marca_padre'] ?? '');
        $categoria_padre = trim($_POST['categoria_padre'] ?? '');
        $descripcion_padre = trim($_POST['descripcion_padre'] ?? '');

        if ($nombre_padre === '' || $marca_padre === '' || $categoria_padre === '') {
            $_SESSION['flash_mensaje'] = "⚠️ Para gestionar un Producto Padre debés completar el Nombre, Marca y Categoría.";
            $_SESSION['flash_tipo'] = "error";
        } else {
            $resp = enviar_a_lauti($url_lauti, [
                "accion" => "crear_producto_padre",
                "producto_id" => $producto_id_padre,
                "nombre" => $nombre_padre,
                "marca" => $marca_padre,
                "categoria" => $categoria_padre,
                "descripcion" => $descripcion_padre
            ]);

            if ($resp && isset($resp['success']) && $resp['success'] === true) {
                $_SESSION['flash_mensaje'] = "✅ Producto Padre '$nombre_padre' guardado correctamente en SQL Server (ID: {$resp['producto_id']}).";
                $_SESSION['flash_tipo'] = "ok";
            } else {
                $err = $resp['error_python'] ?? "Sin respuesta del servidor de Lauti.";
                $_SESSION['flash_mensaje'] = "❌ Error en el producto padre: " . $err;
                $_SESSION['flash_tipo'] = "error";
            }
        }
        header("Location: panel.php");
        exit();
    }

    // B) CREAR O EDITAR VARIANTE (Sin descripción propia, solo toma la del padre)
    elseif ($modo === 'crear_variante' || $modo === 'editar_variante') {
        $variante_id = (int)($_POST['variante_id'] ?? 0);
        $producto_id = (int)($_POST['producto_id'] ?? 0);
        $nombre = trim($_POST['nombre_oculto'] ?? '');
        $marca = trim($_POST['marca_oculta'] ?? '');
        $presentacion = trim($_POST['presentacion'] ?? '');
        $sabor = trim($_POST['sabor'] ?? '');
        $precio = (float)($_POST['precio'] ?? 0);
        $aplicar_img_grupo = isset($_POST['aplicar_img_grupo']);

        $ruta_imagen_guardada = trim($_POST['imagen_actual'] ?? '');
        if (isset($_FILES['imagen_archivo']) && $_FILES['imagen_archivo']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['imagen_archivo']['tmp_name'];
            $nombre_orig = $_FILES['imagen_archivo']['name'];
            $ext = strtolower(pathinfo($nombre_orig, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $nombre_archivo = "prod_" . substr(preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($nombre)), 0, 25) . "_" . time() . "." . $ext;
                if (move_uploaded_file($tmp_name, $carpeta_img . $nombre_archivo)) {
                    $ruta_imagen_guardada = "img/productos/" . $nombre_archivo;
                }
            }
        }

        if ($modo === 'crear_variante') {
            if ($producto_id === 0) {
                $_SESSION['flash_mensaje'] = "⚠️ Debés seleccionar un Producto Padre.";
                $_SESSION['flash_tipo'] = "error";
            } else {
                $resp = enviar_a_lauti($url_lauti, [
                    "accion" => "crear_variante",
                    "producto_id" => $producto_id,
                    "presentacion" => $presentacion,
                    "sabor" => $sabor,
                    "precio" => $precio
                ]);

                if ($resp && isset($resp['success']) && $resp['success'] === true) {
                    $variante_id = (int)$resp['variante_id'];
                    $_SESSION['flash_mensaje'] = "✅ Variante de '$nombre' ($presentacion - $sabor) creada.";
                    $_SESSION['flash_tipo'] = "ok";
                } else {
                    $_SESSION['flash_mensaje'] = "❌ Error al crear la variante: " . ($resp['error_python'] ?? "Fallo de conexión");
                    $_SESSION['flash_tipo'] = "error";
                }
            }
        } else {
            $resp = enviar_a_lauti($url_lauti, [
                "accion" => "editar_variante",
                "variante_id" => $variante_id,
                "presentacion" => $presentacion,
                "sabor" => $sabor,
                "precio" => $precio
            ]);

            if ($resp && isset($resp['success']) && $resp['success'] === true) {
                $_SESSION['flash_mensaje'] = "✅ Variante '$nombre' ($presentacion - $sabor) actualizada.";
                $_SESSION['flash_tipo'] = "ok";
            } else {
                $_SESSION['flash_mensaje'] = "❌ Error al editar: " . ($resp['error_python'] ?? "Fallo de conexión");
                $_SESSION['flash_tipo'] = "error";
            }
        }

        if ($variante_id > 0 && ($_SESSION['flash_tipo'] ?? '') !== 'error') {
            $img_previa = $meta_web['variantes'][$variante_id]['img'] ?? '';
            $img_final = $ruta_imagen_guardada !== '' ? $ruta_imagen_guardada : $img_previa;

            $meta_web['variantes'][$variante_id] = [
                "presentacion" => $presentacion,
                "sabor" => $sabor,
                "precio" => $precio,
                "img" => $img_final
            ];

            $clave_producto = mb_strtolower($nombre . "|" . $marca, 'UTF-8');
            if ($aplicar_img_grupo && $img_final !== '') {
                $meta_web['productos_img'][$clave_producto] = $img_final;
            }
            file_put_contents($archivo_meta, json_encode($meta_web, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        header("Location: panel.php");
        exit();
    }
}

$mensaje = $_SESSION['flash_mensaje'] ?? "";
$tipo_alerta = $_SESSION['flash_tipo'] ?? "ok";
unset($_SESSION['flash_mensaje'], $_SESSION['flash_tipo']);

if (isset($_GET['borrar'])) {
    $id_borrar = (int)$_GET['borrar'];
    enviar_a_lauti($url_lauti, ["accion" => "borrar", "variante_id" => $id_borrar]);
    $meta_web['eliminados'][$id_borrar] = true;
    file_put_contents($archivo_meta, json_encode($meta_web, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $_SESSION['flash_mensaje'] = "🗑️ Variante desactivada (Activa = 0) y retirada del catálogo web.";
    $_SESSION['flash_tipo'] = "ok";
    header("Location: panel.php");
    exit();
}

$inventario = [];
$productos_padre = [];
$marcas_sistema = [];
$categorias_sistema = [];
$presentaciones_sistema = [];
$sabores_sistema = [];
$pedidos_bd = [];
$conexion_bd_ok = false;
$productos_padre_map = [];

if (function_exists('curl_init')) {
    $ch_get = curl_init($url_lauti . "/?recurso=panel");
    curl_setopt($ch_get, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_get, CURLOPT_CONNECTTIMEOUT, 8);
    curl_setopt($ch_get, CURLOPT_TIMEOUT, 15);
    $json_panel = curl_exec($ch_get);
    curl_close($ch_get);
} else {
    $ctx_get = stream_context_create(['http' => ['timeout' => 15, 'ignore_errors' => true]]);
    $json_panel = @file_get_contents($url_lauti . "/?recurso=panel", false, $ctx_get);
}

if ($json_panel !== FALSE && $json_panel !== null) {
    $data_panel = json_decode($json_panel, true);
    if (is_array($data_panel) && isset($data_panel['variantes'])) {
        $conexion_bd_ok = true;
        $datos_bd = $data_panel['variantes'];
        $productos_padre = $data_panel['productos_padre'] ?? [];
        $marcas_sistema = $data_panel['marcas'] ?? [];
        $categorias_sistema = $data_panel['categorias'] ?? [];
        $presentaciones_sistema = $data_panel['presentaciones'] ?? [];
        $sabores_sistema = $data_panel['sabores'] ?? [];
        $pedidos_bd = $data_panel['pedidos'] ?? [];
    } elseif (is_array($data_panel) && !isset($data_panel['error_python'])) {
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

        $nombre = trim((string)($f["producto"] ?? $f["nombre"] ?? 'Producto'));
        $marca = trim((string)($f["marca"] ?? 'Genérica'));
        $cat = trim((string)($f["categoria"] ?? 'General'));
        $pres = trim((string)($f["presentacion"] ?? ''));
        $sabor = trim((string)($f["saborcolor"] ?? ''));
        $desc = trim((string)($f["observaciones"] ?? ''));

        $rawPrecio = $f["precioventa"] ?? $f["precio"] ?? 0;
        $precio = is_numeric($rawPrecio) ? (float)$rawPrecio : (float)preg_replace('/[^0-9.]/', '', str_replace(',', '.', (string)$rawPrecio));

        $rawStock = $f["stockactual"] ?? $f["stock"] ?? 0;
        $stock = (int)round((float)str_replace(',', '.', (string)$rawStock));

        if (isset($meta_web['variantes'][$id])) {
            $ov = $meta_web['variantes'][$id];
            if (isset($ov['presentacion'])) $pres = $ov['presentacion'];
            if (isset($ov['sabor'])) $sabor = $ov['sabor'];
            if (isset($ov['precio'])) $precio = (float)$ov['precio'];
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
    }
}

$ahora = time();
$registro_pedidos = file_exists($archivo_pedidos) ? json_decode(file_get_contents($archivo_pedidos), true) : [];
if (!is_array($registro_pedidos)) $registro_pedidos = [];

if (!empty($pedidos_bd)) {
    foreach ($pedidos_bd as $p_bd) {
        $cod = $p_bd['CodigoPedido'];
        $estado_lau = mb_strtolower(trim($p_bd['Estado'] ?? 'pendiente'), 'UTF-8');
        $seg_transcurridos = (int)($p_bd['SegundosTranscurridos'] ?? 0);

        if (!isset($registro_pedidos[$cod])) {
            $registro_pedidos[$cod] = [
                "orderId" => $cod,
                "nombre" => $p_bd['ClienteNombre'] ?? 'Cliente',
                "direccion" => $p_bd['ClienteDireccion'] ?? '',
                "email" => $p_bd['ClienteEmail'] ?? '',
                "telefono" => $p_bd['ClienteTelefono'] ?? '',
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

$presupuestos_pendientes = [];
$ventas_confirmadas = [];

foreach ($registro_pedidos as $cod => $ped) {
    $est = mb_strtolower(trim($ped['estado'] ?? 'pendiente'), 'UTF-8');
    $segundos = $ahora - (int)($ped['timestamp'] ?? $ahora);

    if ($est === 'pendiente') {
        if ($segundos >= 1500) {
            if (!empty($ped['archivo_csv']) && file_exists($carpeta_excel . $ped['archivo_csv'])) {
                @unlink($carpeta_excel . $ped['archivo_csv']);
            }
            unset($registro_pedidos[$cod]);
        } else {
            $ped['segundos_restantes'] = max(0, 1500 - $segundos);
            $presupuestos_pendientes[$cod] = $ped;
        }
    } else {
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
        .contenedor { max-width: 1400px; margin: 0 auto; }
        
        .panel-header { display: flex; justify-content: space-between; align-items: center; background: var(--oscuro); color: #fff; padding: 18px 28px; border-radius: 12px; margin-bottom: 24px; flex-wrap: wrap; gap: 15px; }
        .panel-header h2 { margin: 0; font-weight: 900; letter-spacing: 0.5px; display: flex; align-items: center; gap: 10px; }
        .panel-header h2 span { color: var(--primario); }
        .estado-bd { font-size: 0.78rem; padding: 5px 12px; border-radius: 20px; font-weight: 700; background: #27272a; color: #a3e635; }
        .header-acciones { display: flex; gap: 12px; align-items: center; }
        .usuario-sesion { font-size: 0.8rem; color: #a1a1aa; font-weight: 700; }
        .btn-ver-web { color: #fff; text-decoration: none; font-size: 0.88rem; border: 1px solid #52525b; padding: 9px 16px; border-radius: 6px; font-weight: 700; transition: 0.2s; }
        .btn-ver-web:hover { background: #fff; color: var(--oscuro); }
        .btn-salir { background: var(--alerta); color: white; padding: 9px 18px; text-decoration: none; border-radius: 6px; font-weight: 800; font-size: 0.88rem; }
        
        .alerta { background: var(--oscuro); color: var(--primario); padding: 15px 20px; border-radius: 8px; margin-bottom: 24px; font-weight: 700; border-left: 5px solid var(--primario); }
        .alerta--error { color: #fca5a5; border-left-color: var(--alerta); }

        .panel-grilla { display: grid; grid-template-columns: 420px 1fr; gap: 24px; align-items: start; }
        .caja { background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); border: 1px solid var(--borde); margin-bottom: 24px; }
        .caja h3 { margin-top: 0; font-weight: 900; text-transform: uppercase; border-bottom: 2px solid var(--oscuro); padding-bottom: 10px; margin-bottom: 16px; font-size: 1rem; display: flex; justify-content: space-between; align-items: center; }

        .selector-tipo-alta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; background: #f4f4f5; padding: 6px; border-radius: 8px; margin-bottom: 18px; border: 1px solid var(--borde); }
        .opcion-check { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 9px 10px; border-radius: 6px; font-size: 0.78rem; font-weight: 800; cursor: pointer; color: #52525b; transition: 0.2s; text-align: center; }
        .opcion-check input { accent-color: var(--oscuro); cursor: pointer; }
        .opcion-check--activa { background: var(--oscuro); color: var(--primario); }

        .campo { margin-bottom: 14px; }
        .campo-doble { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .campo label { display: block; font-weight: 800; font-size: 0.8rem; margin-bottom: 5px; color: #27272a; }
        .campo input[type="text"], .campo input[type="number"], .campo select, .campo textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--borde); border-radius: 8px; font-family: inherit; font-size: 0.9rem; background: #fafafa; color: #111; }
        .campo input:focus, .campo select:focus, .campo textarea:focus { outline: none; border-color: var(--oscuro); background: #fff; }

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

        .buscador-pedidos { width: 100%; padding: 12px 14px; border: 1px solid var(--borde); border-radius: 8px; margin-bottom: 16px; font-size: 0.92rem; }
        
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
        .acciones-celda { display: flex; gap: 6px; flex-wrap: wrap; }
        .btn-editar { background: var(--oscuro); color: var(--primario); border: none; padding: 6px 10px; border-radius: 4px; font-weight: 800; font-size: 0.78rem; cursor: pointer; }
        .btn-editar:hover { background: var(--primario); color: var(--oscuro); }
        .btn-borrar { color: var(--alerta); text-decoration: none; font-weight: 800; font-size: 0.78rem; padding: 5px 9px; border: 1px solid var(--alerta); border-radius: 4px; }
        .btn-borrar:hover { background: var(--alerta); color: #fff; }

        @media (max-width: 1024px) { .panel-grilla { grid-template-columns: 1fr; } }
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
            <span class="usuario-sesion">👤 <?php echo htmlspecialchars($_SESSION['admin_nombre'] ?? 'PAGINA WEB'); ?> (ID: <?php echo $id_usuario_auditoria; ?>)</span>
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
        
        <!-- ================= COLUMNA IZQUIERDA: FORMULARIO ================= -->
        <div class="caja" style="position: sticky; top: 20px;">
            <h3 id="tituloFormulario">➕ NUEVO PRODUCTO / VARIANTE</h3>

            <!-- CHECK: NUEVA VARIANTE vs NUEVO PRODUCTO PADRE -->
            <div class="selector-tipo-alta" id="selectorTipoAlta">
                <label class="opcion-check" id="labelCheckPadre">
                    <input type="radio" name="tipo_alta_check" value="padre" id="radioNuevoPadre">
                    Nuevo Producto (Padre)
                </label>
                <label class="opcion-check opcion-check--activa" id="labelCheckVariante">
                    <input type="radio" name="tipo_alta_check" value="variante" id="radioNuevaVariante" checked>
                    Nueva Variante
                </label>
            </div>
            
            <form action="panel.php" method="POST" enctype="multipart/form-data" id="formProducto">
                <input type="hidden" name="accion_form" id="accionForm" value="crear_variante">
                <input type="hidden" name="variante_id" id="inputVarianteId" value="0">
                <input type="hidden" name="imagen_actual" id="inputImagenActual" value="">
                <input type="hidden" name="nombre_oculto" id="inputNombreOculto" value="">
                <input type="hidden" name="marca_oculta" id="inputMarcaOculta" value="">
                <input type="hidden" name="categoria_oculta" id="inputCategoriaOculta" value="">
                <input type="hidden" name="producto_id_padre" id="inputProductoIdPadre" value="0">

                <!-- BLOQUE A: PRODUCTO PADRE (Incluye la Descripción General) -->
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
                    <div class="campo">
                        <label>Descripción para la Card Flotante:</label>
                        <textarea name="descripcion_padre" id="inputDescripcionPadre" rows="3" placeholder="Beneficios, modo de uso, detalles generales del suplemento..."></textarea>
                    </div>
                    <p style="font-size: 0.78rem; color: #52525b; background: #f4f4f5; padding: 10px; border-radius: 6px; margin-bottom: 14px;">
                        ℹ️ La descripción general y los detalles se configuran a nivel del <strong>Producto Padre</strong>.
                    </p>
                </div>

                <!-- BLOQUE B: NUEVA VARIANTE / EDITAR (CON SELECTS DINÁMICOS) -->
                <div id="bloqueVariante">
                    <div class="campo">
                        <label>Nombre del Suplemento:</label>
                        <select name="producto_id" id="selectProductoPadre" required>
                            <option value="">Seleccionar producto padre...</option>
                            <?php foreach ($productos_padre as $pp): ?>
                                <option value="<?php echo (int)$pp['ProductoId']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($pp['Nombre']); ?>"
                                        data-marca="<?php echo htmlspecialchars($pp['Marca']); ?>"
                                        data-cat="<?php echo htmlspecialchars($pp['Categoria']); ?>"
                                        data-desc="<?php echo htmlspecialchars($pp['Observaciones'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($pp['Nombre']); ?> (<?php echo htmlspecialchars($pp['Marca']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
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
                            <select name="presentacion" id="selectPresentacion" required>
                                <option value="">Seleccionar presentación...</option>
                                <?php foreach ($presentaciones_sistema as $p): ?>
                                    <option value="<?php echo htmlspecialchars($p); ?>"><?php echo htmlspecialchars($p); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="campo">
                            <label>Sabor / Color:</label>
                            <select name="sabor" id="selectSabor" required>
                                <option value="">Seleccionar sabor...</option>
                                <?php foreach ($sabores_sistema as $s): ?>
                                    <option value="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($s); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="campo-doble">
                        <div class="campo">
                            <label>Precio de Venta ($):</label>
                            <input type="number" name="precio" id="inputPrecio" placeholder="Ej: 28500" required min="0" step="0.01">
                        </div>
                        <div class="campo">
                            <label>Stock Disponible:</label>
                            <input type="text" id="inputStockBloqueado" class="input-bloqueado" value="0 (Por Movimientos)" readonly tabindex="-1">
                        </div>
                    </div>

                    <div class="caja-foto">
                        <img id="previewImagen" class="preview-img" src="" alt="Vista previa">
                        <label for="inputImagenArchivo" style="display:block; font-weight:800; font-size:0.8rem; margin-bottom:6px; cursor:pointer;">
                            📷 Subir / Cambiar Imagen de la Variante
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

        <!-- ================= COLUMNA DERECHA ================= -->
        <div>
            <!-- PRESUPUESTOS ACTIVOS -->
            <div class="caja">
                <h3>
                    <span>⏳ Presupuestos Activos (Duración: 25 minutos)</span>
                    <span style="font-size: 0.75rem; color: #71717a; font-weight: 700;">ESTADO: PENDIENTE</span>
                </h3>
                <p style="font-size: 0.82rem; color: #52525b; margin-top: -8px; margin-bottom: 14px;">
                    Cada pedido iniciado en la web genera un presupuesto en estado <strong>"pendiente"</strong> que viaja al sistema de Lauti. Si no se confirma dentro de los 25 minutos, se elimina automáticamente.
                </p>

                <input type="text" id="buscadorPresupuestos" class="buscador-pedidos" placeholder="🔍 Buscar presupuestos por nombre, email, teléfono, orden o fecha...">

                <?php if (count($presupuestos_pendientes) > 0): ?>
                    <div class="grilla-pedidos" id="grillaPresupuestos">
                        <?php foreach ($presupuestos_pendientes as $pres): ?>
                            <div class="tarjeta-pedido fila-presupuesto" data-expira="<?php echo (int)$pres['segundos_restantes']; ?>">
                                <div class="tarjeta-pedido__top">
                                    <strong>📄 <?php echo htmlspecialchars($pres['orderId']); ?></strong>
                                    <span class="badge-estado badge-estado--pendiente">pendiente</span>
                                </div>
                                <div style="font-size: 0.82rem; color: #3f3f46;">
                                    👤 <strong><?php echo htmlspecialchars($pres['nombre']); ?></strong><br>
                                    ✉️ <?php echo htmlspecialchars($pres['email'] ?? ''); ?><br>
                                    📞 <?php echo htmlspecialchars($pres['telefono'] ?? ''); ?><br>
                                    📍 <?php echo htmlspecialchars($pres['direccion']); ?><br>
                                    📅 <?php echo htmlspecialchars($pres['fecha']); ?><br>
                                    💰 Total: <strong>$ <?php echo number_format((float)$pres['total'], 0, ',', '.'); ?></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                                    <span class="timer-presupuesto js-timer-presupuesto" data-segundos="<?php echo (int)$pres['segundos_restantes']; ?>">
                                        ⏱️ <?php echo floor($pres['segundos_restantes'] / 60) . "m " . ($pres['segundos_restantes'] % 60) . "s"; ?>
                                    </span>
                                    <?php if (!empty($pres['archivo_csv']) && file_exists($carpeta_excel . $pres['archivo_csv'])): ?>
                                        <a href="ventas_excel/<?php echo htmlspecialchars($pres['archivo_csv']); ?>" download class="btn-descargar">Descargar CSV</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #71717a; font-size: 0.85rem; margin: 0;" id="msjCeroPresupuestos">No hay presupuestos pendientes en este momento.</p>
                <?php endif; ?>
            </div>

            <!-- RECEPCIONES DE VENTA / PEDIDOS CONCRETADOS -->
            <div class="caja">
                <h3>
                    <span>✅ Recepciones de Venta (Pedidos Concretados)</span>
                    <span style="font-size: 0.75rem; color: #166534; font-weight: 700;">Sincronizado con Sistema de Lau</span>
                </h3>
                <p style="font-size: 0.82rem; color: #52525b; margin-top: -8px; margin-bottom: 14px;">
                    Cuando Lauti confirma la compra desde su sistema, el presupuesto pasa a esta lista mostrando su estado actual (<strong>"confirmada/en camino"</strong> o <strong>"confirmado/entregado"</strong>).
                </p>

                <input type="text" id="buscadorVentas" class="buscador-pedidos" placeholder="🔍 Buscar ventas por nombre, email, teléfono, orden o fecha...">

                <?php if (count($ventas_confirmadas) > 0): ?>
                    <div class="grilla-pedidos" id="grillaVentas">
                        <?php foreach ($ventas_confirmadas as $venta): 
                            $est_actual = mb_strtolower(trim($venta['estado']), 'UTF-8');
                            $clase_badge = (strpos($est_actual, 'entregado') !== false) ? 'badge-estado--entregado' : 'badge-estado--camino';
                        ?>
                            <div class="tarjeta-pedido fila-venta">
                                <div class="tarjeta-pedido__top">
                                    <strong>🧾 <?php echo htmlspecialchars($venta['orderId']); ?></strong>
                                    <span class="badge-estado <?php echo $clase_badge; ?>">
                                        <?php echo htmlspecialchars($venta['estado']); ?>
                                    </span>
                                </div>
                                <div style="font-size: 0.82rem; color: #3f3f46;">
                                    👤 <strong><?php echo htmlspecialchars($venta['nombre']); ?></strong><br>
                                    ✉️ <?php echo htmlspecialchars($venta['email'] ?? ''); ?><br>
                                    📞 <?php echo htmlspecialchars($venta['telefono'] ?? ''); ?><br>
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
                    <p style="color: #71717a; font-size: 0.85rem; margin: 0;" id="msjCeroVentas">Aún no hay ventas confirmadas desde el sistema de Lauti.</p>
                <?php endif; ?>
            </div>

            <!-- TABLA DE INVENTARIO -->
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
                                <th>Stock (Lauti)</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($inventario) > 0): ?>
                                <?php foreach ($inventario as $prod): 
                                    $tiene_img = !empty($prod['img']) && $prod['img'] !== 'IMG' && $prod['img'] !== 'SUPPLEX';
                                    
                                    // Búsqueda de la descripción original del padre
                                    $desc_padre = '';
                                    if ($prod['producto_id'] > 0 && isset($productos_padre_map[$prod['producto_id']])) {
                                        // La descripción viene mapeada desde el padre
                                        foreach ($productos_padre as $pp) {
                                            if ($pp['ProductoId'] == $prod['producto_id']) {
                                                $desc_padre = $pp['Observaciones'] ?? '';
                                                break;
                                            }
                                        }
                                    }
                                    // Combinamos los datos para JS
                                    $prod_js = $prod;
                                    $prod_js['desc_padre'] = $desc_padre;
                                    
                                    $json_prod = htmlspecialchars(json_encode($prod_js, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
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
                                                <!-- Botón para Editar Padre Rápidamente -->
                                                <button type="button" class="btn-editar" style="background:#52525b;" onclick='cargarPadreParaEditar(<?php echo $json_prod; ?>)'>⚙️ Padre</button>
                                                <a href="panel.php?borrar=<?php echo (int)$prod['id']; ?>" class="btn-borrar" onclick="return confirm('¿Seguro que querés desactivar esta variante de la tienda?');">Borrar</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 30px; color: #71717a;">
                                        No hay productos cargados en la base de datos de Lauti.
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
    const inputProductoIdPadre = document.getElementById('inputProductoIdPadre');

    function actualizarModoAlta() {
        if (radioPadre.checked) {
            labelPadre.classList.add('opcion-check--activa');
            labelVariante.classList.remove('opcion-check--activa');
            accionForm.value = 'crear_producto_padre';
            inputProductoIdPadre.value = '0';
            bloquePadre.style.display = 'block';
            bloqueVariante.style.display = 'none';

            document.getElementById('inputNombrePadre').required = true;
            document.getElementById('selectMarcaPadre').required = true;
            document.getElementById('selectCategoriaPadre').required = true;
            document.getElementById('inputDescripcionPadre').required = true;

            selectProductoPadre.required = false;
            document.getElementById('selectPresentacion').required = false;
            document.getElementById('selectSabor').required = false;
            document.getElementById('inputPrecio').required = false;
        } else {
            labelVariante.classList.add('opcion-check--activa');
            labelPadre.classList.remove('opcion-check--activa');
            accionForm.value = 'crear_variante';
            bloquePadre.style.display = 'none';
            bloqueVariante.style.display = 'block';

            document.getElementById('inputNombrePadre').required = false;
            document.getElementById('selectMarcaPadre').required = false;
            document.getElementById('selectCategoriaPadre').required = false;
            document.getElementById('inputDescripcionPadre').required = false;

            selectProductoPadre.style.display = 'block';
            selectProductoPadre.required = true;
            inputNombreBloqueado.style.display = 'none';
            document.getElementById('selectPresentacion').required = true;
            document.getElementById('selectSabor').required = true;
            document.getElementById('inputPrecio').required = true;
            inputStockBloqueado.value = '0 (Por Movimientos)';
        }
    }

    radioVariante.addEventListener('change', actualizarModoAlta);
    radioPadre.addEventListener('change', actualizarModoAlta);

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

    // EDITAR VARIANTE
    function cargarParaEditar(prod) {
        selectorTipoAlta.style.display = 'none';
        bloquePadre.style.display = 'none';
        bloqueVariante.style.display = 'block';

        tituloFormulario.textContent = '✏️ editar variante';
        tituloFormulario.style.textTransform = 'lowercase';
        tituloFormulario.style.fontSize = '1.25rem';

        accionForm.value = 'editar_variante';
        document.getElementById('inputVarianteId').value = prod.id;

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

        document.getElementById('selectPresentacion').value = prod.presentacion || '';
        document.getElementById('selectPresentacion').required = true;
        document.getElementById('selectSabor').value = prod.sabor || '';
        document.getElementById('selectSabor').required = true;
        document.getElementById('inputPrecio').value = prod.precio ?? 0;
        document.getElementById('inputPrecio').required = true;
        
        document.getElementById('inputImagenActual').value = (prod.img && prod.img !== 'IMG') ? prod.img : '';

        const preview = document.getElementById('previewImagen');
        if (prod.img && prod.img !== 'IMG' && prod.img !== 'SUPPLEX') {
            preview.src = prod.img;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }

        document.getElementById('btnSubmitForm').textContent = 'GUARDAR VARIANTE';
        document.getElementById('btnCancelarEdicion').style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // EDITAR PRODUCTO PADRE
    function cargarPadreParaEditar(prod) {
        selectorTipoAlta.style.display = 'none';
        bloquePadre.style.display = 'block';
        bloqueVariante.style.display = 'none';

        tituloFormulario.textContent = '⚙️ editar producto padre';
        tituloFormulario.style.textTransform = 'lowercase';
        tituloFormulario.style.fontSize = '1.25rem';

        accionForm.value = 'crear_producto_padre'; 
        inputProductoIdPadre.value = prod.producto_id || '0';

        document.getElementById('inputNombrePadre').value = prod.nombre || '';
        document.getElementById('selectMarcaPadre').value = prod.marca || '';
        document.getElementById('selectCategoriaPadre').value = prod.cat || '';
        document.getElementById('inputDescripcionPadre').value = prod.desc_padre || '';

        document.getElementById('inputNombrePadre').required = true;
        document.getElementById('selectMarcaPadre').required = true;
        document.getElementById('selectCategoriaPadre').required = true;
        document.getElementById('inputDescripcionPadre').required = true;

        document.getElementById('btnSubmitForm').textContent = 'GUARDAR PRODUCTO PADRE';
        document.getElementById('btnCancelarEdicion').style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    document.getElementById('btnCancelarEdicion').addEventListener('click', () => {
        document.getElementById('formProducto').reset();
        selectorTipoAlta.style.display = 'grid';
        tituloFormulario.textContent = '➕ NUEVO PRODUCTO / VARIANTE';
        tituloFormulario.style.textTransform = 'uppercase';
        tituloFormulario.style.fontSize = '1rem';

        document.getElementById('inputVarianteId').value = '0';
        inputProductoIdPadre.value = '0';
        document.getElementById('inputImagenActual').value = '';
        document.getElementById('previewImagen').style.display = 'none';
        document.getElementById('btnCancelarEdicion').style.display = 'none';
        document.getElementById('btnSubmitForm').textContent = 'GUARDAR EN BASE DE DATOS';

        radioVariante.checked = true;
        actualizarModoAlta();
    });

    document.getElementById('inputImagenArchivo').addEventListener('change', (e) => {
        const file = e.target.files[0];
        const preview = document.getElementById('previewImagen');
        if (file) {
            preview.src = URL.createObjectURL(file);
            preview.style.display = 'block';
        }
    });

    // BUSCADORES
    document.getElementById('buscadorPanel').addEventListener('input', (e) => {
        const texto = e.target.value.toLowerCase().trim();
        document.querySelectorAll('.fila-inventario').forEach(fila => {
            fila.style.display = fila.textContent.toLowerCase().includes(texto) ? '' : 'none';
        });
    });

    document.getElementById('buscadorPresupuestos').addEventListener('input', (e) => {
        const texto = e.target.value.toLowerCase().trim();
        document.querySelectorAll('.fila-presupuesto').forEach(fila => {
            fila.style.display = fila.textContent.toLowerCase().includes(texto) ? '' : 'none';
        });
    });

    document.getElementById('buscadorVentas').addEventListener('input', (e) => {
        const texto = e.target.value.toLowerCase().trim();
        document.querySelectorAll('.fila-venta').forEach(fila => {
            fila.style.display = fila.textContent.toLowerCase().includes(texto) ? '' : 'none';
        });
    });

    setInterval(() => {
        document.querySelectorAll('.js-timer-presupuesto').forEach(el => {
            let seg = parseInt(el.getAttribute('data-segundos'), 10);
            if (seg <= 1) {
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