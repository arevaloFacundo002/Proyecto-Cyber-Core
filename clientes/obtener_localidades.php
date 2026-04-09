<?php
require_once "../conexion.php";
$dao = new UserDao();

header('Content-Type: application/json');

if (!isset($_GET['provincia'])) {
    echo json_encode([]);
    exit;
}

$id_provincia = intval($_GET['provincia']);

$localidades = $dao->obtener_localidades($id_provincia);

// Devuelve JSON
echo json_encode($localidades);
?>
