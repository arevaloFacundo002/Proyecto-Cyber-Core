<?php
require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../models/Notificacion.php';

$es_staff = in_array($_SESSION['rol'] ?? '', ['administrador', 'empleado'], true);

$notificacion = new Notificacion();
$lista        = $notificacion->listar_todas((int) $_SESSION['id_usuario']);

function e($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }

function destino_notificacion(?string $url): string
{
    if (!$url) {
        return '';
    }
    if (preg_match('#^(https?:)?//#', $url) || $url[0] === '/') {
        return $url;
    }
    return '../../' . preg_replace('#^(\.\./)+#', '', $url);
}

$nombres_tipo = [
    'pedido_recibido' => 'Pedido recibido',
    'pedido_nuevo'    => 'Pedido nuevo',
    'pago_aprobado'   => 'Pago aprobado',
    'factura_emitida' => 'Factura',
    'estado_pedido'   => 'Estado del pedido',
    'stock_bajo'      => 'Stock bajo',
    'oferta'          => 'Oferta',
    'evento'          => 'Evento',
    'aviso_general'   => 'Aviso',
];

$ruta_raiz = '../../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Historial de notificaciones - CyberCore</title>
<?php if ($es_staff): ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../../css/theme.css" rel="stylesheet">
<?php else: ?>
<link href="../../css/theme.css" rel="stylesheet">
<link href="../../css/tienda.css" rel="stylesheet">
<link href="../../css/mis_pedidos.css" rel="stylesheet">
<style>
.hist-tabla { width: 100%; border-collapse: collapse; background: var(--cc-superficie); border: 1px solid var(--cc-borde); border-radius: 12px; overflow: hidden; }
.hist-tabla th, .hist-tabla td { padding: 12px 14px; text-align: left; border-bottom: 1px solid var(--cc-borde); font-size: 14px; vertical-align: top; color: var(--cc-texto); }
.hist-tabla th { background: var(--cc-superficie-2); color: var(--cc-primario); }
.hist-nueva { background: var(--cc-primario); color: var(--cc-texto-sobre-primario); padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; }
.hist-leida { color: var(--cc-texto-secundario); font-size: 12px; }
.hist-wrap { overflow-x: auto; }
</style>
<?php endif; ?>
</head>
<body>

<?php if ($es_staff): ?>
    <?php require __DIR__ . '/../../includes/header_admin.php'; ?>
    <div class="contenedor">
        <a href="../../inicio.php" class="btn btn-secondary mt-3">← Volver al Panel</a>
        <h1 class="mt-3">🔔 Historial de notificaciones</h1>
<?php else: ?>
    <header class="tienda-header">
        <div class="logo">CYBERCORE</div>
        <nav>
            <a href="../../home.php">Inicio</a>
            <?php include __DIR__ . '/../../includes/campana.php'; ?>
            <a href="../../carrito/mis_pedidos.php">📦 Mis pedidos</a>
            <a class="logout" href="../../logout.php">Cerrar sesión</a>
        </nav>
    </header>
    <div class="tienda-container">
        <h1>🔔 Historial de notificaciones</h1>
<?php endif; ?>

<?php if (empty($lista)): ?>
        <p class="text-center py-4" style="color: var(--cc-texto-secundario);">Todavía no tenés notificaciones.</p>
<?php else: ?>
        <div class="<?= $es_staff ? 'table-responsive' : 'hist-wrap' ?> mt-3">
        <table class="<?= $es_staff ? 'table table-hover align-middle' : 'hist-tabla' ?>">
            <thead>
                <tr>
                    <th>Estado</th>
                    <th>Tipo</th>
                    <th>Aviso</th>
                    <th>Enviado por</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($lista as $n):
                $url = destino_notificacion($n['url_accion']); ?>
                <tr>
                    <td>
                        <?php if (!$n['leida']): ?>
                            <span class="<?= $es_staff ? 'badge bg-info text-dark' : 'hist-nueva' ?>">Nueva</span>
                        <?php else: ?>
                            <span class="<?= $es_staff ? 'text-muted small' : 'hist-leida' ?>">Leída</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($nombres_tipo[$n['tipo']] ?? $n['tipo']) ?></td>
                    <td>
                        <strong><?= $url ? '<a href="' . e($url) . '">' . e($n['titulo']) . '</a>' : e($n['titulo']) ?></strong><br>
                        <span style="color: var(--cc-texto-secundario);"><?= e($n['mensaje']) ?></span>
                    </td>
                    <td>
                        <?php if ($n['canales_externos']): ?>
                            <?php foreach (explode(', ', $n['canales_externos']) as $c):
                                [$canal, $estado] = array_pad(explode(':', $c, 2), 2, '');
                                $color = $estado === 'enviado' ? 'var(--cc-exito)' : ($estado === 'fallido' ? 'var(--cc-peligro)' : 'var(--cc-advertencia)'); ?>
                                <div style="font-size: 12px; color: <?= $color ?>;"><?= e(ucfirst($canal)) ?>: <?= e($estado) ?></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span style="font-size: 12px; color: var(--cc-texto-secundario);">solo app</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e(date('d/m/Y H:i', strtotime($n['fecha_creacion']))) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
<?php endif; ?>
    </div>

<script src="../../js/theme-toggle.js"></script>
</body>
</html>