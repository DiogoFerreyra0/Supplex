<?php
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$url_lauti = "http://100.93.175.113:8000";
$archivo_meta = __DIR__ . "/catalogo_web.json";

$meta_web = file_exists($archivo_meta) ? json_decode(file_get_contents($archivo_meta), true) : [];
if (!is_array($meta_web)) $meta_web = [];
$ov_variantes = $meta_web['variantes'] ?? [];
$ov_productos_img = $meta_web['productos_img'] ?? [];
$creados_locales = $meta_web['creados_locales'] ?? [];
$eliminados = $meta_web['eliminados'] ?? [];

$json_lauti = @file_get_contents($url_lauti);
$datos = ($json_lauti !== FALSE) ? json_decode($json_lauti, true) : [];
if (!is_array($datos) || isset($datos["error_python"]) || isset($datos["aviso"])) {
    $datos = [];
}

$productos = [];
$ids_procesados = [];

foreach ($datos as $index => $fila) {
    $f = array_change_key_case($fila, CASE_LOWER);

    $id = (int)($f["varianteid"] ?? $f["id"] ?? ($index + 1));
    if (isset($eliminados[$id])) continue;

    $nombreBase = trim((string)($f["producto"] ?? $f["nombre"] ?? $f["nombreproducto"] ?? 'Suplemento'));
    $marca = trim((string)($f["marca"] ?? 'Genérica'));
    $categoria = trim((string)($f["categoria"] ?? $f["tipo"] ?? 'General'));
    $presentacion = trim((string)($f["presentacion"] ?? ''));
    $saborColor = trim((string)($f["saborcolor"] ?? ''));
    $obs = trim((string)($f["observaciones"] ?? ''));

    // Normalizar PrecioVenta
    $rawPrecio = $f["precioventa"] ?? $f["precio"] ?? 0;
    if ($rawPrecio === null || $rawPrecio === '' || strtoupper((string)$rawPrecio) === 'NULL') {
        $precioFinal = 0.0;
    } elseif (is_numeric($rawPrecio)) {
        $precioFinal = (float)$rawPrecio;
    } else {
        $limpio = str_replace(',', '.', trim((string)$rawPrecio));
        $limpio = preg_replace('/[^0-9.]/', '', $limpio);
        $precioFinal = (float)$limpio;
    }

    // Normalizar StockActual
    $rawStock = $f["stockactual"] ?? $f["stock"] ?? 0;
    if (is_string($rawStock)) {
        $rawStock = str_replace(',', '.', $rawStock);
    }
    $stockFinal = (int)round((float)$rawStock);

    // Aplicar ediciones realizadas desde panel.php
    if (isset($ov_variantes[$id])) {
        $ov = $ov_variantes[$id];
        if (!empty($ov['nombre'])) $nombreBase = $ov['nombre'];
        if (!empty($ov['marca'])) $marca = $ov['marca'];
        if (!empty($ov['cat'])) $categoria = $ov['cat'];
        if (isset($ov['presentacion'])) $presentacion = $ov['presentacion'];
        if (isset($ov['sabor'])) $saborColor = $ov['sabor'];
        if (isset($ov['precio'])) $precioFinal = (float)$ov['precio'];
        if (isset($ov['stock'])) $stockFinal = (int)$ov['stock'];
        if (!empty($ov['desc'])) $obs = $ov['desc'];
    }

    // Buscar imagen específica de la variante o imagen general del producto
    $clave_prod = mb_strtolower($nombreBase . "|" . $marca, 'UTF-8');
    $imgFinal = $ov_variantes[$id]['img'] ?? ($ov_productos_img[$clave_prod] ?? ($f["imagenurl"] ?? $f["imagen"] ?? "IMG"));
    if ($imgFinal === '') $imgFinal = "IMG";

    $descPorDefecto = "Marca: " . ($marca !== '' ? $marca : 'Genérica') . " | Presentación: " . ($presentacion !== '' ? $presentacion : 'Estándar') . ($saborColor !== '' ? " | Sabor: $saborColor" : "");
    $descFinal = ($obs !== '') ? "$obs ($descPorDefecto)" : $descPorDefecto;

    $productos[] = [
        "id" => $id,
        "nombre" => $nombreBase,
        "marca" => $marca !== '' ? $marca : 'Genérica',
        "cat" => $categoria !== '' ? $categoria : 'General',
        "presentacion" => $presentacion,
        "sabor" => $saborColor,
        "precio" => $precioFinal,
        "img" => $imgFinal,
        "stock" => $stockFinal,
        "desc" => $descFinal
    ];
    $ids_procesados[$id] = true;
}

// Incluir productos creados desde el panel si aún no fueron sincronizados en el servidor remoto
foreach ($creados_locales as $id_loc => $p_loc) {
    if (!isset($ids_procesados[$id_loc]) && !isset($eliminados[$id_loc])) {
        $productos[] = [
            "id" => (int)$id_loc,
            "nombre" => $p_loc['nombre'] ?? 'Suplemento',
            "marca" => $p_loc['marca'] ?? 'Genérica',
            "cat" => $p_loc['cat'] ?? 'General',
            "presentacion" => $p_loc['presentacion'] ?? '',
            "sabor" => $p_loc['sabor'] ?? '',
            "precio" => (float)($p_loc['precio'] ?? 0),
            "img" => !empty($p_loc['img']) ? $p_loc['img'] : 'IMG',
            "stock" => (int)($p_loc['stock'] ?? 0),
            "desc" => !empty($p_loc['desc']) ? $p_loc['desc'] : ("Marca: " . ($p_loc['marca'] ?? 'Genérica'))
        ];
    }
}

echo json_encode($productos, JSON_UNESCAPED_UNICODE);
?>