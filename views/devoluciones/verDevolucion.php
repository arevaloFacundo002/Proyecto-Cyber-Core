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
function fechahora($v) { return $v ? e(date('d/m/Y H:i', strtotime($v))) : ''; }

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
$estado  = $abierta ? 'Pendiente' : $d['estado'];
$negocio = devolucion_datos_negocio();
$envioP  = $d['envio_pedido'] ?? [];
$ruta_raiz = '../../';

$pasos = ['Pendiente' => 'Pedida', 'Aprobada' => 'Aprobada', 'Producto enviado' => 'Producto enviado', 'Recibida' => 'Recibida'];
$orden = array_keys($pasos);
$posActual = array_search($estado, $orden, true);
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
    .dos { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; margin-top: 18px; }
    .tarjeta { background: var(--cc-superficie); border: 1px solid var(--cc-borde); border-radius: 12px; padding: 16px; }
    .tarjeta h3 { margin: 0 0 8px; color: var(--cc-primario); font-size: 16px; }
    .pasos { display: flex; gap: 6px; flex-wrap: wrap; margin: 14px 0 4px; }
    .paso { padding: 6px 14px; border-radius: 20px; border: 1px solid var(--cc-borde); color: var(--cc-texto-secundario); font-size: 13px; }
    .paso.hecho { background: var(--cc-primario); color: var(--cc-texto-sobre-primario); border-color: var(--cc-primario); font-weight: 600; }
    .linea { border-left: 3px solid var(--cc-borde); padding: 2px 0 8px 12px; margin-left: 4px; }
    .linea.hecha { border-left-color: var(--cc-primario); }
    .linea small { color: var(--cc-texto-secundario); }
</style>
</head>
<body>
<?php require __DIR__ . '/../../includes/header_admin.php'; ?>

<div class="container">
    <h1>↩ Devolución #<?= $id ?></h1>
    <div class="subtitulo">
        <span class="badge" style="background: <?= e($info['color']) ?>;"><?= $info['emoji'] ?> <?= e($estado) ?></span>
    </div>

    <?php if ($flash): ?><div class="aviso <?= e($flash['tipo']) ?>"><?= e($flash['texto']) ?></div><?php endif; ?>

    <?php if ($estado !== 'Rechazada'): ?>
    <div class="pasos">
        <?php foreach ($pasos as $clave => $texto): ?>
            <span class="paso <?= ($posActual !== false && array_search($clave, $orden, true) <= $posActual) ? 'hecho' : '' ?>"><?= e($texto) ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="dos">
        <div class="tarjeta">
            <h3>👤 Datos del cliente</h3>
            <p class="dato"><strong>Nombre:</strong> <?= e($d['nombre'] . ' ' . $d['apellido']) ?></p>
            <?php if (!empty($d['correo'])): ?>
                <p class="dato"><strong>Correo:</strong> <a href="mailto:<?= e($d['correo']) ?>"><?= e($d['correo']) ?></a></p>
            <?php endif; ?>
            <?php foreach ($d['contactos'] as $c): ?>
                <?php $soloNum = preg_replace('/\D/', '', $c['valor']); ?>
                <p class="dato"><strong><?= e($c['tipo']) ?>:</strong>
                    <?php if (stripos($c['tipo'], 'whatsapp') !== false && $soloNum !== ''): ?>
                        <a href="https://wa.me/<?= e($soloNum) ?>" target="_blank" rel="noopener"><?= e($c['valor']) ?></a>
                    <?php else: ?><?= e($c['valor']) ?><?php endif; ?></p>
            <?php endforeach; ?>
            <?php if (!empty($envioP['recibe_telefono'])): ?>
                <p class="dato"><strong>Tel. de quien recibió el pedido:</strong> <?= e($envioP['recibe_telefono']) ?></p>
            <?php endif; ?>
            <?php if (!empty($envioP['direccion_entrega'])): ?>
                <p class="dato"><strong>Dirección de entrega del pedido:</strong> <?= e($envioP['direccion_entrega']) ?>
                    <?= !empty($envioP['recibe_nombre']) ? ' (recibió ' . e($envioP['recibe_nombre']) . ')' : '' ?></p>
            <?php endif; ?>
            <p class="dato"><strong>Pedido:</strong> <a href="../pedidos/verPedido.php?id=<?= (int) $d['rela_id_pedidos'] ?>">#<?= (int) $d['rela_id_pedidos'] ?></a> (<?= e($d['estado_pedido']) ?>, <?= plata($d['monto_total']) ?>)</p>
        </div>

        <div class="tarjeta">
            <h3>🏬 Datos del negocio (lo que ve el cliente)</h3>
            <p class="dato"><strong>Dirección:</strong> <?= e($negocio['domicilio']) ?></p>
            <p class="dato"><strong>Horario:</strong> <?= e($negocio['horario']) ?></p>
            <?php if ($negocio['telefono'] !== ''): ?><p class="dato"><strong>Teléfono:</strong> <?= e($negocio['telefono']) ?></p><?php endif; ?>
            <?php if ($negocio['whatsapp'] !== ''): ?><p class="dato"><strong>WhatsApp:</strong> <?= e($negocio['whatsapp']) ?></p><?php endif; ?>
            <?php if ($negocio['email'] !== ''): ?><p class="dato"><strong>Correo:</strong> <?= e($negocio['email']) ?></p><?php endif; ?>
        </div>
    </div>

    <h2 style="margin-top:24px;">Motivo</h2>
    <p class="dato"><?= nl2br(e($d['motivo'])) ?>
        <?php if (!empty($d['tipo_motivo'])): ?>
            <span class="badge" style="background: <?= Devolucion::esFalla($d['tipo_motivo']) ? 'var(--cc-peligro)' : 'var(--cc-texto-secundario)' ?>;color:#fff;">
                <?= Devolucion::esFalla($d['tipo_motivo']) ? 'Error / falla nuestra' : 'Decisión del cliente' ?></span>
        <?php endif; ?>
    </p>

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
        <?php if ((float) ($d['monto_envio'] ?? 0) > 0): ?>
        <tr><td colspan="2" class="num">Envío (proporcional)</td><td class="num"><?= plata($d['monto_envio']) ?></td></tr>
        <?php endif; ?>
        <tr><td colspan="2" class="num"><strong>Reembolso</strong></td><td class="num"><strong><?= plata($d['monto_reembolso']) ?></strong></td></tr>
    </table>
    <?php else: ?>
        <div class="empty">Esta solicitud no tiene detalle de productos. Reembolso registrado: <?= plata($d['monto_reembolso']) ?>.</div>
    <?php endif; ?>

    <h2 style="margin-top:24px;">Seguimiento</h2>
    <div class="linea hecha"><strong>Pedida</strong> <small><?= $d['fecha_solicitud'] ? e(date('d/m/Y', strtotime($d['fecha_solicitud']))) : '' ?></small></div>
    <?php if (!$abierta): ?>
        <div class="linea hecha"><strong><?= $estado === 'Rechazada' ? 'Rechazada' : 'Aprobada' ?></strong>
            <small><?= fechahora($d['fecha_resolucion']) ?><?= !empty($d['autor_resolucion']) ? ' · por ' . e($d['autor_resolucion']) : '' ?></small>
            <?php if (!empty($d['nota_admin'])): ?><br><small>Mensaje al cliente: <?= e($d['nota_admin']) ?></small><?php endif; ?></div>
    <?php endif; ?>
    <?php if (!empty($d['fecha_despacho'])): ?>
        <div class="linea hecha"><strong>Producto enviado</strong> <small><?= fechahora($d['fecha_despacho']) ?></small><br>
            <?php if ($d['metodo_entrega'] === 'local'): ?>
                <small>El cliente avisó que lo lleva al local.</small>
            <?php else: ?>
                <small>Empresa: <?= e($d['empresa_transporte']) ?> · Seguimiento: <strong><?= e($d['num_seguimiento']) ?></strong></small>
            <?php endif; ?></div>
    <?php endif; ?>
    <?php if (!empty($d['fecha_recibida'])): ?>
        <div class="linea hecha"><strong>Recibida</strong> <small><?= fechahora($d['fecha_recibida']) ?></small><br>
            <small><?= (int) $d['vuelve_stock'] === 1 ? 'Volvió al stock.' : 'No volvió al stock.' ?><?= !empty($d['nota_recepcion']) ? ' ' . e($d['nota_recepcion']) : '' ?></small></div>
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
                    onclick="return confirm('¿Aprobar la devolución? Se le explica al cliente cómo mandar el producto.');">✔ Aprobar devolución</button>
            <button class="btn-cc btn-rojo" type="submit" name="accion" value="rechazar"
                    onclick="return confirm('¿Rechazar la devolución?');">✖ Rechazar</button>
        </div>
    </form>
    <?php elseif (in_array($estado, ['Aprobada', 'Producto enviado'], true)): ?>
    <h2 style="margin-top:24px;">Recibir el producto</h2>
    <p class="dato" style="color:var(--cc-texto-secundario);">
        <?= $estado === 'Aprobada' ? 'Esperando que el cliente envíe o traiga el producto. Si ya lo tenés en mano, podés marcarlo como recibido.' : 'El cliente ya avisó que lo despachó. Cuando te llegue, revisalo y registralo acá.' ?></p>
    <?php $sugiere_stock = !Devolucion::esFalla($d['tipo_motivo'] ?? null); ?>
    <form method="POST" action="../../controllers/pedidosControllers/DevolucionController.php">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_admin_devoluciones']) ?>">
        <input type="hidden" name="id_devolucion" value="<?= $id ?>">
        <p class="dato"><strong>¿Qué pasa con el producto devuelto?</strong></p>
        <label style="display:block;margin:4px 0;"><input type="radio" name="destino_stock" value="si" <?= $sugiere_stock ? 'checked' : '' ?>>
            Está en buen estado: vuelve al stock</label>
        <label style="display:block;margin:4px 0 14px;"><input type="radio" name="destino_stock" value="no" <?= $sugiere_stock ? '' : 'checked' ?>>
            Está roto o con falla: no vuelve al stock</label>
        <label style="font-size:13px;color:var(--cc-texto-secundario);">Nota (opcional, el cliente la ve)</label>
        <textarea name="nota" class="campo" rows="2" maxlength="255"></textarea>
        <div class="acciones">
            <button class="btn-cc" type="submit" name="accion" value="recibir"
                    onclick="return confirm('¿Confirmás que recibiste el producto? Se avisa al cliente.');">📥 Marcar como recibido</button>
        </div>
    </form>
    <?php endif; ?>

    <a href="listarDevoluciones.php" class="volver" style="margin-top:24px;">⬅ Volver a Devoluciones</a>
</div>
<script src="../../js/theme-toggle.js"></script>
</body>
</html>