<?php
header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-cache, no-store, must-revalidate");
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

if (!is_array($datos) || isset($datos["aviso"]) || isset($datos["error_python"])) {
    echo json_encode($datos ?? ["error" => "JSON inválido"], JSON_UNESCAPED_UNICODE);
    exit;
}

$productos = [];

foreach ($datos as $index => $fila) {
    $f = array_change_key_case($fila, CASE_LOWER);

    $id = $f["varianteid"] ?? $f["id"] ?? ($index + 1);
    $nombreBase = trim((string)($f["producto"] ?? $f["nombre"] ?? $f["nombreproducto"] ?? 'Suplemento'));
    $marca = trim((string)($f["marca"] ?? 'Genérica'));
    $categoria = trim((string)($f["categoria"] ?? $f["tipo"] ?? 'General'));
    $presentacion = trim((string)($f["presentacion"] ?? ''));
    $saborColor = trim((string)($f["saborcolor"] ?? ''));

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

    $productos[] = [
        "id" => (int)$id,
        "nombre" => $nombreBase,
        "marca" => $marca !== '' ? $marca : 'Genérica',
        "cat" => $categoria !== '' ? $categoria : 'General',
        "presentacion" => $presentacion,
        "sabor" => $saborColor,
        "precio" => $precioFinal,
        "img" => $f["imagenurl"] ?? $f["imagen"] ?? "IMG",
        "stock" => $stockFinal,
        "desc" => "Marca: " . ($marca !== '' ? $marca : 'Genérica') . " | Presentación: " . ($presentacion !== '' ? $presentacion : 'Estándar') . ($saborColor !== '' ? " | Sabor: $saborColor" : "")
    ];
}

echo json_encode($productos, JSON_UNESCAPED_UNICODE);
?>