<?php
session_start();
require_once '../models/MercadoPago.php';

if (!isset($_SESSION['usuario'], $_SESSION['id_usuario'])) {
    header("Location: ../login.php?msg=Debes iniciar sesión");
    exit;
}

$id_pedido  = (int) ($_GET['external_reference'] ?? 0);
$payment_id = (string) ($_GET['payment_id'] ?? ($_GET['collection_id'] ?? ''));

if ($id_pedido <= 0) {
    header("Location: mis_pedidos.php");
    exit;
}

if (!preg_match('/^\d{1,20}$/', $payment_id)) {
    $_SESSION['mp_flash'] = ['tipo' => 'error', 'texto' => 'El pago no se completó. Si querés, podés intentarlo de nuevo desde acá.'];
    header("Location: pedido_confirmado.php?id=" . $id_pedido);
    exit;
}

$r = (new MercadoPago())->confirmarRetorno($id_pedido, (int) $_SESSION['id_usuario'], $payment_id);

if (empty($r['ok'])) {
    $_SESSION['mp_flash'] = ['tipo' => 'error', 'texto' => $r['error'] ?? 'No pudimos confirmar el pago.'];
} elseif ($r['estado'] === 'approved') {
    $_SESSION['mp_flash'] = ['tipo' => 'ok', 'texto' => '¡Pago aprobado! Te enviamos el comprobante por correo.'];
} elseif (in_array($r['estado'], ['pending', 'in_process', 'authorized'], true)) {
    $_SESSION['mp_flash'] = ['tipo' => 'ok', 'texto' => 'Tu pago está pendiente de acreditación. Te avisamos apenas se confirme.'];
} else {
    $_SESSION['mp_flash'] = ['tipo' => 'error', 'texto' => 'El pago fue rechazado. Podés intentar de nuevo con otra tarjeta u otro medio.'];
}

header("Location: pedido_confirmado.php?id=" . $id_pedido);
exit;