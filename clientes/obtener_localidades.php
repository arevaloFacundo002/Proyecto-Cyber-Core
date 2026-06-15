<?php
require_once "../models/tablas_maestras/Ubicacion.php";
$ubic = new Ubicacion();

header('Content-Type: application/json');

if (!isset($_GET['provincia'])) {
    echo json_encode([]);
    exit;
}

$id_provincia = intval($_GET['provincia']);

$localidades = $ubic->obtenerLocalidadesPorProvincia($id_provincia);

// Devuelve JSON
echo json_encode($localidades);
?>
