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
    // Convertimos todas las claves de la fila a minúsculas para leer sin importar mayúsculas
    $f = array_change_key_case($fila, CASE_LOWER);

    $id = $f["id"] ?? $f["id_producto"] ?? $f["idproducto"] ?? ($index + 1);
    $nombreBase = $f["nombre"] ?? $f["producto"] ?? $f["nombre_producto"] ?? 'Suplemento';
    $marca = $f["marca"] ?? $f["brand"] ?? $f["laboratorio"] ?? '';
    
    $nombreCompleto = ($marca !== '') ? "$nombreBase - $marca" : $nombreBase;
    $categoria = $f["tipo"] ?? $f["categoria"] ?? $f["rubro"] ?? ($marca !== '' ? $marca : 'General');
    $descripcion = $f["descripcion"] ?? $f["detalle"] ?? $f["presentacion"] ?? $f["peso"] ?? '';

    // Limpiar el precio por si viene como texto o con signo '$'
    $precioBruto = $f["precio"] ?? $f["precio_unitario"] ?? $f["precio_final"] ?? $f["valor"] ?? 0;
    if (is_string($precioBruto)) {
        $precioBruto = preg_replace('/[^0-9.]/', '', str_replace(',', '.', $precioBruto));
    }

    $productos[] = [
        "id" => $id,
        "nombre" => $nombreCompleto,
        "cat" => $categoria,
        "desc" => $descripcion !== '' ? $descripcion : "Suplemento $nombreCompleto ($categoria) de calidad garantizada.",
        "precio" => (float)$precioBruto,
        "img" => $f["imagen_url"] ?? $f["imagen"] ?? $f["foto"] ?? $f["url_imagen"] ?? "IMG",
        "stock" => (int)($f["stock"] ?? $f["cantidad"] ?? 10)
    ];
}

echo json_encode($productos, JSON_UNESCAPED_UNICODE);
?>