<?php
session_start();
require_once __DIR__ . '/../models/Favorito.php';

header('Content-Type: application/json; charset=utf-8');

$rol = $_SESSION['rol'] ?? '';
if (!isset($_SESSION['id_usuario']) || !in_array($rol, ['cliente', 'usuario'], true)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'login']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || !isset($_SESSION['csrf_fav'], $_POST['csrf'])
    || !hash_equals($_SESSION['csrf_fav'], (string) $_POST['csrf'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'csrf']);
    exit;
}

$id_producto = (int) ($_POST['id_producto'] ?? 0);
$id_usuario  = (int) $_SESSION['id_usuario'];
$fav = new Favorito();

$estado = $id_producto > 0 ? $fav->alternar($id_usuario, $id_producto) : null;
if ($estado === null) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'producto']);
    exit;
}

echo json_encode(['ok' => true, 'favorito' => $estado, 'total' => $fav->contar($id_usuario)]);