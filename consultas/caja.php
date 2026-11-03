<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario'], $_SESSION['id_usuario']) ||
   ($_SESSION['rol'] != "administrador" && $_SESSION['rol'] != "empleado")) {
    header("Location: ../home.php");
    exit;
}

require_once __DIR__ . '/../models/Caja.php';

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function plata($v) { return '$' . number_format((float) $v, 2, ',', '.'); }
function numero($v): float { return (float) str_replace(',', '.', trim((string) $v)); }

if (empty($_SESSION['csrf_caja'])) {
    $_SESSION['csrf_caja'] = bin2hex(random_bytes(16));
}

$modelo = new Caja();
$uid    = (int) $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf_caja'], $token)) {
        $_SESSION['caja_flash'] = ['tipo' => 'error', 'texto' => 'La sesión del formulario venció. Probá de nuevo.'];
        header("Location: caja.php");
        exit;
    }

    $accion = $_POST['accion'] ?? '';
    $r = ['ok' => false, 'error' => 'Acción no válida.'];
    $okTexto = '';

    if ($accion === 'abrir') {
        $r = $modelo->abrir($uid, numero($_POST['monto_inicial'] ?? '0'));
        $okTexto = 'Caja abierta.';
    } elseif ($accion === 'movimiento') {
        $r = $modelo->registrarMovimiento((int) ($_POST['id_caja'] ?? 0), $uid, $_POST['tipo'] ?? '',
            numero($_POST['monto'] ?? '0'), $_POST['motivo'] ?? '');
        $okTexto = 'Movimiento registrado.';
    } elseif ($accion === 'cerrar') {
        $r = $modelo->cerrar((int) ($_POST['id_caja'] ?? 0), $uid, numero($_POST['contado'] ?? ''), $_POST['nota'] ?? '');
        if (!empty($r['ok'])) {
            $d = (float) $r['diferencia'];
            $okTexto = 'Caja cerrada. ' . (abs($d) < 0.005 ? 'La caja cerró justa.' : ($d > 0 ? 'Sobrante de ' . plata($d) . '.' : 'Faltante de ' . plata(abs($d)) . '.'));
        }
    }

    $_SESSION['caja_flash'] = !empty($r['ok'])
        ? ['tipo' => 'ok', 'texto' => $okTexto]
        : ['tipo' => 'error', 'texto' => $r['error'] ?? 'No se pudo completar la acción.'];
    header("Location: caja.php");
    exit;
}

$flash = $_SESSION['caja_flash'] ?? null;
unset($_SESSION['caja_flash']);

$abierta = $modelo->abierta();
$resumen = $abierta ? $modelo->resumen((int) $abierta['id_caja']) : null;
$movs    = $abierta ? $modelo->movimientos((int) $abierta['id_caja']) : [];

$ruta_raiz = '../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Caja - CyberCore</title>
<link href="../css/theme.css" rel="stylesheet">
<?php require __DIR__ . '/_estilo_consultas.php'; ?>
<style>
    .resumen { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin: 16px 0 20px; }
    .tarjeta { background: var(--cc-superficie-2); border: 1px solid var(--cc-borde); border-radius: 12px; padding: 14px 16px; }
    .tarjeta small { color: var(--cc-texto-secundario); display: block; margin-bottom: 4px; }
    .tarjeta strong { font-size: 21px; color: var(--cc-texto); }
    .tarjeta.esperado { border-color: var(--cc-primario); }
    .cols { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 18px; margin-top: 10px; }
    .panel { background: var(--cc-superficie-2); border: 1px solid var(--cc-borde); border-radius: 12px; padding: 18px; }
    .panel h3 { margin: 0 0 12px; color: var(--cc-primario); font-size: 16px; }
    .panel label { display: block; font-size: 12px; color: var(--cc-texto-secundario); margin: 10px 0 4px; font-weight: 600; }
    .panel input, .panel select { width: 100%; box-sizing: border-box; background: var(--cc-superficie); color: var(--cc-texto);
        border: 1px solid var(--cc-borde); border-radius: 8px; padding: 9px 10px; font-size: 14px; }
    .panel button { margin-top: 14px; }
    .estado-caja { text-align: center; margin: 6px 0 4px; }
    .estado-caja span { display: inline-block; padding: 5px 14px; border-radius: 20px; font-weight: 700; font-size: 13px; color: #0B1220; background: #3DDC97; }
    .meta { text-align: center; color: var(--cc-texto-secundario); font-size: 14px; }
    h2.sec { color: var(--cc-primario); font-size: 17px; margin: 24px 0 4px; }
    .barra-links { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; margin-top: 8px; }
</style>
</head>
<body>
<?php require __DIR__ . '/../includes/header_admin.php'; ?>

<div class="container">
    <h1>🧮 Caja</h1>
    <div class="subtitulo">Apertura, movimientos y cierre del efectivo</div>

    <?php if ($flash): ?><div class="aviso <?= e($flash['tipo']) ?>"><?= e($flash['texto']) ?></div><?php endif; ?>

    <?php if (!$abierta): ?>
        <div class="empty">No hay ninguna caja abierta.</div>
        <div class="cols" style="max-width:420px;margin:0 auto;">
            <form method="POST" class="panel">
                <h3>Abrir caja</h3>
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_caja']) ?>">
                <input type="hidden" name="accion" value="abrir">
                <label>Efectivo con el que empieza</label>
                <input type="number" name="monto_inicial" step="0.01" min="0" value="0" required>
                <button class="btn-cc" type="submit">Abrir caja</button>
            </form>
        </div>
    <?php else: ?>
        <div class="estado-caja"><span>● Caja abierta</span></div>
        <div class="meta">Caja #<?= (int) $abierta['id_caja'] ?> · abierta por <?= e($abierta['usuario_apertura']) ?>
            el <?= e(date('d/m/Y H:i', strtotime($abierta['fecha_apertura']))) ?></div>

        <div class="resumen">
            <div class="tarjeta"><small>Efectivo inicial</small><strong><?= plata($resumen['monto_inicial']) ?></strong></div>
            <div class="tarjeta"><small>Cobros en efectivo</small><strong><?= plata($resumen['ventas_efectivo']) ?></strong></div>
            <div class="tarjeta"><small>Ingresos</small><strong><?= plata($resumen['ingresos']) ?></strong></div>
            <div class="tarjeta"><small>Egresos</small><strong><?= plata($resumen['egresos']) ?></strong></div>
            <div class="tarjeta esperado"><small>Efectivo que debería haber</small><strong><?= plata($resumen['esperado']) ?></strong></div>
        </div>

        <?php if ($resumen['por_metodo']): ?>
            <h2 class="sec">Cobros de esta caja por método</h2>
            <table>
                <tr><th>Método</th><th class="num">Pagos</th><th class="num">Total</th><th>Se cuenta en caja</th></tr>
                <?php foreach ($resumen['por_metodo'] as $m): ?>
                <tr><td><?= e($m['metodo']) ?></td><td class="num"><?= (int) $m['cantidad'] ?></td>
                    <td class="num"><?= plata($m['total']) ?></td><td><?= !empty($m['es_efectivo']) ? 'Sí' : 'No, es informativo' ?></td></tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <div class="cols">
            <form method="POST" class="panel">
                <h3>Registrar ingreso o egreso</h3>
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_caja']) ?>">
                <input type="hidden" name="accion" value="movimiento">
                <input type="hidden" name="id_caja" value="<?= (int) $abierta['id_caja'] ?>">
                <label>Tipo</label>
                <select name="tipo"><option value="Egreso">Egreso (sale plata)</option><option value="Ingreso">Ingreso (entra plata)</option></select>
                <label>Monto</label>
                <input type="number" name="monto" step="0.01" min="0.01" required>
                <label>Motivo</label>
                <input type="text" name="motivo" maxlength="200" placeholder="Ej: pago a proveedor" required>
                <button class="btn-cc" type="submit">Registrar</button>
            </form>

            <form method="POST" class="panel" onsubmit="return confirm('¿Cerrar la caja? Después no se puede modificar.');">
                <h3>Cerrar caja</h3>
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_caja']) ?>">
                <input type="hidden" name="accion" value="cerrar">
                <input type="hidden" name="id_caja" value="<?= (int) $abierta['id_caja'] ?>">
                <label>Efectivo contado (lo que realmente hay)</label>
                <input type="number" name="contado" step="0.01" min="0" required>
                <label>Nota (opcional)</label>
                <input type="text" name="nota" maxlength="255" placeholder="Ej: faltaron $50 de vuelto">
                <button class="btn-cc" type="submit">Cerrar caja</button>
            </form>
        </div>

        <h2 class="sec">Movimientos de esta caja</h2>
        <?php if (!$movs): ?>
            <div class="empty">Todavía no hay ingresos ni egresos anotados.</div>
        <?php else: ?>
            <table>
                <tr><th>Hora</th><th>Tipo</th><th>Motivo</th><th>Usuario</th><th class="num">Monto</th></tr>
                <?php foreach ($movs as $m): ?>
                <tr><td><?= e(date('d/m H:i', strtotime($m['fecha']))) ?></td><td><?= e($m['tipo']) ?></td>
                    <td><?= e($m['motivo']) ?></td><td><?= e($m['usuario']) ?></td>
                    <td class="num"><?= $m['tipo'] === 'Egreso' ? '− ' : '+ ' ?><?= plata($m['monto']) ?></td></tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    <?php endif; ?>

    <div class="barra-links">
        <a href="caja_historial.php" class="btn-cc btn-sec">📜 Historial de cajas</a>
        <a href="transacciones.php" class="btn-cc btn-sec">💳 Transacciones</a>
    </div>
    <a href="menu_consultas.php" class="volver" style="margin-top:24px;">⬅ Volver a Consultas</a>
</div>
<script src="../js/theme-toggle.js"></script>
</body>
</html>