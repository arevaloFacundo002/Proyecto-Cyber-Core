<?php
require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../models/MovimientoFactura.php';

$rol = $_SESSION['rol'] ?? '';
if (!in_array($rol, ['administrador', 'empleado'], true)) {
    header("Location: ../../home.php");
    exit;
}

$destino = "../../views/movimientos/cargarFactura.php";

function volver(string $tipo, string $texto, string $url): void
{
    $_SESSION['factura_mov_flash'] = ['tipo' => $tipo, 'texto' => $texto];
    header("Location: " . $url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . $destino);
    exit;
}

$token = $_POST['csrf'] ?? '';
if (empty($_SESSION['csrf_factura_mov']) || !hash_equals($_SESSION['csrf_factura_mov'], $token)) {
    volver('error', 'La sesión del formulario venció. Probá de nuevo.', $destino);
}

$modelo = new MovimientoFactura();
$v = $modelo->validar($_POST);

if (empty($v['ok'])) {
    $_SESSION['factura_mov_old'] = $_POST;
    volver('error', $v['error'], $destino);
}

$r = $modelo->crear($v, (int) ($_SESSION['id_usuario'] ?? 0));

if (empty($r['ok'])) {
    $_SESSION['factura_mov_old'] = $_POST;
    volver('error', $r['error'], $destino);
}

volver('ok', 'Factura cargada (N° interno ' . $r['id'] . '): ' . $r['lineas'] . ' producto(s), total $' . number_format($r['total'], 2, ',', '.') . '. El stock ya se actualizó.', $destino);