<?php
require_once '../../auth/auth.php';
require_once '../../models/Pedido.php';

$pedidosModelo = new Pedido();

$estado = $_GET['estado'] ?? 'todos';
if ($estado !== 'todos' && !array_key_exists($estado, Pedido::ESTADOS)) {
    $estado = 'todos';
}

$pedidos = $pedidosModelo->listarPedidosAdmin($estado);

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function plata($valor)
{
    return '$' . number_format((float) $valor, 2, ',', '.');
}

$ruta_raiz = '../../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedidos - CyberCore</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../../css/theme.css" rel="stylesheet">

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
}

.contenedor {
    max-width: 1100px;
    margin: 40px auto;
    padding: 0 20px;
}

h1 {
    color: var(--cc-primario);
    text-shadow: 0 0 10px rgba(0, 234, 255, 0.4);
}

.caja-filtro {
    background: var(--cc-superficie);
    border: 1px solid var(--cc-borde);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.badge-estado {
    padding: 5px 10px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: bold;
    color: #0B1220;
    display: inline-block;
}
</style>
</head>

<body>

<?php require __DIR__ . '/../../includes/header_admin.php'; ?>

<div class="contenedor">

    <a href="../../inicio.php" class="btn btn-secondary mt-3">← Volver al Panel</a>

    <h1 class="mt-3">📦 Pedidos</h1>

    <div class="caja-filtro">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold">Filtrar por estado</label>
                <select name="estado" class="form-select">
                    <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                    <?php foreach (Pedido::ESTADOS as $nombreEstado => $info): ?>
                        <option value="<?= e($nombreEstado) ?>" <?= $estado === $nombreEstado ? 'selected' : '' ?>>
                            <?= $info['emoji'] ?> <?= e($nombreEstado) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-info text-dark fw-bold w-100">🔎 Filtrar</button>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>N° Pedido</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Transporte</th>
                    <th width="100">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pedidos)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No hay pedidos para este filtro.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pedidos as $p): ?>
                        <?php $info = Pedido::ESTADOS[$p['estado']] ?? ['emoji' => '⚪', 'color' => '#999']; ?>
                        <tr>
                            <td>#<?= (int) $p['id_pedidos'] ?></td>
                            <td><?= e($p['nombre'] . ' ' . $p['apellido']) ?></td>
                            <td><?= e(date('d/m/Y', strtotime($p['fecha_pedidos']))) ?></td>
                            <td><?= plata($p['monto_total']) ?></td>
                            <td>
                                <span class="badge-estado" style="background: <?= e($info['color']) ?>;">
                                    <?= $info['emoji'] ?> <?= e($p['estado']) ?>
                                </span>
                            </td>
                            <td><?= e($p['empresa_transporte'] ?? 'N/A') ?></td>
                            <td>
                                <a href="verPedido.php?id=<?= (int) $p['id_pedidos'] ?>" class="btn btn-info btn-sm text-dark fw-bold">
                                    Ver
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script src="../../js/theme-toggle.js"></script>

</body>
</html>