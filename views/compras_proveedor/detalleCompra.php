<?php
require_once __DIR__ . '/../../auth/staff.php';
require_once __DIR__ . '/../../models/inputs/CompraProveedor.php';

$modelo = new CompraProveedor();
$id = (int) ($_GET['id'] ?? 0);
$compra = $modelo->obtener($id);

if (!$compra) {
    $_SESSION['flash'] = ['tipo' => 'danger', 'texto' => 'La compra no existe.'];
    header('Location: listarCompra.php');
    exit();
}

$movimientos = $modelo->movimientosDeCompra($id);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$badges = ['borrador' => 'warning text-dark', 'confirmada' => 'success', 'anulada' => 'secondary'];
$estado = $compra['estado'];

$unidades = array_sum(array_map(fn($l) => (int) $l['cantidad'], $compra['lineas']));
$productos = count($compra['lineas']);
$subtotal = array_sum(array_map(fn($l) => (float) $l['subtotal_linea'], $compra['lineas']));

$msgConfirmar = "Vas a ingresar {$productos} producto(s) ({$unidades} unidades) al stock. "
              . "Se generará un movimiento por cada línea. ¿Confirmar compra?";
$msgAnular = "Vas a anular esta compra: se descontarán del stock {$productos} producto(s) ({$unidades} unidades). "
           . "Si alguna de esas unidades ya se vendió, el sistema la va a rechazar. "
           . "Esta acción no se puede deshacer. ¿Confirmar?";
$msgEliminar = "¿Eliminar este borrador? Todavía no movió stock, pero se pierde lo cargado.";

$accion = '../../controllers/inputsControllers/CompraProveedorController.php';

// Para usar un texto dentro de onsubmit="return confirm(...)"
function js($texto)
{
    return htmlspecialchars(json_encode($texto), ENT_QUOTES);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compra #<?= $id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-3 mb-5">

    <a href="listarCompra.php" class="btn btn-secondary btn-sm mb-3">← Volver</a>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold mb-0">
            Compra #<?= $id ?>
            <span class="badge bg-<?= $badges[$estado] ?? 'secondary' ?> fs-6 align-middle">
                <?= ucfirst($estado) ?>
            </span>
        </h3>

        <div class="d-flex gap-2">
            <?php if ($estado === 'borrador'): ?>
                <a href="cargarCompra.php?id=<?= $id ?>" class="btn btn-outline-primary">Editar</a>

                <form method="POST" action="<?= $accion ?>" class="d-inline"
                      onsubmit="return confirm(<?= js($msgConfirmar) ?>)">
                    <input type="hidden" name="accion" value="confirmar">
                    <input type="hidden" name="id_compra" value="<?= $id ?>">
                    <button class="btn btn-success">Confirmar compra</button>
                </form>

                <form method="POST" action="<?= $accion ?>" class="d-inline"
                      onsubmit="return confirm(<?= js($msgEliminar) ?>)">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id_compra" value="<?= $id ?>">
                    <button class="btn btn-outline-danger">Eliminar borrador</button>
                </form>

            <?php elseif ($estado === 'confirmada'): ?>
                <form method="POST" action="<?= $accion ?>" class="d-inline"
                      onsubmit="return confirm(<?= js($msgAnular) ?>)">
                    <input type="hidden" name="accion" value="anular">
                    <input type="hidden" name="id_compra" value="<?= $id ?>">
                    <button class="btn btn-outline-danger">Anular compra</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['tipo']) ?>">
            <?= htmlspecialchars($flash['texto']) ?>
        </div>
    <?php endif; ?>

    <!-- CABECERA -->
    <div class="card shadow border-0 mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">Proveedor</div>
                    <div class="fw-semibold"><?= htmlspecialchars($compra['razon_social']) ?></div></div>
                <div class="col-md-3"><div class="text-muted small">Comprobante</div>
                    <div class="fw-semibold"><?= htmlspecialchars($compra['comprobante_compra']) ?></div></div>
                <div class="col-md-2"><div class="text-muted small">Fecha</div>
                    <div class="fw-semibold"><?= $compra['fecha_compra'] ? date('d/m/Y', strtotime($compra['fecha_compra'])) : '-' ?></div></div>
                <div class="col-md-3"><div class="text-muted small">Cargada por</div>
                    <div class="fw-semibold"><?= htmlspecialchars($compra['usuario_nombre'] ?? '—') ?></div></div>
            </div>
            <?php if ($compra['fecha_confirmacion']): ?>
                <div class="text-muted small mt-2">
                    Confirmada el <?= date('d/m/Y H:i', strtotime($compra['fecha_confirmacion'])) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- LINEAS -->
    <div class="card shadow border-0 mb-3">
        <div class="card-header fw-bold">Productos de la factura</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Código</th><th>Producto</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-end">Costo unit.</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($compra['lineas'] as $l): ?>
                    <tr>
                        <td><?= htmlspecialchars($l['codigo']) ?></td>
                        <td><?= htmlspecialchars($l['nombre']) ?></td>
                        <td class="text-center"><?= (int) $l['cantidad'] ?></td>
                        <td class="text-end">$ <?= number_format((float) $l['costo_unitario'], 2, ',', '.') ?></td>
                        <td class="text-end">$ <?= number_format((float) $l['subtotal_linea'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-body">
            <div class="row justify-content-end">
                <div class="col-md-4">
                    <div class="d-flex justify-content-between"><span>Subtotal</span><span>$ <?= number_format($subtotal, 2, ',', '.') ?></span></div>
                    <div class="d-flex justify-content-between"><span>Bonificación</span><span>- $ <?= number_format((float) $compra['bonificacion'], 2, ',', '.') ?></span></div>
                    <hr class="my-1">
                    <div class="d-flex justify-content-between fw-bold fs-5"><span>Total</span><span>$ <?= number_format((float) $compra['total_compra'], 2, ',', '.') ?></span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- MOVIMIENTOS GENERADOS -->
    <div class="card shadow border-0">
        <div class="card-header fw-bold">Movimientos de stock generados</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Fecha</th><th>Hora</th><th>Concepto</th><th>Producto</th><th class="text-end">Cantidad</th></tr>
                </thead>
                <tbody>
                <?php if (count($movimientos) === 0): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">
                        <?= $estado === 'borrador' ? 'Todavía no hay movimientos: el stock se modifica al confirmar.' : 'Sin movimientos asociados.' ?>
                    </td></tr>
                <?php endif; ?>
                <?php foreach ($movimientos as $m): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($m['fecha_movimiento'])) ?></td>
                        <td><?= htmlspecialchars(substr($m['hora_movimiento'], 0, 5)) ?></td>
                        <td><?= htmlspecialchars($m['concepto']) ?></td>
                        <td><?= htmlspecialchars($m['codigo'] . ' - ' . $m['nombre']) ?></td>
                        <td class="text-end fw-semibold <?= $m['cantidad'] > 0 ? 'text-success' : 'text-danger' ?>">
                            <?= $m['cantidad'] > 0 ? '+' : '' ?><?= (int) $m['cantidad'] ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>