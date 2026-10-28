<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario']) ||
   ($_SESSION['rol'] != "administrador" && $_SESSION['rol'] != "empleado")) {
    header("Location: ../home.php");
    exit;
}
require_once __DIR__ . '/../models/Pedido.php';
require_once __DIR__ . '/../includes/factura_render.php';

$modelo  = new Pedido();
$factura = $modelo->obtenerFactura((int) ($_GET['id'] ?? 0));
if (!$factura) {
    header("Location: facturas.php");
    exit;
}
$ruta_raiz = '../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Factura <?= htmlspecialchars($factura['numero_factura']) ?> - CyberCore</title>
<link href="../css/theme.css" rel="stylesheet">
<?php require __DIR__ . '/_estilo_consultas.php'; ?>
<style>.container { max-width: 880px; padding: 20px; }</style>
</head>
<body>
<?php require __DIR__ . '/../includes/header_admin.php'; ?>

<div class="container">
    <?php renderFactura($factura); ?>

    <div class="no-imprimir" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:22px;">
        <button class="btn-cc" onclick="window.print()">🖨 Imprimir / guardar PDF</button>
        <a class="btn-cc btn-sec" href="../views/pedidos/verPedido.php?id=<?= (int) $factura['rela_id_pedidos'] ?>">Ver pedido</a>
        <a class="volver" href="facturas.php">⬅ Volver a Facturas</a>
    </div>
</div>
<script src="../js/theme-toggle.js"></script>
</body>
</html>