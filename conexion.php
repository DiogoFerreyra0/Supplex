<?php
// Datos para tu servidor local XAMPP
$host = "localhost"; 
$user = "root";       // XAMPP usa 'root' por defecto
$password = "";       // XAMPP no tiene contraseña por defecto
$dbname = "supplex_db"; // El nombre que le pusiste a tu base en phpMyAdmin

$conn = new mysqli($host, $user, $password, $dbname);

// Forzar la codificación para que los acentos y la 'ñ' se vean bien
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    // Si falla, le avisamos a JavaScript con un error en formato JSON
    die(json_encode(["error" => "Falló la conexión: " . $conn->connect_error]));
}
?>