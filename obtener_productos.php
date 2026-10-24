<?php
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-cache, no-store, must-revalidate"); // Evita caché antigua
header("Pragma: no-cache");
header("Expires: 0");

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

$productos = []; // CORREGIDO: Inicializamos correctamente la variable que luego devolvemos

foreach ($datos as $index => $fila) {
    $f = array_change_key_case($fila, CASE_LOWER);

    $id = $f["varianteid"] ?? ($index + 1);
    $nombreBase = $f["nombre"] ?? $f["nombreproducto"] ?? $f["producto"] ?? 'Suplemento';
    $marca = $f["marca"] ?? 'Genérica';
    $categoria = $f["categoria"] ?? 'General';
    $presentacion = $f["presentacion"] ?? '';
    $saborColor = $f["saborcolor"] ?? '';

    // Capturamos el PrecioVenta real de la tabla VariantesProducto
    $precioVenta = $f["precioventa"] ?? $f["precio"] ?? 0;
    if (is_string($precioVenta)) {
        $precioVenta = preg_replace('/[^0-9.]/', '', str_replace(',', '.', $precioVenta));
    }

    $stockActual = $f["stockactual"] ?? $f["stock"] ?? 10;

    $productos[] = [
        "id" => $id,
        "nombre" => trim($nombreBase),
        "marca" => trim($marca),
        "cat" => trim($categoria),
        "presentacion" => trim($presentacion),
        "sabor" => trim($saborColor),
        "sku" => $f["skuinterno"] ?? 'N/D',
        "precio" => (float)$precioVenta,
        "img" => $f["imagenurl"] ?? "IMG",
        "stock" => (int)$stockActual,
        "desc" => "SKU: " . ($f["skuinterno"] ?? 'N/D') . " | Marca: " . trim($marca) . " | Presentación: " . ($presentacion !== '' ? $presentacion : 'Estándar') . ($saborColor !== '' ? " | Sabor: $saborColor" : "")
    ];
}

echo json_encode($productos, JSON_UNESCAPED_UNICODE);
?>