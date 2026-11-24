<?php
// MÓDULO DE AUDITORÍA - solo administrador (ver auth/auth.php)
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../models/Auditoria.php';

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function plata($v) { return '$' . number_format((float) $v, 2, ',', '.'); }

$aud = new Auditoria();

// ---------- filtros ----------
$vista = in_array($_GET['vista'] ?? '', ['registro', 'caja', 'usuarios'], true) ? $_GET['vista'] : 'registro';
$f = [
    'desde'      => $_GET['desde'] ?? date('Y-m-d', strtotime('-30 days')),
    'hasta'      => $_GET['hasta'] ?? date('Y-m-d'),
    'usuario'    => $_GET['usuario'] ?? '',
    'modulo'     => $_GET['modulo'] ?? '',
    'accion'     => $_GET['accion'] ?? '',
    'criticidad' => $_GET['criticidad'] ?? '',
    'q'          => trim($_GET['q'] ?? ''),
    'tabla'      => $_GET['tabla'] ?? '',
    'registro'   => $_GET['registro'] ?? '',
];
$trazabilidad = ($f['tabla'] !== '' && $f['registro'] !== '');
$qs_filtros = http_build_query(array_filter($f, fn($v) => $v !== ''));

// ---------- exportar CSV (abre directo en Excel) ----------
if (($_GET['export'] ?? '') === 'csv') {
    $filas = $aud->listar($f, 20000, 0);
    Auditoria::registrar('Auditoría', 'EXPORTAR', 'Exportó la bitácora de auditoría a CSV (' . count($filas) . ' filas)', 'Media');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="auditoria_' . date('Ymd_His') . '.csv"');
    $o = fopen('php://output', 'w');
    fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, ['ID', 'Fecha', 'Usuario', 'Rol', 'IP', 'Módulo', 'Acción', 'Criticidad', 'Tabla', 'Registro', 'Descripción', 'Datos antes', 'Datos después'], ';');
    foreach ($filas as $r) {
        fputcsv($o, [$r['id_auditoria'], $r['fecha'], $r['usuario_nombre'], $r['rol'], $r['ip'], $r['modulo'], $r['accion'],
            $r['criticidad'], $r['tabla'], $r['registro_id'], $r['descripcion'], $r['datos_antes'], $r['datos_despues']], ';');
    }
    fclose($o);
    exit;
}

// ---------- datos ----------
$porPag = 50;
$pag    = max(1, (int) ($_GET['pag'] ?? 1));
$kpis = $filas = $cajas = $actividad = $ventas = [];
$total = 0;

if ($vista === 'registro') {
    $kpis  = $aud->kpis($f);
    $total = $aud->contar($f);
    $filas = $aud->listar($f, $porPag, ($pag - 1) * $porPag);
} elseif ($vista === 'caja') {
    $cajas = $aud->cajas($f['desde'], $f['hasta']);
} else {
    $actividad = $aud->actividadUsuarios($f['desde'], $f['hasta']);
    $ventas    = $aud->ventasPorUsuario($f['desde'], $f['hasta']);
}
$paginas = max(1, (int) ceil($total / $porPag));

function chip_crit(string $c): string
{
    $col = ['Alta' => '#EF4444', 'Media' => '#F59E0B', 'Baja' => '#3DDC97'][$c] ?? '#7C8DB5';
    return '<span class="badge" style="background:' . $col . '">' . e($c) . '</span>';
}
function url_vista(string $v): string
{
    $q = $_GET; $q['vista'] = $v; unset($q['pag'], $q['export']);
    return '?' . http_build_query($q);
}
function detalle_cambios(?string $antes, ?string $despues): string
{
    $a = $antes ? json_decode($antes, true) : null;
    $d = $despues ? json_decode($despues, true) : null;
    if (!is_array($a) && !is_array($d)) return '<em>Sin detalle de campos.</em>';
    $a = is_array($a) ? $a : []; $d = is_array($d) ? $d : [];
    $html = '<table class="det"><tr><th>Campo</th><th>Antes</th><th>Después</th></tr>';
    foreach (array_unique(array_merge(array_keys($a), array_keys($d))) as $k) {
        $va = $a[$k] ?? null; $vd = $d[$k] ?? null;
        $cambio = ($va !== $vd) && $a && $d;
        $html .= '<tr' . ($cambio ? ' class="cambio"' : '') . '><td>' . e($k) . '</td><td>' . ($va === null ? '—' : e($va)) . '</td><td>' . ($vd === null ? '—' : e($vd)) . '</td></tr>';
    }
    return $html . '</table>';
}
$ruta_raiz = '../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Auditoría - CyberCore</title>
<link href="../css/theme.css" rel="stylesheet">
<?php require __DIR__ . '/_estilo_consultas.php'; ?>
<style>
    .container { max-width: 1300px; }
    .tabs { display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; margin: 14px 0 18px; }
    .tabs a { padding: 9px 18px; border-radius: 20px; text-decoration: none; font-weight: 700; font-size: 14px;
        border: 1px solid var(--cc-primario); color: var(--cc-primario); }
    .tabs a.activa { background: var(--cc-primario); color: var(--cc-texto-sobre-primario); }
    .kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; margin: 6px 0 18px; }
    .kpi { background: var(--cc-superficie-2); border: 1px solid var(--cc-borde); border-radius: 12px; padding: 14px 16px; }
    .kpi small { color: var(--cc-texto-secundario); display: block; margin-bottom: 4px; }
    .kpi strong { font-size: 24px; }
    .kpi.rojo strong { color: var(--cc-peligro); }
    .acciones { display: flex; gap: 10px; flex-wrap: wrap; margin: 0 0 14px; }
    .tabla-scroll { overflow-x: auto; }
    td, th { font-size: 13px; vertical-align: top; }
    details summary { cursor: pointer; color: var(--cc-primario); font-weight: 600; }
    table.det { margin: 8px 0 0; width: auto; min-width: 360px; }
    table.det th, table.det td { padding: 5px 10px; font-size: 12px; }
    table.det tr.cambio td { background: rgba(245,158,11,.18); font-weight: 700; }
    .pager { display: flex; gap: 6px; justify-content: center; margin-top: 16px; flex-wrap: wrap; }
    .pager a, .pager span { padding: 6px 12px; border-radius: 8px; border: 1px solid var(--cc-borde); text-decoration: none; color: var(--cc-texto); font-size: 13px; }
    .pager span.act { background: var(--cc-primario); color: var(--cc-texto-sobre-primario); font-weight: 700; }
    .banner { border: 1px solid var(--cc-primario); border-radius: 10px; padding: 10px 14px; margin-bottom: 14px; color: var(--cc-primario); font-weight: 600; }
    .alerta { color: var(--cc-peligro); font-weight: 700; }
    .solo-impresion { display: none; }
    h2.sec { color: var(--cc-primario); font-size: 17px; margin: 24px 0 4px; }
    @media print {
        body { background: #fff !important; color: #000 !important; }
        .container { box-shadow: none; border: none; width: 100%; max-width: none; margin: 0; padding: 0; background: #fff; }
        header, .cc-barra-volver, .tabs, .filtros, .acciones, .pager, nav, .no-print { display: none !important; }
        .solo-impresion { display: block; margin-bottom: 10px; }
        th, td { color: #000 !important; border-bottom: 1px solid #999 !important; background: #fff !important; font-size: 10px; }
        h1, h2.sec { color: #000 !important; text-shadow: none; }
        details { display: block; } details > summary { display: none; }
        .badge { color: #000 !important; border: 1px solid #000; background: #fff !important; }
    }
</style>
</head>
<body>
<?php require __DIR__ . '/../includes/header_admin.php'; ?>
<?php $volver_url = '../inicio.php'; $volver_texto = '⬅ Volver al panel'; require __DIR__ . '/_barra_volver.php'; ?>

<div class="container">
    <h1>🛡️ Auditoría</h1>
    <div class="subtitulo">Registro de acciones de usuarios, operaciones críticas y trazabilidad de cambios</div>
    <div class="solo-impresion">
        <strong>CyberCore – Informe de auditoría</strong><br>
        Período: <?= e($f['desde']) ?> a <?= e($f['hasta']) ?> · Emitido por <?= e($_SESSION['usuario']) ?> el <?= date('d/m/Y H:i') ?>
    </div>

    <div class="tabs">
        <a href="<?= e(url_vista('registro')) ?>" class="<?= $vista === 'registro' ? 'activa' : '' ?>">📋 Registro de actividad</a>
        <a href="<?= e(url_vista('caja')) ?>" class="<?= $vista === 'caja' ? 'activa' : '' ?>">🧮 Control de caja</a>
        <a href="<?= e(url_vista('usuarios')) ?>" class="<?= $vista === 'usuarios' ? 'activa' : '' ?>">👥 Actividad por usuario</a>
    </div>

    <?php if ($trazabilidad): ?>
        <div class="banner">🔎 Historial completo de <strong><?= e($f['tabla']) ?></strong> · registro <strong><?= e($f['registro']) ?></strong>
            — <a href="?vista=registro" style="color:inherit">ver todo el registro</a></div>
    <?php endif; ?>

    <?php if (!$trazabilidad): ?>
    <form method="get" class="filtros">
        <input type="hidden" name="vista" value="<?= e($vista) ?>">
        <div><label>Desde</label><input type="date" name="desde" value="<?= e($f['desde']) ?>"></div>
        <div><label>Hasta</label><input type="date" name="hasta" value="<?= e($f['hasta']) ?>"></div>
        <?php if ($vista === 'registro'): ?>
        <div><label>Usuario</label>
            <select name="usuario"><option value="">Todos</option>
                <?php foreach ($aud->usuariosPersonal() as $u): ?>
                    <option value="<?= (int) $u['id_usuario'] ?>" <?= (string) $f['usuario'] === (string) $u['id_usuario'] ? 'selected' : '' ?>><?= e($u['nombre']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div><label>Módulo</label>
            <select name="modulo"><option value="">Todos</option>
                <?php foreach ($aud->opciones('modulo') as $m): ?><option <?= $f['modulo'] === $m ? 'selected' : '' ?>><?= e($m) ?></option><?php endforeach; ?>
            </select></div>
        <div><label>Acción</label>
            <select name="accion"><option value="">Todas</option>
                <?php foreach ($aud->opciones('accion') as $m): ?><option <?= $f['accion'] === $m ? 'selected' : '' ?>><?= e($m) ?></option><?php endforeach; ?>
            </select></div>
        <div><label>Criticidad</label>
            <select name="criticidad"><option value="">Todas</option>
                <?php foreach (['Alta', 'Media', 'Baja'] as $m): ?><option <?= $f['criticidad'] === $m ? 'selected' : '' ?>><?= $m ?></option><?php endforeach; ?>
            </select></div>
        <div><label>Buscar</label><input type="text" name="q" placeholder="texto, usuario, id…" value="<?= e($f['q']) ?>"></div>
        <?php endif; ?>
        <div><button class="btn-cc" type="submit">Filtrar</button></div>
    </form>
    <?php endif; ?>

    <div class="acciones no-print">
        <button class="btn-cc btn-sec" onclick="window.print()">🖨️ Imprimir / PDF</button>
        <?php if ($vista === 'registro'): ?>
            <a class="btn-cc btn-sec" href="?<?= e($qs_filtros) ?>&vista=registro&export=csv">📥 Exportar a Excel (CSV)</a>
        <?php endif; ?>
    </div>

<?php if ($vista === 'registro'): ?>
    <div class="kpis">
        <div class="kpi"><small>Eventos registrados</small><strong><?= number_format((int) $kpis['total'], 0, ',', '.') ?></strong></div>
        <div class="kpi rojo"><small>Operaciones críticas</small><strong><?= (int) $kpis['criticos'] ?></strong></div>
        <div class="kpi rojo"><small>Intentos de login fallidos</small><strong><?= (int) $kpis['fallidos'] ?></strong></div>
        <div class="kpi"><small>Eliminaciones</small><strong><?= (int) $kpis['eliminaciones'] ?></strong></div>
        <div class="kpi"><small>Usuarios activos</small><strong><?= (int) $kpis['usuarios'] ?></strong></div>
    </div>

    <div class="tabla-scroll">
    <table>
        <thead><tr><th>Fecha y hora</th><th>Usuario</th><th>Módulo</th><th>Acción</th><th>Criticidad</th><th>Descripción / detalle</th><th>IP</th><th class="no-print"></th></tr></thead>
        <tbody>
        <?php if (!$filas): ?>
            <tr><td colspan="8" class="empty">No hay eventos para los filtros elegidos.</td></tr>
        <?php endif; ?>
        <?php foreach ($filas as $r): ?>
            <tr>
                <td><?= e(date('d/m/Y H:i:s', strtotime($r['fecha']))) ?></td>
                <td><strong><?= e($r['usuario_nombre']) ?></strong><br><small><?= e($r['rol']) ?></small></td>
                <td><?= e($r['modulo']) ?></td>
                <td><?= e($r['accion']) ?></td>
                <td><?= chip_crit($r['criticidad']) ?></td>
                <td>
                    <?= e($r['descripcion']) ?>
                    <?php if ($r['datos_antes'] || $r['datos_despues']): ?>
                        <details><summary>Ver antes / después</summary><?= detalle_cambios($r['datos_antes'], $r['datos_despues']) ?></details>
                    <?php endif; ?>
                </td>
                <td><?= e($r['ip']) ?></td>
                <td class="no-print"><?php if ($r['tabla'] && $r['registro_id'] !== null): ?>
                    <a title="Historial de este registro" href="?vista=registro&tabla=<?= urlencode($r['tabla']) ?>&registro=<?= urlencode($r['registro_id']) ?>">🔎</a>
                <?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <?php if ($paginas > 1):
        $q = $_GET; unset($q['pag']); $base = '?' . http_build_query($q);
        $ini = max(1, $pag - 3); $fin = min($paginas, $pag + 3); ?>
        <div class="pager">
            <?php if ($pag > 1): ?><a href="<?= e($base . '&pag=' . ($pag - 1)) ?>">« Anterior</a><?php endif; ?>
            <?php for ($i = $ini; $i <= $fin; $i++): ?>
                <?= $i === $pag ? '<span class="act">' . $i . '</span>' : '<a href="' . e($base . '&pag=' . $i) . '">' . $i . '</a>' ?>
            <?php endfor; ?>
            <?php if ($pag < $paginas): ?><a href="<?= e($base . '&pag=' . ($pag + 1)) ?>">Siguiente »</a><?php endif; ?>
        </div>
    <?php endif; ?>

<?php elseif ($vista === 'caja'): ?>
    <?php
        $conDif = array_filter($cajas, fn($c) => $c['diferencia'] !== null && abs((float) $c['diferencia']) > 0.004);
        $sumDif = array_sum(array_map(fn($c) => (float) $c['diferencia'], $cajas));
    ?>
    <div class="kpis">
        <div class="kpi"><small>Cajas del período</small><strong><?= count($cajas) ?></strong></div>
        <div class="kpi rojo"><small>Cajas con diferencia</small><strong><?= count($conDif) ?></strong></div>
        <div class="kpi <?= abs($sumDif) > 0.004 ? 'rojo' : '' ?>"><small>Diferencia acumulada</small><strong><?= plata($sumDif) ?></strong></div>
    </div>
    <div class="tabla-scroll">
    <table>
        <thead><tr><th>Caja</th><th>Apertura</th><th>Cierre</th><th class="num">Inicial</th><th class="num">Ingresos</th><th class="num">Egresos</th><th class="num">Cobros</th>
            <th class="num">Esperado</th><th class="num">Contado</th><th class="num">Diferencia</th><th>Observaciones de control</th><th class="no-print"></th></tr></thead>
        <tbody>
        <?php if (!$cajas): ?><tr><td colspan="12" class="empty">No hay cajas en el período.</td></tr><?php endif; ?>
        <?php foreach ($cajas as $c):
            $obs = [];
            $dif = $c['diferencia'] !== null ? (float) $c['diferencia'] : null;
            if ($c['estado'] === 'Abierta' && strtotime($c['fecha_apertura']) < strtotime('-24 hours')) $obs[] = 'Abierta hace más de 24 h';
            if ($dif !== null && abs($dif) > 0.004) {
                $esp = abs((float) $c['efectivo_esperado']);
                $obs[] = ($dif > 0 ? 'Sobrante' : 'Faltante') . ' de ' . plata(abs($dif)) . (($esp > 0 && abs($dif) / $esp > 0.05) ? ' (más del 5% — revisar)' : '');
            }
            if ($c['usuario_cierre'] && $c['rela_id_usuario_cierre'] != $c['rela_id_usuario_apertura']) $obs[] = 'Cerró otro usuario distinto al que abrió';
            if ((float) $c['egresos'] > 0) $obs[] = 'Tiene egresos manuales (' . plata($c['egresos']) . ')';
        ?>
            <tr>
                <td>#<?= (int) $c['id_caja'] ?><br><?= $c['estado'] === 'Abierta' ? '<span class="badge" style="background:#3DDC97">Abierta</span>' : 'Cerrada' ?></td>
                <td><?= e(date('d/m/Y H:i', strtotime($c['fecha_apertura']))) ?><br><small><?= e($c['usuario_apertura']) ?></small></td>
                <td><?= $c['fecha_cierre'] ? e(date('d/m/Y H:i', strtotime($c['fecha_cierre']))) . '<br><small>' . e($c['usuario_cierre']) . '</small>' : '—' ?></td>
                <td class="num"><?= plata($c['monto_inicial']) ?></td>
                <td class="num"><?= plata($c['ingresos']) ?></td>
                <td class="num"><?= plata($c['egresos']) ?></td>
                <td class="num"><?= plata($c['cobros']) ?></td>
                <td class="num"><?= $c['efectivo_esperado'] !== null ? plata($c['efectivo_esperado']) : '—' ?></td>
                <td class="num"><?= $c['efectivo_contado'] !== null ? plata($c['efectivo_contado']) : '—' ?></td>
                <td class="num <?= ($dif !== null && abs($dif) > 0.004) ? 'alerta' : '' ?>"><?= $dif !== null ? plata($dif) : '—' ?></td>
                <td><?= $obs ? e(implode(' · ', $obs)) : 'Sin observaciones' ?></td>
                <td class="no-print"><a title="Historial de esta caja" href="?vista=registro&tabla=caja&registro=<?= (int) $c['id_caja'] ?>">🔎</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

<?php else: ?>
    <h2 class="sec">Acciones auditadas por usuario</h2>
    <div class="tabla-scroll">
    <table>
        <thead><tr><th>Usuario</th><th>Rol</th><th class="num">Eventos</th><th class="num">Cambios en datos</th><th class="num">Op. críticas</th><th class="num">Logins</th><th class="num">Fallidos</th><th>Última actividad</th></tr></thead>
        <tbody>
        <?php if (!$actividad): ?><tr><td colspan="8" class="empty">Sin actividad en el período.</td></tr><?php endif; ?>
        <?php foreach ($actividad as $a): ?>
            <tr>
                <td><strong><?= e($a['usuario_nombre']) ?></strong></td><td><?= e($a['rol']) ?></td>
                <td class="num"><?= (int) $a['total'] ?></td><td class="num"><?= (int) $a['cambios'] ?></td>
                <td class="num"><?= (int) $a['altas'] ?></td><td class="num"><?= (int) $a['logins'] ?></td>
                <td class="num <?= $a['fallidos'] > 0 ? 'alerta' : '' ?>"><?= (int) $a['fallidos'] ?></td>
                <td><?= e(date('d/m/Y H:i', strtotime($a['ultima']))) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <h2 class="sec">Ventas atendidas por usuario</h2>
    <div class="tabla-scroll">
    <table>
        <thead><tr><th>Usuario</th><th class="num">Pedidos</th><th class="num">Monto</th></tr></thead>
        <tbody>
        <?php if (!$ventas): ?><tr><td colspan="3" class="empty">Sin pedidos atendidos por personal en el período.</td></tr><?php endif; ?>
        <?php foreach ($ventas as $v): ?>
            <tr><td><?= e($v['nombre']) ?></td><td class="num"><?= (int) $v['pedidos'] ?></td><td class="num"><?= plata($v['monto']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>
</div>
</body>
</html>
