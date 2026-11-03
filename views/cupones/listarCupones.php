<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'administrador') {
    header("Location: ../../home.php");
    exit;
}

require_once __DIR__ . '/../../models/Cupon.php';

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function plata($v) { return '$' . number_format((float) $v, 2, ',', '.'); }

if (empty($_SESSION['csrf_cupones'])) {
    $_SESSION['csrf_cupones'] = bin2hex(random_bytes(16));
}
$flash = $_SESSION['cupon_flash'] ?? null;
unset($_SESSION['cupon_flash']);

$cupones = (new Cupon())->listar();
$ahora  = date('Y-m-d H:i:s');

function vigencia(array $c, string $ahora): array
{
    if (!$c['es_activo']) return ['Desactivada', '#94a3b8'];
    if ($c['fecha_inicio'] && $c['fecha_inicio'] > $ahora) return ['Todavía no empezó', '#facc15'];
    if ($c['fecha_fin'] && $c['fecha_fin'] < $ahora) return ['Vencido', '#ef4444'];
    if ($c['usos_maximos'] !== null && (int) $c['usos_realizados'] >= (int) $c['usos_maximos']) return ['Agotado', '#f97316'];
    return ['Vigente', '#22c55e'];
}

$ruta_raiz = '../../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cupones - CyberCore</title>
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
    <h1>🎟 Cupones</h1>
    <div class="subtitulo">Códigos de descuento: el cliente los escribe en el checkout y se suman a las promociones</div>

    <?php if ($flash): ?><div class="aviso <?= e($flash['tipo']) ?>"><?= e($flash['texto']) ?></div><?php endif; ?>

    <p><a class="btn-cc" href="formCupon.php">+ Nuevo cupón</a></p>

    <?php if (!$cupones): ?>
        <div class="empty">Todavía no hay cupones cargados.</div>
    <?php else: ?>
    <table>
        <tr><th>Código</th><th>Descuento</th><th class="num">Compra mínima</th><th>Vigencia</th><th>Estado</th><th class="num">Usos</th><th></th></tr>
        <?php foreach ($cupones as $c): [$txt, $color] = vigencia($c, $ahora); ?>
        <tr>
            <td><strong><?= e($c['codigo']) ?></strong><?= $c['descripcion'] ? '<br><small style="color:var(--cc-texto-secundario);">' . e($c['descripcion']) . '</small>' : '' ?></td>
            <td><?= $c['tipo'] === 'Porcentaje' ? rtrim(rtrim(number_format((float) $c['valor'], 2, ',', ''), '0'), ',') . ' %' : plata($c['valor']) ?></td>
            <td class="num"><?= plata($c['compra_minima']) ?></td>
            <td><?= $c['fecha_inicio'] ? e(date('d/m/Y', strtotime($c['fecha_inicio']))) : 'Sin inicio' ?> → <?= $c['fecha_fin'] ? e(date('d/m/Y', strtotime($c['fecha_fin']))) : 'Sin fin' ?></td>
            <td><span class="badge" style="background: <?= $color ?>;"><?= e($txt) ?></span></td>
            <td class="num"><?= (int) $c['usos_realizados'] ?><?= $c['usos_maximos'] !== null ? ' / ' . (int) $c['usos_maximos'] : '' ?></td>
            <td>
                <div class="fila-acciones">
                    <a class="btn-cc mini" href="formCupon.php?id=<?= (int) $c['id_cupon'] ?>">Editar</a>
                    <form method="POST" action="../../controllers/tablasMaestrasControllers/CuponController.php">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_cupones']) ?>">
                        <input type="hidden" name="id_cupon" value="<?= (int) $c['id_cupon'] ?>">
                        <button class="btn-cc btn-sec mini" name="accion" value="<?= $c['es_activo'] ? 'desactivar' : 'activar' ?>"><?= $c['es_activo'] ? 'Desactivar' : 'Activar' ?></button>
                    </form>
                    <?php if ((int) $c['usos_realizados'] === 0): ?>
                    <form method="POST" action="../../controllers/tablasMaestrasControllers/CuponController.php"
                          onsubmit="return confirm('¿Eliminar este cupón?');">
                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_cupones']) ?>">
                        <input type="hidden" name="id_cupon" value="<?= (int) $c['id_cupon'] ?>">
                        <button class="btn-cc btn-rojo mini" name="accion" value="eliminar">Eliminar</button>
                    </form>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <p class="subtitulo" style="margin-top:14px;">El cupón se aplica sobre el subtotal que queda después de la promoción. Cada cliente puede usar un mismo cupón una sola vez.</p>
    <?php endif; ?>

    <a href="../../inicio.php" class="volver" style="margin-top:24px;">⬅ Volver al Panel</a>
</div>
<script src="../../js/theme-toggle.js"></script>
</body>
</html>