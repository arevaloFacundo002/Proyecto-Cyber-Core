<?php
session_start();
require_once '../models/Pedido.php';
require_once __DIR__ . '/../includes/factura_render.php';

if (!isset($_SESSION['usuario'], $_SESSION['id_usuario'])) {
    header("Location: ../login.php?msg=Debes iniciar sesión");
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Solo devuelve la factura si pertenece al usuario logueado
$factura = $id > 0 ? (new Pedido())->obtenerFactura($id, (int) $_SESSION['id_usuario']) : null;
if (!$factura) {
    header("Location: mis_pedidos.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Factura <?= htmlspecialchars($factura['numero_factura']) ?> - CyberCore</title>
<link href="../css/theme.css" rel="stylesheet">
<link href="../css/tienda.css" rel="stylesheet">
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
    <?php renderFactura($factura); ?>

    <p class="no-imprimir" style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap;">
        <button type="button" class="btn-principal" onclick="window.print()">🖨 Imprimir / guardar PDF</button>
        <a class="btn-secundario" href="mi_pedido.php?id=<?= (int) $factura['rela_id_pedidos'] ?>">← Volver al pedido</a>
    </p>
</div>

<script src="../js/theme-toggle.js"></script>
</body>
</html>