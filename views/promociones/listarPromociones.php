<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'administrador') {
    header("Location: ../../home.php");
    exit;
}

require_once __DIR__ . '/../../models/Promocion.php';

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function plata($v) { return '$' . number_format((float) $v, 2, ',', '.'); }

if (empty($_SESSION['csrf_promociones'])) {
    $_SESSION['csrf_promociones'] = bin2hex(random_bytes(16));
}
$flash = $_SESSION['promo_flash'] ?? null;
unset($_SESSION['promo_flash']);

$promos = (new Promocion())->listar();
$ahora  = date('Y-m-d H:i:s');

function vigencia(array $p, string $ahora): array
{
    if (!$p['es_activa']) return ['Desactivada', '#94a3b8'];
    if ($p['fecha_inicio'] && $p['fecha_inicio'] > $ahora) return ['Todavía no empezó', '#facc15'];
    if ($p['fecha_fin'] && $p['fecha_fin'] < $ahora) return ['Vencida', '#ef4444'];
    return ['Vigente', '#22c55e'];
}

$ruta_raiz = '../../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Promociones - CyberCore</title>
<link href="../../css/theme.css" rel="stylesheet">
<?php require __DIR__ . '/../../consultas/_estilo_consultas.php'; ?>
<style>
    .fila-acciones { display: flex; gap: 6px; flex-wrap: wrap; }
    .fila-acciones form { margin: 0; }
    .mini { padding: 6px 12px; font-size: 13px; }
    .btn-rojo { background: var(--cc-peligro); color: #fff; }
</style>
</head>
<body>
<?php require __DIR__ . '/../../includes/header_admin.php'; ?>

<div class="container">
    <h1>🏷 Promociones</h1>
    <div class="subtitulo">Descuentos automáticos: se aplican solos en el carrito cuando la compra cumple las condiciones</div>

    <?php if ($flash): ?><div class="aviso <?= e($flash['tipo']) ?>"><?= e($flash['texto']) ?></div><?php endif; ?>

    <p><a class="btn-cc" href="formPromocion.php">+ Nueva promoción</a></p>

    <?php if (!$promos): ?>
        <div class="empty">Todavía no hay promociones cargadas.</div>
    <?php else: ?>
    <table>
        <tr><th>Nombre</th><th>Descuento</th><th class="num">Compra mínima</th><th>Vigencia</th><th>Estado</th><th class="num">Usos</th><th></th></tr>
        <?php foreach ($promos as $p): [$txt, $color] = vigencia($p, $ahora); ?>
        <tr>
            <td><strong><?= e($p['nombre']) ?></strong></td>
            <td><?= $p['tipo'] === 'Porcentaje' ? rtrim(rtrim(number_format((float) $p['valor'], 2, ',', ''), '0'), ',') . ' %' : plata($p['valor']) ?></td>
            <td class="num"><?= plata($p['monto_minmo_requererido']) ?></td>
            <td><?= $p['fecha_inicio'] ? e(date('d/m/Y', strtotime($p['fecha_inicio']))) : 'Sin inicio' ?> → <?= $p['fecha_fin'] ? e(date('d/m/Y', strtotime($p['fecha_fin']))) : 'Sin fin' ?></td>
            <td><span class="badge" style="background: <?= $color ?>;"><?= e($txt) ?></span></td>
            <td class="num"><?= (int) $p['usos'] ?></td>
            <td>
                <div class="fila-acciones">
                    <a class="btn-cc mini" href="formPromocion.php?id=<?= (int) $p['id_promociones'] ?>">Editar</a>
                    <form method="POST" action="../../controllers/tablasMaestrasControllers/PromocionController.php">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_promociones']) ?>">
                        <input type="hidden" name="id_promociones" value="<?= (int) $p['id_promociones'] ?>">
                        <button class="btn-cc btn-sec mini" name="accion" value="<?= $p['es_activa'] ? 'desactivar' : 'activar' ?>"><?= $p['es_activa'] ? 'Desactivar' : 'Activar' ?></button>
                    </form>
                    <?php if ((int) $p['usos'] === 0): ?>
                    <form method="POST" action="../../controllers/tablasMaestrasControllers/PromocionController.php"
                          onsubmit="return confirm('¿Eliminar esta promoción?');">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_promociones']) ?>">
                        <input type="hidden" name="id_promociones" value="<?= (int) $p['id_promociones'] ?>">
                        <button class="btn-cc btn-rojo mini" name="accion" value="eliminar">Eliminar</button>
                    </form>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <p class="subtitulo" style="margin-top:14px;">Si varias promociones aplican a la misma compra, se usa la que más descuento da. No se suman.</p>
    <?php endif; ?>

    <a href="../../inicio.php" class="volver" style="margin-top:24px;">⬅ Volver al Panel</a>
</div>
<script src="../../js/theme-toggle.js"></script>
</body>
</html>