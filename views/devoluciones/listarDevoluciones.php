<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario']) ||
   ($_SESSION['rol'] != "administrador" && $_SESSION['rol'] != "empleado")) {
    header("Location: ../../home.php");
    exit;
}

require_once __DIR__ . '/../../models/Devolucion.php';

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function plata($v) { return '$' . number_format((float) $v, 2, ',', '.'); }

$modelo = new Devolucion();

$estado = $_GET['estado'] ?? 'Pendiente';
if (!in_array($estado, ['Pendiente', 'Aprobada', 'Rechazada', 'todos'], true)) {
    $estado = 'Pendiente';
}
$buscar = trim($_GET['buscar'] ?? '');

$filas   = $modelo->listarAdmin($estado, $buscar);
$conteo  = $modelo->contarPorEstado();
$ruta_raiz = '../../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Devoluciones - CyberCore</title>
<link href="../../css/theme.css" rel="stylesheet">
<?php require __DIR__ . '/../../consultas/_estilo_consultas.php'; ?>
<style>
    .tabs { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
    .tab { padding: 8px 16px; border-radius: 20px; border: 1px solid var(--cc-borde); color: var(--cc-texto);
        text-decoration: none; font-weight: 600; font-size: 14px; background: var(--cc-superficie-2); }
    .tab.activa { background: var(--cc-primario); color: var(--cc-texto-sobre-primario); border-color: var(--cc-primario); }
    .tab:hover { border-color: var(--cc-primario); }
</style>
</head>
<body>
<?php require __DIR__ . '/../../includes/header_admin.php'; ?>

<div class="container">
    <h1>↩ Devoluciones</h1>
    <div class="subtitulo">Pedidos de devolución de los clientes: revisalos, aprobalos o rechazalos</div>

    <div class="tabs">
        <a class="tab <?= $estado === 'Pendiente' ? 'activa' : '' ?>" href="?estado=Pendiente">Pendientes (<?= $conteo['Pendiente'] ?>)</a>
        <a class="tab <?= $estado === 'Aprobada' ? 'activa' : '' ?>" href="?estado=Aprobada">Aprobadas (<?= $conteo['Aprobada'] ?>)</a>
        <a class="tab <?= $estado === 'Rechazada' ? 'activa' : '' ?>" href="?estado=Rechazada">Rechazadas (<?= $conteo['Rechazada'] ?>)</a>
        <a class="tab <?= $estado === 'todos' ? 'activa' : '' ?>" href="?estado=todos">Todas</a>
    </div>

    <form method="GET" class="filtros">
        <input type="hidden" name="estado" value="<?= e($estado) ?>">
        <div style="flex:1;min-width:220px;"><label>Buscar</label>
            <input type="text" name="buscar" style="width:100%;box-sizing:border-box;" placeholder="N° de devolución, N° de pedido o cliente" value="<?= e($buscar) ?>"></div>
        <button class="btn-cc" type="submit">🔎 Buscar</button>
        <?php if ($buscar !== ''): ?><a class="btn-cc btn-sec" href="?estado=<?= e($estado) ?>">Limpiar</a><?php endif; ?>
    </form>

    <?php if (!$filas): ?>
        <div class="empty">No hay devoluciones para mostrar.</div>
    <?php else: ?>
    <table>
        <tr><th>N°</th><th>Fecha</th><th>Pedido</th><th>Cliente</th><th>Estado</th><th class="num">Reembolso</th><th></th></tr>
        <?php foreach ($filas as $f): $info = Devolucion::infoEstado($f['estado']); ?>
        <tr>
            <td><strong>#<?= (int) $f['id_devolucion'] ?></strong></td>
            <td><?= $f['fecha_solicitud'] ? e(date('d/m/Y', strtotime($f['fecha_solicitud']))) : '' ?></td>
            <td>#<?= (int) $f['rela_id_pedidos'] ?></td>
            <td><?= e($f['nombre'] . ' ' . $f['apellido']) ?></td>
            <td><span class="badge" style="background: <?= e($info['color']) ?>;"><?= $info['emoji'] ?> <?= e(Devolucion::estaAbierta($f['estado']) ? 'Pendiente' : $f['estado']) ?></span></td>
            <td class="num"><strong><?= plata($f['monto_reembolso']) ?></strong></td>
            <td><a class="btn-cc" style="padding:6px 12px;font-size:13px;" href="verDevolucion.php?id=<?= (int) $f['id_devolucion'] ?>">Ver</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <a href="../../inicio.php" class="volver" style="margin-top:24px;">⬅ Volver al Panel</a>
</div>
<script src="../../js/theme-toggle.js"></script>
</body>
</html>