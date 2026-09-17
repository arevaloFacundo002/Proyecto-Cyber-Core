<?php
$host     = "localhost";
$dbname   = "cyber_core";
$user     = "root";
$password = "Itati2005";
 
// Creamos la conexión mediante mysqli
$conexion = new mysqli($host, $user, $password, $dbname);
 
if ($conexion->connect_error) {
    die("Error al conectar con la base de datos: " . $conexion->connect_error);
}
 
$conexion->set_charset("utf8mb4");
?>