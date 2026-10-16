<?php
require 'conexion.php'; // Tu archivo de conexión a la BD
header('Content-Type: application/json');

// 1. Recibir los datos de JavaScript
$datos = json_decode(file_get_contents('php://input'), true);

if (!$datos) {
    echo json_encode(["success" => false, "error" => "No llegaron datos al servidor"]);
    exit;
}

$orderId = $datos['orderId'];
$nombre = $datos['nombre'];
$direccion = $datos['direccion'];
$cupon = $datos['cupon'];
$subtotal = $datos['subtotal'];
$descuento = $datos['descuento'];
$total = $datos['total'];
$articulos = $datos['articulos'];

// 2. Guardar en la tabla principal 'pedidos'
$stmt = $conn->prepare("INSERT INTO pedidos (codigo_pedido, cliente_nombre, cliente_direccion, cupon_aplicado, subtotal, descuento, total) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssddd", $orderId, $nombre, $direccion, $cupon, $subtotal, $descuento, $total);
$stmt->execute();
$pedido_id = $stmt->insert_id; // Obtenemos el ID que se acaba de crear
$stmt->close();

// 3. Guardar en 'pedido_detalles' y preparar las columnas para tu Excel
$lineas_excel = [];
$lineas_excel[] = ["CANTIDAD", "PRODUCTO", "PRECIO UNIT.", "SUBTOTAL ITEM"];

$stmt_det = $conn->prepare("INSERT INTO pedido_detalles (pedido_id, producto_nombre, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");

foreach ($articulos as $item) {
    $prod_nombre = $item['nombre'];
    $cant = $item['cantidad'];
    $precio_u = $item['precio'];
    $sub_item = $cant * $precio_u;
    
    $stmt_det->bind_param("isid", $pedido_id, $prod_nombre, $cant, $precio_u);
    $stmt_det->execute();

    $lineas_excel[] = [$cant, $prod_nombre, $precio_u, $sub_item];
}
$stmt_det->close();

// Agregar los totales al final de tu archivo Excel
$lineas_excel[] = ["", "", "", ""]; // Espacio en blanco
$lineas_excel[] = ["", "", "SUBTOTAL:", $subtotal];
if ($descuento > 0) {
    $lineas_excel[] = ["", "", "DESCUENTO ($cupon):", -$descuento];
}
$lineas_excel[] = ["", "", "TOTAL FINAL:", $total];

// 4. FABRICAR Y GUARDAR EL ARCHIVO EXCEL (CSV) EN EL SERVIDOR
// Creamos la carpeta automáticamente si no existe
if (!file_exists('ventas_excel')) {
    mkdir('ventas_excel', 0777, true);
}
$ruta_archivo = "ventas_excel/{$orderId}_Supplex.csv";

$archivo = fopen($ruta_archivo, 'w');
// Esto fuerza a Excel a leer perfectamente los acentos en español y símbolos como la 'ñ'
fputs($archivo, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

// Escribimos el encabezado del archivo
fputcsv($archivo, ["SUPPLEX - COMPROBANTE DE VENTA"]);
fputcsv($archivo, ["ID Pedido:", $orderId, "Fecha:", date("Y-m-d H:i:s")]);
fputcsv($archivo, ["Cliente:", $nombre, "Dirección:", $direccion]);
fputcsv($archivo, [""]); 

// Escribimos todos los productos (separados por punto y coma para el Excel en español)
foreach ($lineas_excel as $linea) {
    fputcsv($archivo, $linea, ";"); 
}
fclose($archivo);

// 5. ¡Todo un éxito! Le avisamos a JS que ya puede abrir WhatsApp
echo json_encode(["success" => true]);
$conn->close();
?>