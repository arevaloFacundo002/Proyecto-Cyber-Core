 <?php
session_start();
require_once '../models/Pedido.php';

if (!isset($_SESSION['usuario'], $_SESSION['id_usuario'])) {
    header("Location: ../login.php?msg=Debes iniciar sesión");
    exit;
}

$id_pedido = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id_pedido <= 0) {
    header("Location: ../home.php");
    exit;
}

$modelo = new Pedido();
$pedido = $modelo->obtenerPedido($id_pedido, (int) $_SESSION['id_usuario']);

// Solo puede verlo el dueño del pedido
if (!$pedido) {
    header("Location: ../home.php");
    exit;
}

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function plata($valor)
{
    return '$' . number_format((float) $valor, 2, ',', '.');
}

$subtotal = 0.0;
foreach ($pedido['items'] as $it) {
    $subtotal += (float) $it['subtotal_final'];
}
$envio = round((float) $pedido['monto_total'] - $subtotal, 2);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedido confirmado - CyberCore</title>

<link href="../css/theme.css" rel="stylesheet">
<link href="../css/tienda.css" rel="stylesheet">
</head>
<body>

<header class="tienda-header">
    <div class="logo">CYBERCORE</div>
    <nav>
        <a href="../home.php">Inicio</a>
        <a href="carrito.php">🛒 Carrito</a>
        <a class="logout" href="../logout.php">Cerrar sesión</a>
    </nav>
</header>

<div class="tienda-container">

    <h1>¡Gracias por tu compra!</h1>

    <div class="aviso aviso-ok centrado">
        Tu pedido <strong>#<?= (int) $pedido['id_pedidos'] ?></strong> quedó registrado.
    </div>

    <div class="caja">
        <h2>Detalle</h2>
        <table class="tabla-compra">
            <tr>
                <th>Producto</th>
                <th class="num">Precio</th>
                <th class="num">Cant.</th>
                <th class="num">Subtotal</th>
            </tr>
            <?php foreach ($pedido['items'] as $it): ?>
            <tr>
                <td><?= e($it['nombre']) ?></td>
                <td class="num"><?= plata($it['precio_unitario_base']) ?></td>
                <td class="num"><?= (int) $it['cantidad'] ?></td>
                <td class="num"><?= plata($it['subtotal_final']) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="3" class="num texto-suave">Subtotal</td>
                <td class="num"><?= plata($subtotal) ?></td>
            </tr>
            <tr>
                <td colspan="3" class="num texto-suave">Envío</td>
                <td class="num"><?= plata($envio) ?></td>
            </tr>
            <tr class="fila-total">
                <td colspan="3" class="num">Total</td>
                <td class="num"><?= plata($pedido['monto_total']) ?></td>
            </tr>
        </table>
    </div>

    <div class="caja">
        <h2>Envío y pago</h2>
        <p>
            <strong>Dirección:</strong> <?= e($pedido['direccion_entrega']) ?><br>
            <strong>Transporte:</strong> <?= e($pedido['empresa_transporte']) ?>
            (<?= e($pedido['estado_envio']) ?>)<br>
            <?php if (!empty($pedido['fecha_estimada_entrega'])): ?>
                <strong>Llegada estimada:</strong>
                <?= e(date('d/m/Y', strtotime($pedido['fecha_estimada_entrega']))) ?><br>
            <?php endif; ?>
        </p>
        <p>
            <strong>Método de pago:</strong> <?= e($pedido['metodo_pago']) ?><br>
            <strong>Estado del pago:</strong> <?= e($pedido['estado_pagos']) ?><br>
            <strong>Referencia:</strong> <?= e($pedido['referencia_transaccion']) ?>
        </p>
    </div>

    <p class="centrado">
        <a class="btn-principal" href="../home.php">Seguir comprando</a>
    </p>

</div>

<script src="../js/theme-toggle.js"></script>

</body>
</html>