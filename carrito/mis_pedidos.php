<?php
session_start();
require_once '../models/Pedido.php';
require_once __DIR__ . '/_helpers_pedidos.php';

if (!isset($_SESSION['usuario'], $_SESSION['id_usuario'])) {
    header("Location: ../login.php?msg=Debes iniciar sesión");
    exit;
}

$id_usuario = (int) $_SESSION['id_usuario'];
$modelo     = new Pedido();

$grupo = $_GET['ver'] ?? 'todos';
if (!in_array($grupo, ['todos', 'curso', 'entregados'], true)) {
    $grupo = 'todos';
}

$pedidos = $modelo->listarPedidosCliente($id_usuario, $grupo);
$cuentas = $modelo->contarPedidosCliente($id_usuario);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mis pedidos - CyberCore</title>
<link href="../css/theme.css" rel="stylesheet">
<link href="../css/tienda.css" rel="stylesheet">
<link href="../css/mis_pedidos.css" rel="stylesheet">
</head>
<body>

<header class="tienda-header">
    <div class="logo">CYBERCORE</div>
    <nav>
        <a href="../home.php">Inicio</a>
        <?php $ruta_raiz = '../'; include __DIR__ . '/../includes/campana.php'; ?>
        <a href="carrito.php">🛒 Carrito</a>
        <a href="mis_pedidos.php">📦 Mis pedidos</a>
        <a class="logout" href="../logout.php">Cerrar sesión</a>
    </nav>
</header>

<div class="tienda-container">

    <h1>📦 Mis pedidos</h1>

    <div class="mp-filtros">
        <a class="mp-filtro <?= $grupo === 'todos' ? 'activa' : '' ?>" href="mis_pedidos.php">Todos <span class="n"><?= $cuentas['todos'] ?></span></a>
        <a class="mp-filtro <?= $grupo === 'curso' ? 'activa' : '' ?>" href="mis_pedidos.php?ver=curso">En curso <span class="n"><?= $cuentas['curso'] ?></span></a>
        <a class="mp-filtro <?= $grupo === 'entregados' ? 'activa' : '' ?>" href="mis_pedidos.php?ver=entregados">Entregados <span class="n"><?= $cuentas['entregados'] ?></span></a>
    </div>

    <?php if (empty($pedidos)): ?>
        <div class="mp-vacio">
            <?= $grupo === 'todos' ? 'Todavía no hiciste ninguna compra.' : 'No hay pedidos en esta sección.' ?><br><br>
            <a class="btn-principal" href="../home.php">Ir a la tienda</a>
        </div>
    <?php else: ?>
        <?php foreach ($pedidos as $p): ?>
            <?php $info = Pedido::ESTADOS[$p['estado']] ?? ['emoji' => '⚪', 'color' => '#999']; ?>
            <div class="mp-card">
                <div class="mp-card-top">
                    <div>
                        <div class="mp-num">Pedido #<?= (int) $p['id_pedidos'] ?></div>
                        <div class="mp-meta"><?= e(date('d/m/Y', strtotime($p['fecha_pedidos']))) ?> · <?= (int) $p['cant_items'] ?> producto(s)</div>
                    </div>
                    <span class="mp-badge" style="background: <?= e($info['color']) ?>;"><?= $info['emoji'] ?> <?= e($p['estado']) ?></span>
                    <div class="mp-total"><?= plata($p['monto_total']) ?></div>
                </div>

                <?php barraProgreso($modelo, $p['estado']); ?>

                <?php if (!empty($p['num_seguimiento'])): ?>
                    <div class="mp-track">🚚 <?= e($p['empresa_transporte']) ?> · N° <span><?= e($p['num_seguimiento']) ?></span>
                        <button type="button" onclick="copiarSeguimiento(this, '<?= e($p['num_seguimiento']) ?>')">Copiar</button>
                    </div>
                <?php endif; ?>

                <p style="margin:14px 0 0;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <a class="btn-secundario" href="mi_pedido.php?id=<?= (int) $p['id_pedidos'] ?>">Ver detalle</a>
                    <?php if (!empty($p['id_factura'])): ?>
                        <a class="btn-principal" href="mi_factura.php?id=<?= (int) $p['id_factura'] ?>">🧾 Ver factura</a>
                    <?php endif; ?>
                    <?= etiquetaPago($p['estado_pagos'] ?? null) ?>
                </p>
            </div>
        <?php endforeach; ?>
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