<?php
session_start();
require_once '../models/Pedido.php';
require_once '../models/Devolucion.php';
require_once '../models/Comprobante.php';
require_once __DIR__ . '/_pago_info.php';
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
$base     = 0.0;
$promoNombre = '';
foreach ($pedido['items'] as $it) {
    $subtotal += (float) $it['subtotal_final'];
    $base     += (float) ($it['subtotal_base'] ?? $it['subtotal_final']);
    if (in_array($it['promo_tipo'] ?? '', ['Porcentaje', 'Fijo'], true)) {
        $promoNombre = $it['promo_nombre'];
    }
}
$descuento = max(0, round($base - $subtotal, 2));
$envio = round((float) $pedido['monto_total'] - $subtotal, 2);
$info  = Pedido::ESTADOS[$pedido['estado']] ?? ['emoji' => '⚪', 'color' => '#999'];
$historial = array_reverse($pedido['historial']); // lo más nuevo primero

$devModelo = new Devolucion();
$devoluciones = $devModelo->deDelPedido($id_pedido);
$puedeDev = $devModelo->puedeSolicitar($id_pedido, (int) $_SESSION['id_usuario']);
$flashDev = $_SESSION['dev_flash'] ?? null;
unset($_SESSION['dev_flash']);

$flashComp = $_SESSION['comp_flash'] ?? null;
unset($_SESSION['comp_flash']);

$compModelo = new Comprobante();
$pagoC      = $compModelo->pagoDePedido($id_pedido);
$esTransf   = $pagoC && $compModelo->esTransferencia((int) $pagoC['rela_id_metodo_pago']);
if (empty($_SESSION['csrf_comprobante'])) {
    $_SESSION['csrf_comprobante'] = bin2hex(random_bytes(16));
}
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

    <?php if ($flashDev): ?>
        <div class="aviso <?= $flashDev['tipo'] === 'ok' ? 'aviso-ok' : '' ?>"><?= e($flashDev['texto']) ?></div>
    <?php endif; ?>

    <div class="caja">
        <div class="mp-card-top">
            <div class="mp-meta">Realizado el <?= e(date('d/m/Y', strtotime($pedido['fecha_pedidos']))) ?></div>
            <span class="mp-badge" style="background: <?= e($info['color']) ?>;"><?= $info['emoji'] ?> <?= e($pedido['estado']) ?></span>
        </div>

        <?php barraProgreso($modelo, $pedido['estado']); ?>

        <?php if (!empty($pedido['num_seguimiento'])): ?>
            <div class="mp-track">🚚 <?= e($pedido['empresa_transporte']) ?> · N° <span><?= e($pedido['num_seguimiento']) ?></span>
                <button type="button" onclick="copiarSeguimiento(this, '<?= e($pedido['num_seguimiento']) ?>')">Copiar</button>
                <?php
                $emp = mb_strtolower((string) $pedido['empresa_transporte']);
                $urlSeg = null;
                if (str_contains($emp, 'andreani')) {
                    $urlSeg = 'https://www.andreani.com/envio/' . rawurlencode($pedido['num_seguimiento']);
                } elseif (str_contains($emp, 'correo')) {
                    $urlSeg = 'https://www.correoargentino.com.ar/formularios/e-commerce';
                } elseif (str_contains($emp, 'oca')) {
                    $urlSeg = 'https://www.oca.com.ar/Busquedas/Envios';
                }
                if ($urlSeg): ?>
                    <a href="<?= e($urlSeg) ?>" target="_blank" rel="noopener" style="margin-left:8px;">Seguir mi envío ↗</a>
                <?php endif; ?>
            </div>
        <?php elseif (in_array($pedido['estado'], ['En camino', 'Entregado'], true)): ?>
            <div class="mp-track">🚚 Tu pedido ya salió. El número de seguimiento se está cargando.</div>
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
                <td class="num"><?= plata($it['subtotal_base'] ?? $it['subtotal_final']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php $cuponDesc = (float) ($pedido['cupon_descuento'] ?? 0); $promoDesc = max(0, round($descuento - $cuponDesc, 2)); ?>
            <?php if ($promoDesc > 0): ?>
            <tr><td colspan="3" class="num texto-suave">🏷 <?= e($promoNombre ?: 'Descuento') ?></td><td class="num" style="color: var(--cc-exito);">− <?= plata($promoDesc) ?></td></tr>
            <?php endif; ?>
            <?php if ($cuponDesc > 0): ?>
            <tr><td colspan="3" class="num texto-suave">🎟 Cupón <?= e($pedido['cupon_codigo']) ?></td><td class="num" style="color: var(--cc-exito);">− <?= plata($cuponDesc) ?></td></tr>
            <?php endif; ?>
            <tr><td colspan="3" class="num texto-suave">Subtotal</td><td class="num"><?= plata($subtotal) ?></td></tr>
            <tr><td colspan="3" class="num texto-suave">Envío</td><td class="num"><?= plata($envio) ?></td></tr>
            <tr class="fila-total"><td colspan="3" class="num">Total</td><td class="num"><?= plata($pedido['monto_total']) ?></td></tr>
        </table>
    </div>

    <div class="caja">
        <?php $esTicket = (($pedido['tipo_comprobante'] ?? 'Factura') === 'Ticket') || strncmp((string) ($pedido['numero_factura'] ?? ''), 'T-', 2) === 0; $cNombre = $esTicket ? 'ticket' : 'factura'; ?>
        <h2><?= $esTicket ? 'Ticket' : 'Factura' ?></h2>
        <?php if (!empty($pedido['id_factura'])): ?>
            <p><?= $esTicket ? 'Ticket' : 'Factura' ?> <strong><?= e($pedido['numero_factura']) ?></strong></p>
            <p><a class="btn-principal" href="mi_factura.php?id=<?= (int) $pedido['id_factura'] ?>">🧾 Ver / imprimir <?= $cNombre ?></a></p>
        <?php else: ?>
            <p class="texto-suave"><?= ($pedido['estado_pagos'] ?? '') === 'Aprobado' ? 'Tu ' . $cNombre . ' se está preparando.' : 'Tu ' . $cNombre . ' se emite cuando se aprueba el pago.' ?></p>
        <?php endif; ?>
    </div>

    <?php if ($devoluciones || $puedeDev['ok']): ?>
    <div class="caja">
        <h2>Devoluciones</h2>
        <?php foreach ($devoluciones as $d): $infoDev = Devolucion::infoEstado($d['estado']); ?>
            <div style="margin-bottom:14px;">
                <span class="mp-badge" style="background: <?= e($infoDev['color']) ?>;"><?= $infoDev['emoji'] ?> <?= e(Devolucion::estaAbierta($d['estado']) ? 'Pendiente' : $d['estado']) ?></span>
                <strong>Devolución #<?= (int) $d['id_devolucion'] ?></strong>
                <span class="texto-suave">· pedida el <?= e(date('d/m/Y', strtotime($d['fecha_solicitud']))) ?></span>
                <ul style="margin:8px 0 0 18px;">
                    <?php foreach ($d['items'] as $it): ?>
                        <li><?= (int) $it['cantidad'] ?> × <?= e($it['nombre']) ?> (<?= plata($it['monto']) ?>)</li>
                    <?php endforeach; ?>
                </ul>
                <?php if (!empty($d['monto_reembolso'])): ?>
                    <p class="texto-suave" style="margin:6px 0 0;">Reembolso: <strong><?= plata($d['monto_reembolso']) ?></strong></p>
                <?php endif; ?>
                <?php if (!empty($d['nota_admin'])): ?>
                    <p class="texto-suave" style="margin:6px 0 0;">Respuesta: <?= e($d['nota_admin']) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if ($puedeDev['ok']): ?>
            <p><a class="btn-principal" href="solicitar_devolucion.php?id=<?= (int) $pedido['id_pedidos'] ?>">↩ Pedir una devolución</a></p>
            <p class="texto-suave">Podés pedirla hasta el <?= e($puedeDev['limite']) ?>.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

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

    <?php if ($esTransf && $pedido['estado'] !== 'Cancelado'): ?>
    <div class="caja" id="comprobante">
        <h2>Pago por transferencia</h2>

        <?php if ($flashComp): ?>
            <div class="aviso <?= $flashComp['tipo'] === 'ok' ? 'aviso-ok' : '' ?>"><?= e($flashComp['texto']) ?></div>
        <?php endif; ?>

        <?php if ($pagoC['estado_pagos'] === 'Aprobado' || $pagoC['comprobante_estado'] === 'Aprobado'): ?>
            <p>✅ Tu pago fue confirmado. ¡Gracias!</p>
            <?php if ($pagoC['url_comprobante']): ?>
                <p><a href="ver_comprobante.php?id=<?= (int) $id_pedido ?>" target="_blank" rel="noopener">Ver el comprobante que subiste</a></p>
            <?php endif; ?>
        <?php else: ?>
            <p>Transferí <strong><?= plata($pedido['monto_total']) ?></strong> a esta cuenta:</p>
            <?= cuentaBancariaHtml($compModelo->cuenta()) ?>

            <?php if ($pagoC['comprobante_estado'] === 'Enviado'): ?>
                <p>🕓 Recibimos tu comprobante y lo estamos revisando.
                   <a href="ver_comprobante.php?id=<?= (int) $id_pedido ?>" target="_blank" rel="noopener">Verlo</a></p>
            <?php elseif ($pagoC['comprobante_estado'] === 'Rechazado'): ?>
                <p>❌ No pudimos aceptar tu comprobante.<br><strong>Motivo:</strong> <?= e($pagoC['comprobante_nota']) ?></p>
            <?php else: ?>
                <p class="texto-suave">Todavía no subiste el comprobante.</p>
            <?php endif; ?>

            <?php if ($compModelo->puedeSubir($pagoC)): ?>
                <?= formComprobanteHtml((int) $id_pedido, $_SESSION['csrf_comprobante'], $pagoC['comprobante_estado'] !== 'Sin comprobante') ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?= pagoInfoEstilosYScript() ?>
    <?php endif; ?>

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