<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

$url_lauti = "http://100.93.175.113:8000";
$archivo_meta = __DIR__ . "/catalogo_web.json";

$meta_web = file_exists($archivo_meta) ? json_decode(file_get_contents($archivo_meta), true) : [];
if (!is_array($meta_web)) $meta_web = [];
$variantes_meta = $meta_web['variantes'] ?? [];
$productos_img = $meta_web['productos_img'] ?? [];
$eliminados = $meta_web['eliminados'] ?? [];
$mas_vendidos = (array)($meta_web['mas_vendidos'] ?? []);

$json_lauti = @file_get_contents($url_lauti);

if ($json_lauti === FALSE) {
    echo json_encode([
        "error" => "No se pudo conectar a la API de Lauti en $url_lauti",
        "productos" => [],
        "destacados_ids" => $mas_vendidos
    ]);
    exit;
}

$datos = json_decode($json_lauti, true);

if (!is_array($datos)) {
    echo json_encode([
        "error" => "Formato no válido recibido de la API",
        "productos" => [],
        "destacados_ids" => $mas_vendidos
    ]);
    exit;
}

$productos_finales = [];

foreach ($datos as $index => $fila) {
    $f = array_change_key_case($fila, CASE_LOWER);
    $id = (int)($f["varianteid"] ?? $f["id"] ?? ($index + 1));

    if (isset($eliminados[$id])) continue;

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

    if (isset($variantes_meta[$id])) {
        $ov = $variantes_meta[$id];
        if (isset($ov['presentacion'])) $pres = $ov['presentacion'];
        if (isset($ov['sabor'])) $sabor = $ov['sabor'];
        if (isset($ov['precio'])) $precio = (float)$ov['precio'];
    }

    $clave_prod = mb_strtolower($nombre . "|" . $marca, 'UTF-8');
    $img = $variante_meta[$id]['img'] ?? ($productos_img[$clave_prod] ?? '');

    $productos_finales[] = [
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

echo json_encode([
    "productos" => $productos_finales,
    "destacados_ids" => $mas_vendidos
], JSON_UNESCAPED_UNICODE);
?>