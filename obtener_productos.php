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
    // Convertimos todas las claves a minúsculas para estandarizar la lectura
    $f = array_change_key_case($fila, CASE_LOWER);

    $id = $f["varianteid"] ?? $f["id"] ?? ($index + 1);
    $nombreProducto = $f["producto"] ?? $f["nombre"] ?? 'Suplemento';
    $presentacion = $f["presentacion"] ?? '';
    $saborColor = $f["saborcolor"] ?? '';

    // Armamos un nombre comercial claro (Ej: "Creatina Monohidrato - 500g - Frutos Rojos")
    $nombreCompleto = $nombreProducto;
    if ($presentacion !== '') {
        $nombreCompleto .= " - " . $presentacion;
    }
    if ($saborColor !== '') {
        $nombreCompleto .= " (" . $saborColor . ")";
    }

    // Limpiar el precio de venta por si viene formateado
    $precioBruto = $f["precioventa"] ?? $f["precio"] ?? 0;
    if (is_string($precioBruto)) {
        $precioBruto = preg_replace('/[^0-9.]/', '', str_replace(',', '.', $precioBruto));
    }

    $productos[] = [
        "id" => $id,
        "nombre" => $nombreCompleto,
        "cat" => $f["categoria"] ?? $f["tipo"] ?? 'General',
        "desc" => "SKU: " . ($f["skuinterno"] ?? 'N/D') . " | Presentación: " . ($presentacion !== '' ? $presentacion : 'Estándar') . ($saborColor !== '' ? " | Sabor/Color: $saborColor" : ""),
        "precio" => (float)$precioBruto,
        "img" => $f["imagenurl"] ?? $f["imagen"] ?? $f["foto"] ?? "IMG",
        "stock" => (int)($f["stockactual"] ?? $f["stock"] ?? 10)
    ];
}

echo json_encode($productos, JSON_UNESCAPED_UNICODE);
?>