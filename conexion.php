<?php
// Datos del servidor SQL Server (SQLEXPRESS) de Lauti vía Tailscale
$serverName = "mi-bebe.tail2cdd95.ts.net\\SQLEXPRESS"; // Dominio Tailscale + instancia
$database = "ComparadorSuplementos";
$uid = "Diogof";
$pwd = "Diogoferreyra271352992";

try {
    // Conectamos usando PDO para SQL Server
    $conn = new PDO("sqlsrv:server=$serverName;Database=$database;TrustServerCertificate=true", $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    die(json_encode([
        "error" => "No se pudo conectar a SQL Server",
        "detalle" => $e->getMessage()
    ]));
}
?>