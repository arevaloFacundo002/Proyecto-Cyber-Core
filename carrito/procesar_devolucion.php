<?php
session_start();
require_once '../models/Devolucion.php';

if (!isset($_SESSION['usuario'], $_SESSION['id_usuario'])) {
    header("Location: ../login.php?msg=Debes iniciar sesión");
    exit;
}

$id_pedido = (int) ($_POST['id_pedido'] ?? 0);

function volver(string $tipo, string $texto, string $url): void
{
    $_SESSION['dev_flash'] = ['tipo' => $tipo, 'texto' => $texto];
    header("Location: " . $url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id_pedido <= 0) {
    header("Location: mis_pedidos.php");
    exit;
}

$token = $_POST['csrf'] ?? '';
if (empty($_SESSION['csrf_devolucion']) || !hash_equals($_SESSION['csrf_devolucion'], $token)) {
    volver('error', 'La sesión del formulario venció. Probá de nuevo.', "solicitar_devolucion.php?id=" . $id_pedido);
}

$cantidades = [];
foreach ((array) ($_POST['cant'] ?? []) as $id_detalle => $cant) {
    $cantidades[(int) $id_detalle] = max(0, (int) $cant);
}

$r = (new Devolucion())->solicitar($id_pedido, (int) $_SESSION['id_usuario'], (string) ($_POST['motivo'] ?? ''), $cantidades);

if (!empty($r['ok'])) {
    volver('ok', 'Recibimos tu pedido de devolución #' . $r['id_devolucion'] . '. Lo revisamos y te avisamos.', "mi_pedido.php?id=" . $id_pedido);
}

volver('error', $r['error'] ?? 'No se pudo registrar la devolución.', "solicitar_devolucion.php?id=" . $id_pedido);