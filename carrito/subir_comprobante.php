<?php
session_start();
require_once '../models/Comprobante.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: mis_pedidos.php");
    exit;
}

if (!isset($_SESSION['usuario'], $_SESSION['id_usuario'])) {
    header("Location: ../login.php?msg=Debes iniciar sesión");
    exit;
}

$id_pedido = (int) ($_POST['id_pedido'] ?? 0);
$volver    = $id_pedido > 0 ? "mi_pedido.php?id=" . $id_pedido . "#comprobante" : "mis_pedidos.php";

$token = $_POST['csrf'] ?? '';
if (empty($_SESSION['csrf_comprobante']) || !hash_equals($_SESSION['csrf_comprobante'], $token)) {
    $_SESSION['comp_flash'] = ['tipo' => 'error', 'texto' => 'La sesión del formulario venció. Probá de nuevo.'];
    header("Location: " . $volver);
    exit;
}

$r = (new Comprobante())->subir($id_pedido, (int) $_SESSION['id_usuario'], $_FILES['comprobante'] ?? ['error' => UPLOAD_ERR_NO_FILE]);

$_SESSION['comp_flash'] = !empty($r['ok'])
    ? ['tipo' => 'ok', 'texto' => 'Recibimos tu comprobante. Lo vamos a revisar y te avisamos.']
    : ['tipo' => 'error', 'texto' => $r['error'] ?? 'No se pudo subir el comprobante.'];

header("Location: " . $volver);
exit;