<?php
header('Content-Type: application/json; charset=utf-8');

// Consultamos al servidor Python de Lauti por su IP de Tailscale
$url_lauti = "http://100.93.175.113:8000";
$json_lauti = @file_get_contents($url_lauti);

if ($json_lauti === FALSE) {
    echo json_encode([
        "error" => "No se pudo conectar al Python de Lauti en $url_lauti. Verificá que él tenga corriendo api_productos.py"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$datos = json_decode($json_lauti, true);

if (isset($datos["aviso"]) || isset($datos["error_python"])) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

$productos = [];

foreach ($datos as $index => $fila) {
    // Convertimos todas las claves de la fila a minúsculas para un manejo seguro
    $f = array_change_key_case($fila, CASE_LOWER);

    $id = $f["varianteid"] ?? ($index + 1);
    $nombreBase = $f["nombreproducto"] ?? $f["nombre"] ?? 'Suplemento';
    $marca = $f["marca"] ?? '';
    $presentacion = $f["presentacion"] ?? '';
    $saborColor = $f["saborcolor"] ?? '';
    $categoria = $f["categoria"] ?? 'General';

    // Armamos el nombre comercial completo (Ej: "Creatina Xtrenght - 250 g (Chocolate)")
    $nombreCompleto = $nombreBase;
    if ($marca !== '') {
        $nombreCompleto = "$nombreBase ($marca)";
    }
    if ($presentacion !== '') {
        $nombreCompleto .= " - " . $presentacion;
    }
    if ($saborColor !== '') {
        $nombreCompleto .= " - " . $saborColor;
    }

    // Obtenemos el PrecioVenta real de la base de datos de Lauti
    $precioVenta = $f["precioventa"] ?? 0;
    if (is_string($precioVenta)) {
        $precioVenta = preg_replace('/[^0-9.]/', '', str_replace(',', '.', $precioVenta));
    }

    // Stock actual calculado desde MovimientosStock
    $stockActual = $f["stockactual"] ?? 10;

    $productos[] = [
        "id" => $id,
        "nombre" => $nombreCompleto,
        "cat" => $categoria,
        "desc" => "SKU: " . ($f["skuinterno"] ?? 'N/D') . " | Marca: " . ($marca !== '' ? $marca : 'Genérica') . " | Presentación: " . ($presentacion !== '' ? $presentacion : 'Estándar') . ($saborColor !== '' ? " | Sabor/Color: $saborColor" : ""),
        "precio" => (float)$precioVenta,
        "img" => $f["imagenurl"] ?? "IMG",
        "stock" => (int)$stockActual
    ];
}

echo json_encode($productos, JSON_UNESCAPED_UNICODE);
?>