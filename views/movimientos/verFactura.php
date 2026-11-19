<?php
require_once '../../auth/auth.php';
require_once '../../models/MovimientoFactura.php';

$rol = $_SESSION['rol'] ?? '';
if (!in_array($rol, ['administrador', 'empleado'], true)) {
    header("Location: ../../home.php");
    exit;
}

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function plata($v) { return '$' . number_format((float) $v, 2, ',', '.'); }

$f = (new MovimientoFactura())->obtener((int) ($_GET['id'] ?? 0));
if (!$f) {
    header("Location: listarFacturas.php");
    exit;
}

$unidades = array_sum(array_map(fn($i) => (int) $i['cantidad'], $f['items']));
$esCompra = $f['tipo'] === 'Compra';

$ruta_raiz = '../../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Factura #<?= (int) $f['id_movimiento_factura'] ?> - CyberCore</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../../css/theme.css" rel="stylesheet">
</head>
<body>
<?php require __DIR__ . '/../../includes/header_admin.php'; ?>

<div class="container mt-4 mb-5">

    <a href="listarFacturas.php" class="btn btn-secondary btn-sm mb-3">← Volver a Movimientos</a>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h3 class="fw-bold mb-0">Factura de <?= $esCompra ? 'compra' : 'venta' ?> · movimiento #<?= (int) $f['id_movimiento_factura'] ?></h3>
        <span class="badge <?= $esCompra ? 'bg-success' : 'bg-primary' ?> fs-6"><?= $esCompra ? 'Entró stock' : 'Salió stock' ?></span>
    </div>

    <div class="card shadow border-0 mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><small class="text-muted d-block">Fecha</small><strong><?= e(date('d/m/Y', strtotime($f['fecha']))) ?></strong></div>
                <div class="col-md-3"><small class="text-muted d-block">N° de factura</small><strong><?= e($f['numero_comprobante'] ?: '-') ?></strong></div>
                <div class="col-md-3"><small class="text-muted d-block"><?= $esCompra ? 'Proveedor' : 'Cliente' ?></small><strong><?= e($esCompra ? $f['proveedor'] : ($f['contraparte'] ?: 'Venta de mostrador')) ?></strong></div>
                <div class="col-md-3"><small class="text-muted d-block">Cargada por</small><strong><?= e($f['usuario'] ?: '-') ?></strong>
                    <small class="text-muted d-block"><?= e(date('d/m/Y H:i', strtotime($f['fecha_registro']))) ?></small></div>
                <?php if (!empty($f['comentario'])): ?>
                    <div class="col-12"><small class="text-muted d-block">Comentario</small><?= e($f['comentario']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card shadow border-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Código</th><th>Producto</th><th class="text-end">Cantidad</th><th class="text-end">Precio unitario</th><th class="text-end">Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($f['items'] as $it): ?>
                    <tr>
                        <td><?= e($it['codigo']) ?></td>
                        <td><?= e($it['nombre']) ?></td>
                        <td class="text-end"><?= (int) $it['cantidad'] ?> unidades</td>
                        <td class="text-end"><?= plata($it['precio_unitario']) ?></td>
                        <td class="text-end"><?= plata($it['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><th colspan="2">Total</th><th class="text-end"><?= $unidades ?> unidades</th><th></th><th class="text-end"><?= plata($f['total']) ?></th></tr>
                </tfoot>
            </table>
        </div>
    </div>

    <p class="text-muted mt-3 mb-0">Cada producto de esta factura también figura en el detalle de movimientos con la referencia <strong>FACT-<?= (int) $f['id_movimiento_factura'] ?></strong>.</p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../js/theme-toggle.js"></script>
</body>
</html>