<?php
session_start();
require_once '../models/Pedido.php';

// Si algo sale mal volvemos al checkout SIN perder lo que la persona ya escribió.
function volverConError(string $mensaje): void
{
    $viejo = $_POST;
    unset($viejo['csrf']);

    $_SESSION['checkout_old']   = $viejo;
    $_SESSION['checkout_error'] = $mensaje;

    header("Location: checkout.php");
    exit;
}

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
    volverConError("La sesión del formulario venció. Intentá de nuevo.");
}

$carrito = $_SESSION['carrito'] ?? [];
if (empty($carrito)) {
    header("Location: carrito.php");
    exit;
}

$id_usuario = (int) $_SESSION['id_usuario'];
$id_metodo  = (int) ($_POST['metodo_pago'] ?? 0);

if ($id_metodo <= 0) {
    volverConError("Elegí un método de pago.");
}

$pedidos = new Pedido();

// Si es su primera compra, además de la entrega necesitamos sus datos personales y su dirección.
$esNuevo = ($pedidos->obtenerCliente($id_usuario) === null);

try {
    $datosEntrega = $pedidos->validarDatosEntrega($_POST);
    $datosCliente = $esNuevo ? $pedidos->validarDatosCliente($_POST) : null;
} catch (PedidoException $e) {
    volverConError($e->getMessage());
}

$resultado = $pedidos->crearPedido($id_usuario, $carrito, $id_metodo, $datosEntrega, $datosCliente);

if (!empty($resultado['ok'])) {

    // Pedido creado: vaciamos el carrito y renovamos el token
    unset($_SESSION['carrito'], $_SESSION['csrf_checkout'], $_SESSION['checkout_old']);

    header("Location: pedido_confirmado.php?id=" . (int) $resultado['id_pedido']);
    exit;
}

volverConError($resultado['error'] ?? "No pudimos procesar tu pedido.");