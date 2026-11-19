<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario']) ||
   ($_SESSION['rol'] != "administrador" && $_SESSION['rol'] != "empleado")) {
    header("Location: ../home.php");
    exit;
}

require_once __DIR__ . '/../models/Caja.php';

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function plata($v) { return '$' . number_format((float) $v, 2, ',', '.'); }
function fechaHora($v) { return $v ? date('d/m/Y H:i', strtotime($v)) : '-'; }
function latin1($t) { return mb_convert_encoding((string) $t, 'ISO-8859-1', 'UTF-8'); }
function textoDiferencia($d)
{
    if ($d === null) return '-';
    $d = (float) $d;
    if (abs($d) < 0.005) return 'Justa';
    return ($d > 0 ? 'Sobrante ' : 'Faltante ') . plata(abs($d));
}

$modelo = new Caja();
$id     = (int) ($_GET['id'] ?? 0);
$detalle = $id > 0 ? $modelo->resumen($id) : [];

if ($id > 0 && !$detalle) {
    header("Location: caja_historial.php");
    exit;
}

if ($id > 0 && isset($_GET['pdf'])) {
    require_once __DIR__ . '/../reportes/ReportePDF.php';

    $c    = $detalle['caja'];
    $movs = $modelo->movimientos($id);
    $cerrada = $c['estado'] === 'Cerrada';

    $pdf = new ReportePDF('Cierre de caja #' . $id);
    $pdf->portada(
        'Cierre de caja #' . $id,
        'Estado: ' . $c['estado'] . ' · Apertura: ' . fechaHora($c['fecha_apertura']) . ' por ' . $c['usuario_apertura']
            . ($cerrada ? ' · Cierre: ' . fechaHora($c['fecha_cierre']) . ($c['usuario_cierre'] ? ' por ' . $c['usuario_cierre'] : '') : ''),
        'CAJA-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT)
    );

    $esperado = $c['efectivo_esperado'] ?? $detalle['esperado'];
    $kpis = [
        ['Efectivo inicial', plata($detalle['monto_inicial'])],
        ['Cobros en efectivo', plata($detalle['ventas_efectivo'])],
        ['Ingresos', plata($detalle['ingresos'])],
        ['Egresos', plata($detalle['egresos'])],
        ['Efectivo esperado', plata($esperado)],
    ];
    if ($cerrada) {
        $kpis[] = ['Efectivo contado', plata($c['efectivo_contado'])];
        $kpis[] = ['Diferencia', textoDiferencia($c['diferencia'])];
    }
    $pdf->seccion('Resumen');
    $pdf->kpis($kpis, 3);

    $pdf->seccion('Cobros por método de pago');
    if (!$detalle['por_metodo']) {
        $pdf->nota('Sin cobros en esta caja.');
    } else {
        $grafico = [];
        $filas = [];
        foreach ($detalle['por_metodo'] as $m) {
            $nombre = $m['metodo'] . (!empty($m['es_efectivo']) ? '' : ' (informativo)');
            $grafico[$nombre] = (float) $m['total'];
            $filas[] = [$nombre, (string) (int) $m['cantidad'], plata($m['total'])];
        }
        $pdf->torta($grafico, '', true);
        $pdf->tabla(['Método', 'Pagos', 'Total'], $filas, [100, 30, 50], ['L', 'R', 'R']);
    }

    $pdf->seccion('Ingresos y egresos');
    if (!$movs) {
        $pdf->nota('Sin movimientos.');
    } else {
        $filas = [];
        foreach ($movs as $m) {
            $filas[] = [date('d/m/Y H:i', strtotime($m['fecha'])), $m['tipo'], $m['motivo'],
                        ($m['tipo'] === 'Egreso' ? '- ' : '+ ') . plata($m['monto'])];
        }
        $pdf->tabla(['Fecha', 'Tipo', 'Motivo', 'Monto'], $filas, [34, 24, 80, 42], ['L', 'L', 'L', 'R']);
    }

    $analisis = [];
    if ($cerrada) {
        $d = (float) $c['diferencia'];
        $analisis[] = abs($d) < 0.005
            ? 'La caja cerró justa: el efectivo contado coincide con el esperado.'
            : ($d > 0 ? 'La caja cerró con un sobrante de ' . plata($d) . '.' : 'La caja cerró con un faltante de ' . plata(abs($d)) . '.');
    } else {
        $analisis[] = 'La caja todavía está abierta: el efectivo esperado puede cambiar.';
    }
    $analisis[] = 'Efectivo esperado = efectivo inicial + cobros en efectivo + ingresos - egresos. Los métodos marcados como informativos no suman al efectivo.';
    if (!empty($c['nota_cierre'])) {
        $analisis[] = 'Nota de cierre: ' . $c['nota_cierre'];
    }
    $pdf->seccion('Análisis');
    $pdf->analisis($analisis);

    $pdf->Output('I', 'cierre_caja_' . $id . '.pdf');
    exit;
}

$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
if ($desde !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = '';
if ($hasta !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = '';

$lista = [];
if ($id === 0) {
    foreach ($modelo->listar(500) as $c) {
        $dia = substr($c['fecha_apertura'], 0, 10);
        if ($desde !== '' && $dia < $desde) continue;
        if ($hasta !== '' && $dia > $hasta) continue;
        $lista[] = $c;
    }
}

$ruta_raiz = '../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Historial de cajas - CyberCore</title>
<link href="../css/theme.css" rel="stylesheet">
<?php require __DIR__ . '/_estilo_consultas.php'; ?>
<style>
    .resumen { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin: 16px 0 20px; }
    .tarjeta { background: var(--cc-superficie-2); border: 1px solid var(--cc-borde); border-radius: 12px; padding: 14px 16px; }
    .tarjeta small { color: var(--cc-texto-secundario); display: block; margin-bottom: 4px; }
    .tarjeta strong { font-size: 21px; color: var(--cc-texto); }
    h2.sec { color: var(--cc-primario); font-size: 17px; margin: 24px 0 4px; }
    .meta { text-align: center; color: var(--cc-texto-secundario); font-size: 14px; }
    .dif-mal { color: var(--cc-peligro); font-weight: 700; }
    .dif-ok { color: var(--cc-exito); font-weight: 700; }
    .acciones { display: flex; gap: 10px; flex-wrap: wrap; justify-content: center; margin-top: 12px; }
</style>
</head>
<body>
<?php require __DIR__ . '/../includes/header_admin.php'; ?>

<div class="container">
<?php if ($id > 0): $c = $detalle['caja']; $movs = $modelo->movimientos($id); ?>
    <h1>📜 Caja #<?= (int) $id ?></h1>
    <div class="meta">
        <?= e($c['estado']) ?> · abierta por <?= e($c['usuario_apertura']) ?> el <?= e(fechaHora($c['fecha_apertura'])) ?>
        <?php if ($c['estado'] === 'Cerrada'): ?> · cerrada por <?= e($c['usuario_cierre']) ?> el <?= e(fechaHora($c['fecha_cierre'])) ?><?php endif; ?>
    </div>

    <div class="resumen">
        <div class="tarjeta"><small>Efectivo inicial</small><strong><?= plata($detalle['monto_inicial']) ?></strong></div>
        <div class="tarjeta"><small>Cobros en efectivo</small><strong><?= plata($detalle['ventas_efectivo']) ?></strong></div>
        <div class="tarjeta"><small>Ingresos</small><strong><?= plata($detalle['ingresos']) ?></strong></div>
        <div class="tarjeta"><small>Egresos</small><strong><?= plata($detalle['egresos']) ?></strong></div>
        <div class="tarjeta"><small>Efectivo esperado</small><strong><?= plata($c['efectivo_esperado'] ?? $detalle['esperado']) ?></strong></div>
        <?php if ($c['estado'] === 'Cerrada'): ?>
        <div class="tarjeta"><small>Efectivo contado</small><strong><?= plata($c['efectivo_contado']) ?></strong></div>
        <div class="tarjeta"><small>Diferencia</small>
            <strong class="<?= abs((float) $c['diferencia']) < 0.005 ? 'dif-ok' : 'dif-mal' ?>"><?= e(textoDiferencia($c['diferencia'])) ?></strong></div>
        <?php endif; ?>
    </div>
    <?php if (!empty($c['nota_cierre'])): ?><p class="meta">Nota de cierre: <?= e($c['nota_cierre']) ?></p><?php endif; ?>

    <h2 class="sec">Cobros por método de pago</h2>
    <?php if (!$detalle['por_metodo']): ?>
        <div class="empty">No hubo cobros en esta caja.</div>
    <?php else: ?>
        <table>
            <tr><th>Método</th><th class="num">Pagos</th><th class="num">Total</th><th>Se cuenta en caja</th></tr>
            <?php foreach ($detalle['por_metodo'] as $m): ?>
            <tr><td><?= e($m['metodo']) ?></td><td class="num"><?= (int) $m['cantidad'] ?></td>
                <td class="num"><?= plata($m['total']) ?></td><td><?= !empty($m['es_efectivo']) ? 'Sí' : 'No, es informativo' ?></td></tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <h2 class="sec">Ingresos y egresos</h2>
    <?php if (!$movs): ?>
        <div class="empty">No hubo movimientos.</div>
    <?php else: ?>
        <table>
            <tr><th>Fecha</th><th>Tipo</th><th>Motivo</th><th>Usuario</th><th class="num">Monto</th></tr>
            <?php foreach ($movs as $m): ?>
            <tr><td><?= e(fechaHora($m['fecha'])) ?></td><td><?= e($m['tipo']) ?></td><td><?= e($m['motivo']) ?></td>
                <td><?= e($m['usuario']) ?></td><td class="num"><?= $m['tipo'] === 'Egreso' ? '− ' : '+ ' ?><?= plata($m['monto']) ?></td></tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <div class="acciones">
        <a class="btn-cc" href="caja_historial.php?id=<?= (int) $id ?>&pdf=1" target="_blank">🖨 Descargar PDF</a>
        <a class="btn-cc btn-sec" href="caja_historial.php">⬅ Volver al historial</a>
    </div>

<?php else: ?>
    <h1>📜 Historial de cajas</h1>
    <div class="subtitulo">Todas las aperturas y cierres, con su diferencia</div>

    <form method="GET" class="filtros">
        <div><label>Desde</label><input type="date" name="desde" value="<?= e($desde) ?>"></div>
        <div><label>Hasta</label><input type="date" name="hasta" value="<?= e($hasta) ?>"></div>
        <button class="btn-cc" type="submit">🔎 Filtrar</button>
        <?php if ($desde !== '' || $hasta !== ''): ?><a class="btn-cc btn-sec" href="caja_historial.php">Limpiar</a><?php endif; ?>
    </form>

    <?php if (!$lista): ?>
        <div class="empty">No hay cajas para mostrar.</div>
    <?php else: ?>
    <table>
        <tr><th>Caja</th><th>Apertura</th><th>Cierre</th><th>Abrió</th><th class="num">Inicial</th>
            <th class="num">Esperado</th><th class="num">Contado</th><th>Diferencia</th><th></th></tr>
        <?php foreach ($lista as $c): ?>
        <tr>
            <td><strong>#<?= (int) $c['id_caja'] ?></strong></td>
            <td><?= e(fechaHora($c['fecha_apertura'])) ?></td>
            <td><?= $c['estado'] === 'Abierta' ? '<em>Abierta</em>' : e(fechaHora($c['fecha_cierre'])) ?></td>
            <td><?= e($c['usuario_apertura']) ?></td>
            <td class="num"><?= plata($c['monto_inicial']) ?></td>
            <td class="num"><?= $c['efectivo_esperado'] !== null ? plata($c['efectivo_esperado']) : '-' ?></td>
            <td class="num"><?= $c['efectivo_contado'] !== null ? plata($c['efectivo_contado']) : '-' ?></td>
            <td class="<?= $c['diferencia'] !== null && abs((float) $c['diferencia']) >= 0.005 ? 'dif-mal' : '' ?>"><?= e(textoDiferencia($c['diferencia'])) ?></td>
            <td><a class="btn-cc" style="padding:6px 12px;font-size:13px;" href="caja_historial.php?id=<?= (int) $c['id_caja'] ?>">Ver</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <a href="caja.php" class="volver" style="margin-top:24px;">⬅ Volver a Caja</a>
<?php endif; ?>
</div>
<script src="../js/theme-toggle.js"></script>
</body>
</html>