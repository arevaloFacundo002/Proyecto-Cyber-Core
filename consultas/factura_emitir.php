<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario']) ||
   ($_SESSION['rol'] != "administrador" && $_SESSION['rol'] != "empleado")) {
    header("Location: ../home.php");
    exit;
}
require_once __DIR__ . '/../models/Pedido.php';

function volver(string $tipo, string $texto, string $url = 'facturas.php?tab=pendientes'): void {
    $_SESSION['factura_flash'] = ['tipo' => $tipo, 'texto' => $texto];
    header("Location: " . $url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') volver('error', 'Acción no válida.');

$token = $_POST['csrf'] ?? '';
if (empty($_SESSION['csrf_facturas']) || !hash_equals($_SESSION['csrf_facturas'], $token)) {
    volver('error', 'La sesión del formulario venció. Probá de nuevo.');
}

$id_pedido = (int) ($_POST['id_pedido'] ?? 0);
if ($id_pedido <= 0) volver('error', 'Pedido inválido.');

$r = (new Pedido())->emitirFactura($id_pedido);

if ($r['ok']) {
    volver('ok', "Factura {$r['numero']} emitida para el pedido #$id_pedido.", 'factura_ver.php?id=' . $r['id_factura']);
}
volver('error', $r['error'] ?? 'No se pudo emitir la factura.');