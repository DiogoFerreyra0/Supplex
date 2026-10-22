<?php
require 'conexion.php'; 

try {
    // Consulta de solo lectura (SELECT) a la base ComparadorSuplementos
    $stmt = $conn->query("SELECT * FROM productos");
    $productos = array();

    while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $productos[] = array(
            "id" => $fila["id"] ?? 0,
            "nombre" => ($fila["nombre"] ?? 'Sin nombre') . " - " . ($fila["marca"] ?? ''),
            "cat" => $fila["tipo"] ?? 'General',
            "desc" => $fila["descripcion"] ?? '',
            "precio" => (float)($fila["precio"] ?? 0),
            "img" => "IMG",
            "stock" => (int)($fila["stock"] ?? 10)
        );
    }

    header('Content-Type: application/json');
    echo json_encode($productos);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(["error" => "Error en la consulta SQL: " . $e->getMessage()]);
}
?>