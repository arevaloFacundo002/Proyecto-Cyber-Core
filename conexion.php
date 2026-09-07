<?php
// Datos de configuración de tu servidor local
$host     = "localhost";
 $dbname = "cyber_core";
$user     = "root";     // Usuario por defecto en XAMPP/WAMP
$password = "";         // Contraseña (suele ser vacía en XAMPP)

try {
    // Creamos la conexión mediante PDO
    $conexion = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    
    // Configuración para que muestre errores claros en caso de fallas
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al conectar con la base de datos: " . $e->getMessage());
}
?>