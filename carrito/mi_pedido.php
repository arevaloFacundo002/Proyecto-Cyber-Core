<?php
session_start();
require_once '../models/Pedido.php';
require_once __DIR__ . '/_helpers_pedidos.php';

if (!isset($_SESSION['usuario'], $_SESSION['id_usuario'])) {
    header("Location: ../login.php?msg=Debes iniciar sesión");
    exit;
}

$id_pedido = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$modelo    = new Pedido();
$pedido    = $id_pedido > 0 ? $modelo->obtenerPedido($id_pedido, (int) $_SESSION['id_usuario']) : null;

// Solo el dueño puede verlo
if (!$pedido) {
    header("Location: mis_pedidos.php");
    exit;
}

$subtotal = 0.0;
foreach ($pedido['items'] as $it) {
    $subtotal += (float) $it['subtotal_final'];
}
$envio = round((float) $pedido['monto_total'] - $subtotal, 2);
$info  = Pedido::ESTADOS[$pedido['estado']] ?? ['emoji' => '⚪', 'color' => '#999'];
$historial = array_reverse($pedido['historial']); // lo más nuevo primero
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedido #<?= (int) $pedido['id_pedidos'] ?> - CyberCore</title>
<link href="../css/theme.css" rel="stylesheet">
<link href="../css/tienda.css" rel="stylesheet">
<link href="../css/mis_pedidos.css" rel="stylesheet">
</head>
<body>

<header class="tienda-header">
    <div class="logo">CYBERCORE</div>
    <nav>
        <a href="../home.php">Inicio</a>
        <a href="carrito.php">🛒 Carrito</a>
        <a href="mis_pedidos.php">📦 Mis pedidos</a>
        <a class="logout" href="../logout.php">Cerrar sesión</a>
    </nav>
</header>

<div class="tienda-container">

    <p><a class="btn-secundario" href="mis_pedidos.php">← Mis pedidos</a></p>

    <h1>Pedido #<?= (int) $pedido['id_pedidos'] ?></h1>

    <div class="caja">
        <div class="mp-card-top">
            <div class="mp-meta">Realizado el <?= e(date('d/m/Y', strtotime($pedido['fecha_pedidos']))) ?></div>
            <span class="mp-badge" style="background: <?= e($info['color']) ?>;"><?= $info['emoji'] ?> <?= e($pedido['estado']) ?></span>
        </div>

        <?php barraProgreso($modelo, $pedido['estado']); ?>

        <?php if (!empty($pedido['num_seguimiento'])): ?>
            <div class="mp-track">🚚 <?= e($pedido['empresa_transporte']) ?> · N° <span><?= e($pedido['num_seguimiento']) ?></span>
                <button type="button" onclick="copiarSeguimiento(this, '<?= e($pedido['num_seguimiento']) ?>')">Copiar</button>
            </div>
        <?php endif; ?>

        <?php if (!empty($pedido['fecha_estimada_entrega']) && !in_array($pedido['estado'], ['Entregado', 'Cancelado'], true)): ?>
            <p class="mp-meta" style="margin-top:12px;">Llegada estimada: <strong><?= e(date('d/m/Y', strtotime($pedido['fecha_estimada_entrega']))) ?></strong></p>
        <?php endif; ?>
    </div>

    <div class="caja">
        <h2>Historial</h2>
        <ul class="mp-hist">
            <?php foreach ($historial as $h): ?>
                <li>
                    <div class="h-estado"><?= e($h['estado']) ?></div>
                    <div class="h-fecha"><?= e(fechaHora($h['fecha_evento'])) ?></div>
                    <?php if (!empty($h['nota'])): ?><div class="h-nota"><?= nl2br(e($h['nota'])) ?></div><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="caja">
        <h2>Detalle</h2>
        <table class="tabla-compra">
            <tr><th>Producto</th><th class="num">Precio</th><th class="num">Cant.</th><th class="num">Subtotal</th></tr>
            <?php foreach ($pedido['items'] as $it): ?>
            <tr>
                <td><?= e($it['nombre']) ?></td>
                <td class="num"><?= plata($it['precio_unitario_base']) ?></td>
                <td class="num"><?= (int) $it['cantidad'] ?></td>
                <td class="num"><?= plata($it['subtotal_final']) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr><td colspan="3" class="num texto-suave">Subtotal</td><td class="num"><?= plata($subtotal) ?></td></tr>
            <tr><td colspan="3" class="num texto-suave">Envío</td><td class="num"><?= plata($envio) ?></td></tr>
            <tr class="fila-total"><td colspan="3" class="num">Total</td><td class="num"><?= plata($pedido['monto_total']) ?></td></tr>
        </table>
    </div>

    <div class="caja">
        <h2>Entrega y pago</h2>
        <p>
            <strong>Dirección:</strong> <?= e($pedido['direccion_entrega']) ?><br>
            <?php if (!empty($pedido['recibe_nombre'])): ?>
                <strong>Recibe:</strong> <?= e($pedido['recibe_nombre']) ?>
                <?php if (!empty($pedido['recibe_telefono'])): ?>(<?= e($pedido['recibe_telefono']) ?>)<?php endif; ?><br>
            <?php endif; ?>
            <?php if (!empty($pedido['tipo_lugar'])): ?><strong>Tipo de lugar:</strong> <?= e($pedido['tipo_lugar']) ?><br><?php endif; ?>
            <?php if (!empty($pedido['piso_depto_torre'])): ?><strong>Piso/Depto:</strong> <?= e($pedido['piso_depto_torre']) ?><br><?php endif; ?>
            <?php if (!empty($pedido['referencia'])): ?><strong>Referencia:</strong> <?= e($pedido['referencia']) ?><br><?php endif; ?>
        </p>
        <p>
            <strong>Método de pago:</strong> <?= e($pedido['metodo_pago']) ?><br>
            <strong>Estado del pago:</strong> <?= e($pedido['estado_pagos']) ?>
        </p>
    </div>

</div>

<script>
function copiarSeguimiento(btn, texto) {
    var listo = function () { btn.textContent = '¡Copiado!'; setTimeout(function () { btn.textContent = 'Copiar'; }, 1500); };
    if (navigator.clipboard) { navigator.clipboard.writeText(texto).then(listo); return; }
    var t = document.createElement('textarea'); t.value = texto; document.body.appendChild(t);
    t.select(); try { document.execCommand('copy'); listo(); } catch (e) {} document.body.removeChild(t);
}
</script>
<script src="../js/theme-toggle.js"></script>
</body>
</html>