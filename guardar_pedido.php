<?php
header('Content-Type: application/json; charset=utf-8');

$datos = json_decode(file_get_contents('php://input'), true);

if (!$datos) {
    echo json_encode(["success" => false, "error" => "No llegaron datos al servidor"]);
    exit;
}

$orderId = $datos['orderId'] ?? ('PED-' . rand(10000, 99999));
$nombre = $datos['nombre'] ?? 'Cliente';
$direccion = $datos['direccion'] ?? 'Sin dirección';
$cupon = $datos['cupon'] ?? '';
$subtotal = (float)($datos['subtotal'] ?? 0);
$descuento = (float)($datos['descuento'] ?? 0);
$total = (float)($datos['total'] ?? 0);
$articulos = $datos['articulos'] ?? [];

// 1. Preparar las filas para el archivo Excel (CSV)
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

// 2. Crear y guardar el archivo Excel (CSV) en la carpeta ventas_excel/
if (!file_exists('ventas_excel')) {
    mkdir('ventas_excel', 0777, true);
}
$ruta_archivo = "ventas_excel/{$orderId}_Supplex.csv";

$archivo = fopen($ruta_archivo, 'w');
if ($archivo) {
    fputs($archivo, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 para acentos y ñ en Excel
    fputcsv($archivo, ["SUPPLEX - COMPROBANTE DE VENTA"], ";");
    fputcsv($archivo, ["ID Pedido:", $orderId, "Fecha:", date("Y-m-d H:i:s")], ";");
    fputcsv($archivo, ["Cliente:", $nombre, "Dirección:", $direccion], ";");
    fputcsv($archivo, [""], ";");

    foreach ($lineas_excel as $linea) {
        fputcsv($archivo, $linea, ";");
    }
    fclose($archivo);
}

echo json_encode(["success" => true, "archivo" => $ruta_archivo]);
?>