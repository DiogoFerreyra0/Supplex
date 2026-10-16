<?php
// 1. Se conecta a la base de tu amigo
require 'conexion.php'; 

// 2. Busca TODOS los productos
$resultado = $conn->query("SELECT * FROM productos");

$productos = array();

// 3. Los empaqueta uno por uno
while($fila = $resultado->fetch_assoc()) {
    $productos[] = array(
        "id" => $fila["id"],
        "nombre" => $fila["nombre"] . " - " . $fila["marca"], // Fusionamos Nombre y Marca
        "cat" => $fila["tipo"],                               // Usamos "tipo" como la categoría
        "desc" => $fila["descripcion"],
        "precio" => (int)$fila["precio"],
        "img" => "IMG", // Acá irá la URL de la foto en el futuro
        "stock" => $fila["stock"]
    );
}

// 4. Se los envía a tu web en formato JSON (un idioma que JavaScript entiende)
header('Content-Type: application/json');
echo json_encode($productos);
?>