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

// Si el script de Lauti devuelve un aviso (por ejemplo, cómo se llaman sus tablas), lo mostramos directo
if (isset($datos["aviso"]) || isset($datos["error_python"])) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

$productos = [];

foreach ($datos as $fila) {
    $productos[] = [
        "id" => $fila["id"] ?? $fila["ID"] ?? 0,
        "nombre" => ($fila["nombre"] ?? $fila["Nombre"] ?? 'Sin nombre') . (isset($fila["marca"]) ? " - " . $fila["marca"] : ""),
        "cat" => $fila["tipo"] ?? $fila["categoria"] ?? 'General',
        "desc" => $fila["descripcion"] ?? '',
        "precio" => (float)($fila["precio"] ?? $fila["Precio"] ?? 0),
        "img" => "IMG",
        "stock" => (int)($fila["stock"] ?? 10)
    ];
}

echo json_encode($productos, JSON_UNESCAPED_UNICODE);
?>