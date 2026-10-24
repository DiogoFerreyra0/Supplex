<?php
header('Content-Type: application/json; charset=utf-8');

$url_lauti = "http://100.93.175.113:8000";
$archivo_pedidos = __DIR__ . "/pedidos_web.json";
$carpeta_excel = __DIR__ . "/ventas_excel/";

if (!file_exists($carpeta_excel)) {
    mkdir($carpeta_excel, 0777, true);
}

$datos = json_decode(file_get_contents('php://input'), true);

if (!$datos) {
    echo json_encode(["success" => false, "error" => "No llegaron datos al servidor"]);
    exit;
}

$orderId = $datos['orderId'] ?? ('PED-' . rand(10000, 99999));
$nombre = trim($datos['nombre'] ?? 'Cliente');
$direccion = trim($datos['direccion'] ?? 'Sin dirección');
$email = trim($datos['email'] ?? 'Sin email');
$telefono = trim($datos['telefono'] ?? 'Sin teléfono');
$cupon = $datos['cupon'] ?? '';
$subtotal = (float)($datos['subtotal'] ?? 0);
$descuento = (float)($datos['descuento'] ?? 0);
$total = (float)($datos['total'] ?? 0);
$articulos = $datos['articulos'] ?? [];
$ahora = time();

$nombre_csv = "{$orderId}_Supplex.csv";
$ruta_archivo = $carpeta_excel . $nombre_csv;

$lineas_excel = [];
$lineas_excel[] = ["CANTIDAD", "PRODUCTO", "PRECIO UNIT.", "SUBTOTAL ITEM"];

foreach ($articulos as $item) {
    $prod_nombre = $item['nombre'] ?? 'Suplemento';
    $cant = (int)($item['cantidad'] ?? 1);
    $precio_u = (float)($item['precio'] ?? 0);
    $sub_item = $cant * $precio_u;
    $lineas_excel[] = [$cant, $prod_nombre, $precio_u, $sub_item];
}

$lineas_excel[] = ["", "", "", ""];
$lineas_excel[] = ["", "", "SUBTOTAL:", $subtotal];
if ($descuento > 0) {
    $lineas_excel[] = ["", "", "DESCUENTO ($cupon):", -$descuento];
}
$lineas_excel[] = ["", "", "TOTAL FINAL:", $total];

$archivo = fopen($ruta_archivo, 'w');
if ($archivo) {
    fputs($archivo, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($archivo, ["SUPPLEX - PRESUPUESTO / ORDEN DE COMPRA"], ";");
    fputcsv($archivo, ["ID Presupuesto:", $orderId, "Fecha:", date("Y-m-d H:i:s", $ahora)], ";");
    fputcsv($archivo, ["Estado Inicial:", "pendiente (Validez 25 minutos)"], ";");
    fputcsv($archivo, ["Cliente:", $nombre, "Dirección:", $direccion], ";");
    fputcsv($archivo, ["Email:", $email, "Teléfono:", $telefono], ";");
    fputcsv($archivo, [""], ";");

    foreach ($lineas_excel as $linea) {
        fputcsv($archivo, $linea, ";");
    }
    fclose($archivo);
}

$registro_pedidos = file_exists($archivo_pedidos) ? json_decode(file_get_contents($archivo_pedidos), true) : [];
if (!is_array($registro_pedidos)) $registro_pedidos = [];

foreach ($registro_pedidos as $cod => $ped) {
    $est = mb_strtolower(trim($ped['estado'] ?? 'pendiente'), 'UTF-8');
    $creado = (int)($ped['timestamp'] ?? 0);
    if ($est === 'pendiente' && ($ahora - $creado) >= 1500) {
        if (!empty($ped['archivo_csv']) && file_exists($carpeta_excel . $ped['archivo_csv'])) {
            @unlink($carpeta_excel . $ped['archivo_csv']);
        }
        unset($registro_pedidos[$cod]);
    }
}

$registro_pedidos[$orderId] = [
    "orderId" => $orderId,
    "nombre" => $nombre,
    "direccion" => $direccion,
    "email" => $email,
    "telefono" => $telefono,
    "cupon" => $cupon,
    "subtotal" => $subtotal,
    "descuento" => $descuento,
    "total" => $total,
    "articulos" => $articulos,
    "estado" => "pendiente",
    "fecha" => date("Y-m-d H:i:s", $ahora),
    "timestamp" => $ahora,
    "archivo_csv" => $nombre_csv
];

file_put_contents($archivo_pedidos, json_encode($registro_pedidos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$payload_lauti = [
    "accion" => "crear_presupuesto",
    "orderId" => $orderId,
    "nombre" => $nombre,
    "direccion" => $direccion,
    "email" => $email,
    "telefono" => $telefono,
    "cupon" => $cupon,
    "subtotal" => $subtotal,
    "descuento" => $descuento,
    "total" => $total,
    "articulos" => $articulos
];

if (function_exists('curl_init')) {
    $ch = curl_init($url_lauti);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload_lauti, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    curl_close($ch);
} else {
    $opciones = ['http' => ['method' => 'POST', 'content' => json_encode($payload_lauti), 'timeout' => 5]];
    @file_get_contents($url_lauti, false, stream_context_create($opciones));
}

echo json_encode([
    "success" => true,
    "orderId" => $orderId,
    "estado" => "pendiente"
]);
?>