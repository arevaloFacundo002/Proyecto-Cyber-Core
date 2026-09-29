 <?php
session_start();
require_once '../models/Pedido.php';

// Solo se entra enviando el formulario del checkout
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit;
}

if (!isset($_SESSION['usuario'], $_SESSION['id_usuario'])) {
    header("Location: ../login.php?msg=Debes iniciar sesión");
    exit;
}

$rol = $_SESSION['rol'] ?? null;
if ($rol === 'administrador' || $rol === 'empleado') {
    header("Location: ../inicio.php");
    exit;
}

// Token del formulario
$tokenSesion = $_SESSION['csrf_checkout'] ?? '';
$tokenForm   = $_POST['csrf'] ?? '';

if ($tokenSesion === '' || !hash_equals($tokenSesion, $tokenForm)) {
    $_SESSION['checkout_error'] = "La sesión del formulario venció. Intentá de nuevo.";
    header("Location: checkout.php");
    exit;
}

$carrito = $_SESSION['carrito'] ?? [];
if (empty($carrito)) {
    header("Location: carrito.php");
    exit;
}

$id_metodo = (int) ($_POST['metodo_pago'] ?? 0);
if ($id_metodo <= 0) {
    $_SESSION['checkout_error'] = "Elegí un método de pago.";
    header("Location: checkout.php");
    exit;
}

$pedidos   = new Pedido();
$resultado = $pedidos->crearPedido((int) $_SESSION['id_usuario'], $carrito, $id_metodo);

if (!empty($resultado['ok'])) {

    // Pedido creado: vaciamos el carrito y renovamos el token
    unset($_SESSION['carrito'], $_SESSION['csrf_checkout']);

    header("Location: pedido_confirmado.php?id=" . (int) $resultado['id_pedido']);
    exit;
}

$_SESSION['checkout_error'] = $resultado['error'] ?? "No pudimos procesar tu pedido.";
header("Location: checkout.php");
exit;