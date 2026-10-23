<?php
// Reemplazá 100.X.X.X por la IP de Tailscale de la máquina de Lau
$serverName = "100.93.175.113,1433";
$database = "ComparadorSuplementos";
$uid = "Diogof";
$pwd = "Diogoferreyra271352992";

try {
    // Para ODBC Driver 18 es necesario agregar TrustServerCertificate y especificar el driver
    $conn = new PDO("sqlsrv:server=$serverName;Database=$database;TrustServerCertificate=true;Encrypt=optional", $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    die(json_encode([
        "error" => "No se pudo conectar a SQL Server",
        "detalle" => $e->getMessage()
    ]));
}
?>
