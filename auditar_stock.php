<?php
header('Content-Type: text/html; charset=utf-8');

// Consultamos al servidor Python de Lauti por su IP de Tailscale
$url_lauti = "http://100.93.175.113:8000";
$json_lauti = @file_get_contents($url_lauti);

if ($json_lauti === FALSE) {
    die("<h2 style='color:red;'>❌ Error: No se pudo conectar al Python de Lauti en $url_lauti.</h2>");
}

$datos = json_decode($json_lauti, true);

if (!is_array($datos)) {
    die("<h2 style='color:red;'>❌ Error: El JSON recibido desde Python no es válido.</h2>");
}

$total_recibidos = count($datos);
echo "<h1>🔍 Auditoría de Inventario (Supplex)</h1>";
echo "<p>Total de registros devueltos por el script de Python de Lauti: <strong>{$total_recibidos}</strong></p>";

$validos = 0;
$anomalos = 0;

echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%; font-family: sans-serif; font-size: 0.9rem;'>";
echo "<tr style='background: #111; color: #fff;'><th>#</th><th>VarianteId</th><th>Producto / Nombre</th><th>Marca</th><th>Categoría</th><th>PrecioVenta</th><th>Stock</th><th>Estado / Alerta</th></tr>";

foreach ($datos as $index => $fila) {
    $f = array_change_key_case($fila, CASE_LOWER);
    
    $id = $f["varianteid"] ?? $f["id"] ?? "N/D";
    $nombre = $f["nombre"] ?? $f["nombreproducto"] ?? $f["producto"] ?? 'Sin nombre';
    $marca = $f["marca"] ?? 'Sin marca';
    $cat = $f["categoria"] ?? 'Sin categoría';
    $precio = $f["precioventa"] ?? $f["precio"] ?? 0;
    $stock = $f["stockactual"] ?? $f["stock"] ?? 0;

    $alertas = [];
    if (empty($nombre) || $nombre === 'Sin nombre') $alertas[] = "Falta Nombre";
    if ($precio <= 0) $alertas[] = "Precio 0 o Nulo";
    if (!isset($f["varianteid"])) $alertas[] = "Falta VarianteId";

    $colorFila = "";
    if (count($alertas) > 0) {
        $anomalos++;
        $colorFila = "style='background: #fee2e2;'"; // Rojo claro para anomalías
        $estado = "<span style='color: red; font-weight: bold;'>⚠️ " . implode(", ", $alertas) . "</span>";
    } else {
        $validos++;
        $estado = "<span style='color: green;'>✔ OK</span>";
    }

    echo "<tr {$colorFila}>";
    echo "<td>" . ($index + 1) . "</td>";
    echo "<td>{$id}</td>";
    echo "<td><strong>{$nombre}</strong></td>";
    echo "<td>{$marca}</td>";
    echo "<td>{$cat}</td>";
    echo "<td>$ " . number_format((float)$precio, 2, ',', '.') . "</td>";
    echo "<td>{$stock}</td>";
    echo "<td>{$estado}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr style='margin: 20px 0;'>";
echo "<h3>📊 Resumen de la Auditoría:</h3>";
echo "<ul>";
echo "<li>Registros correctos: <strong>{$validos}</strong></li>";
echo "<li>Registros con advertencias/anomalías: <strong style='color:red;'>{$anomalos}</strong></li>";
echo "</ul>";
?>