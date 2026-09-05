<?php

require_once '../models/tablas_maestras/ModeloProducto.php';

$modelo = new ModeloProducto();

$id_marca = isset($_GET['id_marca'])
    ? (int) $_GET['id_marca']
    : 0;

if ($id_marca <= 0) {
    echo json_encode([]);
    exit();
}

$modelos = $modelo->listarPorMarca($id_marca);

header('Content-Type: application/json');

echo json_encode($modelos);