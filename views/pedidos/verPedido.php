<?php
require_once '../../auth/auth.php';
require_once '../../models/Pedido.php';

$id_pedido = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id_pedido <= 0) {
    header("Location: listarPedidos.php");
    exit;
}

$pedidosModelo = new Pedido();
$pedido = $pedidosModelo->obtenerPedidoAdmin($id_pedido);

if (!$pedido) {
    die("Pedido no encontrado.");
}

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function plata($valor)
{
    return '$' . number_format((float) $valor, 2, ',', '.');
}

$etiquetasLugar = [
    'casa'         => '🏠 Casa',
    'departamento' => '🏢 Departamento',
    'trabajo'      => '💼 Trabajo',
    'local'        => '🏪 Local comercial',
    'otro'         => '📍 Otro',
];
$tipoLugarTexto = $etiquetasLugar[$pedido['tipo_lugar'] ?? ''] ?? 'N/A';

$mensaje = $_GET['mensaje'] ?? null;

$ruta_raiz = '../../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedido #<?= (int) $pedido['id_pedidos'] ?> - CyberCore</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../../css/theme.css" rel="stylesheet">

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
}

.contenedor {
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px;
}

h1 {
    color: var(--cc-primario);
    text-shadow: 0 0 10px rgba(0, 234, 255, 0.4);
}

.caja {
    background: var(--cc-superficie);
    border: 1px solid var(--cc-borde);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 22px;
    color: var(--cc-texto);
}

.caja h2 {
    color: var(--cc-primario);
    font-size: 19px;
    margin-top: 0;
}

.caja table {
    width: 100%;
}

.caja td {
    padding: 6px 0;
    color: var(--cc-texto);
}
</style>
</head>

<body>

<?php require __DIR__ . '/../../includes/header_admin.php'; ?>

<div class="contenedor">

    <a href="listarPedidos.php" class="btn btn-secondary mt-3">← Volver a Pedidos</a>

    <h1 class="mt-3">Pedido #<?= (int) $pedido['id_pedidos'] ?></h1>

    <?php if ($mensaje === 'actualizado'): ?>
        <div class="alert alert-success">Estado actualizado correctamente.</div>
    <?php endif; ?>

    <!-- CAMBIAR ESTADO -->
    <div class="caja">
        <h2>Estado del pedido</h2>
        <form action="../../controllers/pedidosControllers/PedidoController.php?accion=cambiarEstado" method="POST" class="row g-3 align-items-end">
            <input type="hidden" name="id_pedido" value="<?= (int) $pedido['id_pedidos'] ?>">

            <div class="col-md-6">
                <select name="nuevo_estado" class="form-select">
                    <?php foreach (Pedido::ESTADOS as $nombreEstado => $info): ?>
                        <option value="<?= e($nombreEstado) ?>" <?= $pedido['estado'] === $nombreEstado ? 'selected' : '' ?>>
                            <?= $info['emoji'] ?> <?= e($nombreEstado) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-info text-dark fw-bold w-100">Actualizar estado</button>
            </div>
        </form>
    </div>

    <!-- CLIENTE -->
    <div class="caja">
        <h2>Cliente</h2>
        <table>
            <tr><td><strong>Nombre:</strong></td><td><?= e($pedido['cliente_nombre'] . ' ' . $pedido['cliente_apellido']) ?></td></tr>
            <tr><td><strong>Teléfono:</strong></td><td><?= e($pedido['cliente_telefono'] ?? 'N/A') ?></td></tr>
            <tr><td><strong>Fecha del pedido:</strong></td><td><?= e(date('d/m/Y', strtotime($pedido['fecha_pedidos']))) ?></td></tr>
        </table>
    </div>

    <!-- ENTREGA -->
    <div class="caja">
        <h2>Entrega</h2>
        <table>
            <tr><td><strong>Dirección:</strong></td><td><?= e($pedido['direccion_entrega']) ?></td></tr>
            <tr><td><strong>Tipo de lugar:</strong></td><td><?= e($tipoLugarTexto) ?></td></tr>
            <?php if (!empty($pedido['piso_depto_torre'])): ?>
                <tr><td><strong>Piso/Depto/Torre:</strong></td><td><?= e($pedido['piso_depto_torre']) ?></td></tr>
            <?php endif; ?>
            <?php if (!empty($pedido['referencia'])): ?>
                <tr><td><strong>Referencia:</strong></td><td><?= e($pedido['referencia']) ?></td></tr>
            <?php endif; ?>
            <tr>
                <td><strong>Recibe:</strong></td>
                <td>
                    <?php if (($pedido['recibe_quien'] ?? 'yo') === 'otra_persona'): ?>
                        <?= e($pedido['recibe_nombre']) ?> — Tel: <?= e($pedido['recibe_telefono']) ?>
                    <?php else: ?>
                        El/la cliente (<?= e($pedido['cliente_nombre']) ?>)
                    <?php endif; ?>
                </td>
            </tr>
            <tr><td><strong>Transporte:</strong></td><td><?= e($pedido['empresa_transporte'] ?? 'N/A') ?></td></tr>
            <?php if (!empty($pedido['fecha_estimada_entrega'])): ?>
                <tr><td><strong>Llegada estimada:</strong></td><td><?= e(date('d/m/Y', strtotime($pedido['fecha_estimada_entrega']))) ?></td></tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- PAGO -->
    <div class="caja">
        <h2>Pago</h2>
        <table>
            <tr><td><strong>Método:</strong></td><td><?= e($pedido['metodo_pago'] ?? 'N/A') ?></td></tr>
            <tr><td><strong>Estado del pago:</strong></td><td><?= e($pedido['estado_pagos'] ?? 'N/A') ?></td></tr>
            <tr><td><strong>Referencia:</strong></td><td><?= e($pedido['referencia_transaccion'] ?? 'N/A') ?></td></tr>
        </table>
    </div>

    <!-- PRODUCTOS -->
    <div class="caja">
        <h2>Productos</h2>
        <table class="table table-hover">
            <thead>
                <tr><th>Producto</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr>
            </thead>
            <tbody>
                <?php foreach ($pedido['items'] as $it): ?>
                <tr>
                    <td><?= e($it['nombre']) ?></td>
                    <td><?= (int) $it['cantidad'] ?></td>
                    <td><?= plata($it['precio_unitario_base']) ?></td>
                    <td><?= plata($it['subtotal_final']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="text-end fw-bold" style="color: var(--cc-primario);">
            Total: <?= plata($pedido['monto_total']) ?>
        </p>
    </div>

</div>

<script src="../../js/theme-toggle.js"></script>

</body>
</html>