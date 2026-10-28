<?php
require_once __DIR__ . '/config/env.php';

$host     = env_valor('DB_HOST', 'localhost');
$dbname   = env_valor('DB_NAME', 'cyber_core');
$user     = env_valor('DB_USER', 'root');
$password = env_valor('DB_PASS', '');

// Creamos la conexión mediante mysqli
$conexion = new mysqli($host, $user, $password, $dbname);

if ($conexion->connect_error) {
    die("Error al conectar con la base de datos: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");
?>