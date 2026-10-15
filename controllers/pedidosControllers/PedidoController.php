<?php
require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../models/Pedido.php';

$accion = $_GET['accion'] ?? '';

if ($accion === 'cambiarEstado' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_pedido    = (int) ($_POST['id_pedido'] ?? 0);
    $nuevo_estado = $_POST['nuevo_estado'] ?? '';

    $pedidos = new Pedido();

    if ($id_pedido > 0) {
        $pedidos->cambiarEstado($id_pedido, $nuevo_estado);
    }

    header("Location: ../../views/pedidos/verPedido.php?id=" . $id_pedido . "&mensaje=actualizado");
    exit;
}

// Si se entra sin una acción válida, volvemos al listado.
header("Location: ../../views/pedidos/listarPedidos.php");
exit;