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

$id = (int) ($_GET['id'] ?? 0);
$d  = $id > 0 ? (new Devolucion())->obtenerAdmin($id) : null;
if (!$d) {
    header("Location: listarDevoluciones.php");
    exit;
}

if (empty($_SESSION['csrf_admin_devoluciones'])) {
    $_SESSION['csrf_admin_devoluciones'] = bin2hex(random_bytes(16));
}
$flash = $_SESSION['devolucion_flash'] ?? null;
unset($_SESSION['devolucion_flash']);

$abierta = Devolucion::estaAbierta($d['estado']);
$info    = Devolucion::infoEstado($d['estado']);
$ruta_raiz = '../../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Devolución #<?= $id ?> - CyberCore</title>
<link href="../../css/theme.css" rel="stylesheet">
<?php require __DIR__ . '/../../consultas/_estilo_consultas.php'; ?>
<style>
    .campo { width: 100%; box-sizing: border-box; background: var(--cc-superficie-2); color: var(--cc-texto);
        border: 1px solid var(--cc-borde); border-radius: 8px; padding: 10px; font-family: inherit; font-size: 14px; }
    .acciones { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 12px; }
    .btn-rojo { background: var(--cc-peligro); color: #fff; }
    .btn-rojo:hover { background: var(--cc-peligro); filter: brightness(1.1); }
    .dato { margin: 4px 0; }
</style>
</head>
<body>
<?php require __DIR__ . '/../../includes/header_admin.php'; ?>

<div class="container">
    <h1>↩ Devolución #<?= $id ?></h1>
    <div class="subtitulo">
        <span class="badge" style="background: <?= e($info['color']) ?>;"><?= $info['emoji'] ?> <?= e($abierta ? 'Pendiente' : $d['estado']) ?></span>
    </div>

    <?php if ($flash): ?><div class="aviso <?= e($flash['tipo']) ?>"><?= e($flash['texto']) ?></div><?php endif; ?>

    <p class="dato"><strong>Cliente:</strong> <?= e($d['nombre'] . ' ' . $d['apellido']) ?></p>
    <p class="dato"><strong>Pedido:</strong> <a href="../pedidos/verPedido.php?id=<?= (int) $d['rela_id_pedidos'] ?>">#<?= (int) $d['rela_id_pedidos'] ?></a> (<?= e($d['estado_pedido']) ?>, <?= plata($d['monto_total']) ?>)</p>
    <p class="dato"><strong>Pedida el:</strong> <?= $d['fecha_solicitud'] ? e(date('d/m/Y', strtotime($d['fecha_solicitud']))) : '' ?></p>
    <p class="dato"><strong>Motivo:</strong> <?= nl2br(e($d['motivo'])) ?></p>

    <h2 style="margin-top:24px;">Productos a devolver</h2>
    <?php if ($d['items']): ?>
    <table>
        <tr><th>Producto</th><th class="num">Cantidad</th><th class="num">Monto</th></tr>
        <?php foreach ($d['items'] as $it): ?>
        <tr>
            <td><?= e($it['codigo']) ?> · <?= e($it['nombre']) ?></td>
            <td class="num"><?= (int) $it['cantidad'] ?></td>
            <td class="num"><?= plata($it['monto']) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr><td colspan="2" class="num"><strong>Reembolso</strong></td><td class="num"><strong><?= plata($d['monto_reembolso']) ?></strong></td></tr>
    </table>
    <?php else: ?>
        <div class="empty">Esta solicitud no tiene detalle de productos. Reembolso registrado: <?= plata($d['monto_reembolso']) ?>.</div>
    <?php endif; ?>

    <?php if ($abierta): ?>
    <h2 style="margin-top:24px;">Resolver</h2>
    <form method="POST" action="../../controllers/pedidosControllers/DevolucionController.php">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_admin_devoluciones']) ?>">
        <input type="hidden" name="id_devolucion" value="<?= $id ?>">
        <label style="font-size:13px;color:var(--cc-texto-secundario);">Mensaje para el cliente (obligatorio si rechazás)</label>
        <textarea name="nota" class="campo" rows="3" maxlength="255"></textarea>
        <div class="acciones">
            <button class="btn-cc" type="submit" name="accion" value="aprobar"
                    onclick="return confirm('¿Aprobar la devolución? Los productos vuelven al stock y se avisa al cliente.');">✔ Aprobar y devolver al stock</button>
            <button class="btn-cc btn-rojo" type="submit" name="accion" value="rechazar"
                    onclick="return confirm('¿Rechazar la devolución?');">✖ Rechazar</button>
        </div>
    </form>
    <?php else: ?>
        <p class="dato" style="margin-top:20px;"><strong>Resuelta:</strong>
            <?= $d['fecha_resolucion'] ? e(date('d/m/Y H:i', strtotime($d['fecha_resolucion']))) : '' ?>
            <?= !empty($d['autor_resolucion']) ? 'por ' . e($d['autor_resolucion']) : '' ?></p>
        <?php if (!empty($d['nota_admin'])): ?><p class="dato"><strong>Mensaje al cliente:</strong> <?= e($d['nota_admin']) ?></p><?php endif; ?>
    <?php endif; ?>

    <a href="listarDevoluciones.php" class="volver" style="margin-top:24px;">⬅ Volver a Devoluciones</a>
</div>
<script src="../../js/theme-toggle.js"></script>
</body>
</html>