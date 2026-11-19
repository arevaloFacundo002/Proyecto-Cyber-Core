<?php
session_start();
require_once '../models/MercadoPago.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: mis_pedidos.php");
    exit;
}

if (!isset($_SESSION['usuario'], $_SESSION['id_usuario'])) {
    header("Location: ../login.php?msg=Debes iniciar sesión");
    exit;
}

$id_pedido = (int) ($_POST['id_pedido'] ?? 0);
$volver    = $id_pedido > 0 ? "mi_pedido.php?id=" . $id_pedido : "mis_pedidos.php";

$token = $_POST['csrf'] ?? '';
if (empty($_SESSION['csrf_mp']) || !hash_equals($_SESSION['csrf_mp'], $token)) {
    $_SESSION['mp_flash'] = ['tipo' => 'error', 'texto' => 'La sesión del formulario venció. Probá de nuevo.'];
    header("Location: " . $volver);
    exit;
}

$r = (new MercadoPago())->iniciarPago($id_pedido, (int) $_SESSION['id_usuario'], MercadoPago::urlRetorno());

if (!empty($r['ok'])) {
    header("Location: " . $r['url']);
    exit;
}

$_SESSION['mp_flash'] = ['tipo' => 'error', 'texto' => $r['error'] ?? 'No pudimos abrir Mercado Pago.'];
header("Location: " . $volver);
exit;