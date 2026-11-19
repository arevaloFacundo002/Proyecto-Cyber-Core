<?php
require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../models/Pedido.php';
require_once __DIR__ . '/../../models/Comprobante.php';
require_once __DIR__ . '/../../models/MercadoPago.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Solo administrador y empleados pueden gestionar pedidos
$rol = $_SESSION['rol'] ?? '';
if (!in_array($rol, ['administrador', 'empleado'], true)) {
    header("Location: ../../home.php");
    exit;
}

$accion = $_GET['accion'] ?? '';

if ($accion === 'cambiarEstado' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_pedido = (int) ($_POST['id_pedido'] ?? 0);
    $destino   = "../../views/pedidos/verPedido.php?id=" . $id_pedido;

    if ($id_pedido <= 0) {
        header("Location: ../../views/pedidos/listarPedidos.php");
        exit;
    }

    // Validación CSRF
    $token = $_POST['csrf'] ?? '';
    if (empty($_SESSION['csrf_admin_pedidos']) || !hash_equals($_SESSION['csrf_admin_pedidos'], $token)) {
        $_SESSION['pedido_flash'] = ['tipo' => 'error', 'texto' => 'La sesión del formulario venció. Probá de nuevo.'];
        header("Location: " . $destino);
        exit;
    }

    $nuevo_estado  = trim($_POST['nuevo_estado'] ?? '');
    if (isset($_POST['cancelar'])) {
        $nuevo_estado = 'Cancelado';
    } elseif (isset($_POST['solo_guardar'])) {
        $nuevo_estado = '';
    }
    $nota          = trim($_POST['nota'] ?? '');
    $transportista = trim($_POST['transportista'] ?? '');
    $tracking      = trim($_POST['tracking'] ?? '');
    $autor         = $_SESSION['usuario'] ?? 'admin';

    $modelo = new Pedido();

    // Si no se eligió estado nuevo, solo se guarda seguimiento/nota sobre el estado actual
    if ($nuevo_estado === '') {
        $actual = $modelo->obtenerPedidoAdmin($id_pedido);
        $nuevo_estado = $actual['estado'] ?? '';
    }

    $r = $modelo->cambiarEstado(
        $id_pedido,
        $nuevo_estado,
        $autor,
        $nota !== '' ? $nota : null,
        $transportista !== '' ? $transportista : null,
        $tracking !== '' ? $tracking : null
    );

    if (!empty($r['ok'])) {
        $modelo->tomarPedido($id_pedido, (int) ($_SESSION['id_usuario'] ?? 0));
        $texto = 'Pedido #' . $id_pedido . ' actualizado: ' . ($r['estado_nuevo'] ?? $nuevo_estado) . '.';
        if (!empty($r['stock_devuelto'])) {
            $texto .= ' Se devolvieron ' . (int) $r['stock_devuelto'] . ' unidad(es) al stock.';
        }
        $_SESSION['pedido_flash'] = ['tipo' => 'ok', 'texto' => $texto];
    } else {
        $_SESSION['pedido_flash'] = ['tipo' => 'error', 'texto' => $r['error'] ?? 'No se pudo actualizar el pedido.'];
    }

    header("Location: " . $destino);
    exit;
}

if ($accion === 'aprobarPago' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_pedido = (int) ($_POST['id_pedido'] ?? 0);
    $destino   = "../../views/pedidos/verPedido.php?id=" . $id_pedido;

    $token = $_POST['csrf'] ?? '';
    if ($id_pedido <= 0 || empty($_SESSION['csrf_admin_pedidos']) || !hash_equals($_SESSION['csrf_admin_pedidos'], $token)) {
        $_SESSION['pedido_flash'] = ['tipo' => 'error', 'texto' => 'La sesión del formulario venció. Probá de nuevo.'];
        header("Location: " . ($id_pedido > 0 ? $destino : "../../views/pedidos/listarPedidos.php"));
        exit;
    }

    $modelo = new Pedido();
    $r = $modelo->aprobarPago($id_pedido, $_SESSION['usuario'] ?? 'admin');

    if (!empty($r['ok'])) {
        $modelo->tomarPedido($id_pedido, (int) ($_SESSION['id_usuario'] ?? 0));
        $texto = 'Pago del pedido #' . $id_pedido . ' aprobado.';
        if (!empty($r['factura_nueva'])) {
            $texto .= ' Se emitió la factura ' . $r['factura'] . '.';
        }
        $_SESSION['pedido_flash'] = ['tipo' => 'ok', 'texto' => $texto];
    } else {
        $_SESSION['pedido_flash'] = ['tipo' => 'error', 'texto' => $r['error'] ?? 'No se pudo aprobar el pago.'];
    }

    header("Location: " . $destino);
    exit;
}

if ($accion === 'verificarMercadoPago' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_pedido = (int) ($_POST['id_pedido'] ?? 0);
    $destino   = "../../views/pedidos/verPedido.php?id=" . $id_pedido;

    $token = $_POST['csrf'] ?? '';
    if ($id_pedido <= 0 || empty($_SESSION['csrf_admin_pedidos']) || !hash_equals($_SESSION['csrf_admin_pedidos'], $token)) {
        $_SESSION['pedido_flash'] = ['tipo' => 'error', 'texto' => 'La sesión del formulario venció. Probá de nuevo.'];
        header("Location: " . ($id_pedido > 0 ? $destino : "../../views/pedidos/listarPedidos.php"));
        exit;
    }

    $r = (new MercadoPago())->verificarPedido($id_pedido);

    if (empty($r['ok'])) {
        $_SESSION['pedido_flash'] = ['tipo' => 'error', 'texto' => $r['error'] ?? 'No se pudo verificar el pago.'];
    } elseif ($r['estado'] === 'approved') {
        $_SESSION['pedido_flash'] = ['tipo' => 'ok', 'texto' => 'Mercado Pago confirmó el pago del pedido #' . $id_pedido . '. Quedó aprobado.'];
    } elseif (in_array($r['estado'], ['pending', 'in_process', 'authorized'], true)) {
        $_SESSION['pedido_flash'] = ['tipo' => 'ok', 'texto' => 'El pago todavía está en proceso en Mercado Pago. Probá de nuevo en unos minutos.'];
    } else {
        $_SESSION['pedido_flash'] = ['tipo' => 'error', 'texto' => 'Mercado Pago informa que el pago fue rechazado.'];
    }

    header("Location: " . $destino);
    exit;
}

if ($accion === 'rechazarComprobante' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_pedido = (int) ($_POST['id_pedido'] ?? 0);
    $destino   = "../../views/pedidos/verPedido.php?id=" . $id_pedido;

    $token = $_POST['csrf'] ?? '';
    if ($id_pedido <= 0 || empty($_SESSION['csrf_admin_pedidos']) || !hash_equals($_SESSION['csrf_admin_pedidos'], $token)) {
        $_SESSION['pedido_flash'] = ['tipo' => 'error', 'texto' => 'La sesión del formulario venció. Probá de nuevo.'];
        header("Location: " . ($id_pedido > 0 ? $destino : "../../views/pedidos/listarPedidos.php"));
        exit;
    }

    $r = (new Comprobante())->rechazar($id_pedido, $_POST['motivo'] ?? '');

    $_SESSION['pedido_flash'] = !empty($r['ok'])
        ? ['tipo' => 'ok', 'texto' => 'Comprobante rechazado. El cliente recibió el aviso para subir uno nuevo.']
        : ['tipo' => 'error', 'texto' => $r['error'] ?? 'No se pudo rechazar el comprobante.'];

    header("Location: " . $destino);
    exit;
}

header("Location: ../../views/pedidos/listarPedidos.php");
exit;