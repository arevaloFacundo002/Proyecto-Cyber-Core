<?php
require_once '../../auth/auth.php';
require_once '../../models/Pedido.php';

$rol = $_SESSION['rol'] ?? '';
if (!in_array($rol, ['administrador', 'empleado'], true)) {
    header("Location: ../../home.php");
    exit;
}

$pedidosModelo = new Pedido();

$estado = $_GET['estado'] ?? 'todos';
if ($estado !== 'todos' && !array_key_exists($estado, Pedido::ESTADOS)) {
    $estado = 'todos';
}
$buscar = trim($_GET['buscar'] ?? '');

$pedidos = $pedidosModelo->listarPedidosAdmin($estado, $buscar);
$cuentas = $pedidosModelo->contarPorEstado();

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function plata($valor)
{
    return '$' . number_format((float) $valor, 2, ',', '.');
}

function urlFiltro($estado, $buscar)
{
    $q = [];
    if ($estado !== 'todos') $q['estado'] = $estado;
    if ($buscar !== '')      $q['buscar'] = $buscar;
    return 'listarPedidos.php' . ($q ? '?' . http_build_query($q) : '');
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
body { margin: 0; font-family: 'Segoe UI', sans-serif; }
.contenedor { max-width: 1150px; margin: 40px auto; padding: 0 20px; }
h1 { color: var(--cc-primario); text-shadow: 0 0 10px rgba(0, 234, 255, 0.4); }

.caja-filtro {
    background: var(--cc-superficie);
    border: 1px solid var(--cc-borde);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.tabs-estado { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.tab-estado {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: 20px;
    background: var(--cc-superficie); color: var(--cc-texto);
    border: 1px solid var(--cc-borde); text-decoration: none;
    font-size: 14px; font-weight: 600; transition: 0.2s;
}
.tab-estado:hover { border-color: var(--cc-primario); color: var(--cc-primario); }
.tab-estado.activa {
    background: var(--cc-primario); color: var(--cc-texto-sobre-primario);
    border-color: var(--cc-primario);
}
.tab-estado .cuenta {
    background: rgba(0,0,0,0.18); border-radius: 10px;
    padding: 0 8px; font-size: 12px;
}

.badge-estado {
    padding: 5px 10px; border-radius: 12px; font-size: 13px;
    font-weight: bold; color: #0B1220; display: inline-block;
}
.table { color: var(--cc-texto); }
.table thead th { color: var(--cc-primario); font-weight: 700; border-bottom: 2px solid var(--cc-primario); background: transparent; }
.table td { background: transparent; color: var(--cc-texto); border-color: var(--cc-borde); }
.sub { color: var(--cc-texto-secundario); font-size: 12px; }
</style>
</head>

<body>

<?php require __DIR__ . '/../../includes/header_admin.php'; ?>

<div class="contenedor">

    <a href="../../inicio.php" class="btn btn-secondary mt-3">← Volver al Panel</a>

    <h1 class="mt-3">📦 Pedidos</h1>

    <div class="caja-filtro">
        <form method="GET" class="row g-3 align-items-end">
            <?php if ($estado !== 'todos'): ?>
                <input type="hidden" name="estado" value="<?= e($estado) ?>">
            <?php endif; ?>
            <div class="col-md-8">
                <label class="form-label fw-bold">Buscar pedido</label>
                <input type="text" name="buscar" class="form-control"
                       placeholder="N° de pedido, nombre, apellido, correo o N° de seguimiento"
                       value="<?= e($buscar) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-info text-dark fw-bold w-100">🔎 Buscar</button>
            </div>
            <?php if ($buscar !== ''): ?>
                <div class="col-md-2">
                    <a href="<?= e(urlFiltro($estado, '')) ?>" class="btn btn-outline-secondary w-100">Limpiar</a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="tabs-estado">
        <a class="tab-estado <?= $estado === 'todos' ? 'activa' : '' ?>" href="<?= e(urlFiltro('todos', $buscar)) ?>">
            Todos <span class="cuenta"><?= (int) ($cuentas['todos'] ?? 0) ?></span>
        </a>
        <?php foreach (Pedido::ESTADOS as $nombreEstado => $info): ?>
            <a class="tab-estado <?= $estado === $nombreEstado ? 'activa' : '' ?>"
               href="<?= e(urlFiltro($nombreEstado, $buscar)) ?>">
                <?= $info['emoji'] ?> <?= e($nombreEstado) ?>
                <span class="cuenta"><?= (int) ($cuentas[$nombreEstado] ?? 0) ?></span>
            </a>
        <?php endforeach; ?>
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
                    <th>Transporte / Seguimiento</th>
                    <th width="90">Acciones</th>
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
                            <td>
                                <?= e($p['nombre'] . ' ' . $p['apellido']) ?>
                                <?php if (!empty($p['correo'])): ?><div class="sub"><?= e($p['correo']) ?></div><?php endif; ?>
                            </td>
                            <td><?= e(date('d/m/Y', strtotime($p['fecha_pedidos']))) ?></td>
                            <td><?= plata($p['monto_total']) ?></td>
                            <td>
                                <span class="badge-estado" style="background: <?= e($info['color']) ?>;">
                                    <?= $info['emoji'] ?> <?= e($p['estado']) ?>
                                </span>
                            </td>
                            <td>
                                <?= e($p['empresa_transporte'] ?: '—') ?>
                                <?php if (!empty($p['num_seguimiento'])): ?>
                                    <div class="sub">N° <?= e($p['num_seguimiento']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="verPedido.php?id=<?= (int) $p['id_pedidos'] ?>" class="btn btn-info btn-sm text-dark fw-bold">Ver</a>
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