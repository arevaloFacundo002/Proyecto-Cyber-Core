<?php
session_start();
require_once '../models/Devolucion.php';

if (!isset($_SESSION['usuario'], $_SESSION['id_usuario'])) {
    header("Location: ../login.php?msg=Debes iniciar sesión");
    exit;
}

$id_devolucion = (int) ($_POST['id_devolucion'] ?? 0);
$id_pedido     = (int) ($_POST['id_pedido'] ?? 0);
$destino       = $id_pedido > 0 ? "mi_pedido.php?id=" . $id_pedido : "mis_pedidos.php";

function volver(string $tipo, string $texto, string $url): void
{
    $_SESSION['dev_flash'] = ['tipo' => $tipo, 'texto' => $texto];
    header("Location: " . $url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id_devolucion <= 0) {
    header("Location: mis_pedidos.php");
    exit;
}

$token = $_POST['csrf'] ?? '';
if (empty($_SESSION['csrf_devolucion']) || !hash_equals($_SESSION['csrf_devolucion'], $token)) {
    volver('error', 'La sesión del formulario venció. Probá de nuevo.', $destino);
}

$r = (new Devolucion())->informarEnvio(
    $id_devolucion,
    (int) $_SESSION['id_usuario'],
    (string) ($_POST['metodo_entrega'] ?? ''),
    (string) ($_POST['empresa_transporte'] ?? ''),
    (string) ($_POST['num_seguimiento'] ?? '')
);

if (!empty($r['ok'])) {
    volver('ok', 'Listo, avisamos al negocio. Cuando recibamos el producto te avisamos.', $destino);
}
volver('error', $r['error'] ?? 'No se pudo registrar el aviso.', $destino);