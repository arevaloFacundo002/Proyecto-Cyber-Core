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

$r = (new MercadoPago())->verificarPedido($id_pedido, (int) $_SESSION['id_usuario']);

if (empty($r['ok'])) {
    $_SESSION['mp_flash'] = ['tipo' => 'error', 'texto' => $r['error'] ?? 'No pudimos verificar el pago.'];
} elseif ($r['estado'] === 'approved') {
    $_SESSION['mp_flash'] = ['tipo' => 'ok', 'texto' => '¡Pago aprobado! Te enviamos el comprobante por correo.'];
} elseif (in_array($r['estado'], ['pending', 'in_process', 'authorized'], true)) {
    $_SESSION['mp_flash'] = ['tipo' => 'ok', 'texto' => 'Tu pago todavía está en proceso en Mercado Pago. Probá verificar de nuevo en unos minutos.'];
} else {
    $_SESSION['mp_flash'] = ['tipo' => 'error', 'texto' => 'El pago fue rechazado. Podés intentar de nuevo con otra tarjeta u otro medio.'];
}

header("Location: " . $volver);
exit;