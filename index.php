<?php

require_once '../auth/auth.php';
define('REPORTES_OK', 1);
require '../conexion.php';
require_once __DIR__ . '/motor.php';

$esAdmin = ($_SESSION['rol'] ?? '') === 'administrador';
$catalogo = reportes_catalogo();

function h($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function opciones(mysqli $c, string $sql): array
{
    $out = [];
    $r = $c->query($sql);
    while ($r && ($f = $r->fetch_row())) {
        $out[$f[0]] = $f[1];
    }
    return $out;
}

$listas = [
    'categoria' => ['Categoría', opciones($conexion, "SELECT id_categoria, nombre FROM categorias WHERE es_activo = 1 ORDER BY nombre")],
    'marca'     => ['Marca', opciones($conexion, "SELECT id_marca, nombre_marca FROM marcas WHERE es_activo = 1 ORDER BY nombre_marca")],
    'proveedor' => ['Proveedor', opciones($conexion, "SELECT id_proveedores, nombre_apellido FROM proveedores ORDER BY nombre_apellido")],
    'cliente'   => ['Cliente', opciones($conexion, "SELECT id_cliente, CONCAT(nombre, ' ', apellido) FROM clientes ORDER BY nombre, apellido")],
    'concepto'  => ['Motivo del movimiento', opciones($conexion, "SELECT id_concepto, descripcion FROM conceptos_movimiento ORDER BY descripcion")],
];

$porSeccion = [];
foreach ($catalogo as $id => $def) {
    if (!empty($def['solo_admin']) && !$esAdmin) {
        continue;
    }
    $porSeccion[$def['seccion']][$id] = $def;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reportes - CyberCore</title>
<link href="../css/theme.css" rel="stylesheet">
<style>
body { margin: 0; font-family: 'Segoe UI', sans-serif; }
.subheader { background: var(--cc-superficie-2); padding: 16px 50px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--cc-borde); }
.subheader .titulo { font-size: 22px; font-weight: bold; color: var(--cc-primario); }
.back { background: var(--cc-primario); padding: 10px 18px; border-radius: 20px; color: var(--cc-texto-sobre-primario); font-weight: bold; text-decoration: none; }
.back:hover { background: var(--cc-primario-hover); }
.container { max-width: 1100px; margin: 0 auto; padding: 30px 24px 60px; }
.intro { color: var(--cc-texto-secundario); margin-bottom: 20px; }
.atajos { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 26px; }
.atajos a { background: var(--cc-superficie); border: 1px solid var(--cc-borde); border-radius: 18px; padding: 7px 14px; color: var(--cc-texto); text-decoration: none; font-size: 14px; }
.atajos a:hover { border-color: var(--cc-primario); }
.seccion { margin-bottom: 34px; }
.seccion h2 { color: var(--cc-primario); margin: 0 0 4px; }
.seccion .desc { color: var(--cc-texto-secundario); margin: 0 0 14px; }
.reporte { background: var(--cc-superficie); border: 1px solid var(--cc-borde); border-radius: 10px; margin-bottom: 10px; }
.reporte summary { cursor: pointer; padding: 14px 18px; list-style: none; }
.reporte summary::-webkit-details-marker { display: none; }
.reporte summary b { color: var(--cc-texto); display: block; }
.reporte summary span { color: var(--cc-texto-secundario); font-size: 14px; }
.reporte[open] { border-color: var(--cc-primario); }
.reporte form { padding: 4px 18px 18px; border-top: 1px solid var(--cc-borde); }
.campos { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px; margin: 14px 0; }
.campos label { display: block; font-size: 13px; color: var(--cc-texto-secundario); margin-bottom: 4px; }
.campos input, .campos select { width: 100%; box-sizing: border-box; padding: 8px 10px; border-radius: 8px; border: 1px solid var(--cc-borde); background: var(--cc-superficie-2); color: var(--cc-texto); }
.botones { display: flex; gap: 10px; flex-wrap: wrap; }
.botones button { border: 0; border-radius: 20px; padding: 10px 20px; font-weight: bold; cursor: pointer; }
.btn-pdf { background: var(--cc-primario); color: var(--cc-texto-sobre-primario); }
.btn-pdf:hover { background: var(--cc-primario-hover); }
.btn-xls { background: transparent; color: var(--cc-primario); border: 1px solid var(--cc-primario) !important; }
.buscador { width: 100%; box-sizing: border-box; padding: 12px 16px; border-radius: 24px; border: 1px solid var(--cc-borde); background: var(--cc-superficie); color: var(--cc-texto); font-size: 15px; margin-bottom: 16px; }
.buscador:focus { outline: none; border-color: var(--cc-primario); }
.sin-filtros { color: var(--cc-texto-secundario); font-size: 14px; margin: 14px 0; }
</style>
</head>
<body>
<?php
$ruta_raiz = '../';
require __DIR__ . '/../includes/header_admin.php';
?>
<div class="subheader">
    <div class="titulo">Reportes</div>
    <a href="../consultas/menu_consultas.php" class="back">⬅ Volver a Consultas y Reportes</a>
</div>

<div class="container">
    <p class="intro">Elegí un reporte, ajustá los filtros si querés y descargalo en PDF (con gráficos) o en Excel. Cada reporte indica quién lo generó y cuándo.</p>

    <input type="search" id="buscador" class="buscador" placeholder="🔎 Buscar un reporte (por ejemplo: proveedor, stock, devoluciones)" autocomplete="off">
    <p id="sinResultados" class="intro" style="display:none;">No se encontró ningún reporte con ese texto.</p>

    <div class="atajos">
        <?php foreach (REP_SECCIONES as $k => $s) { if (isset($porSeccion[$k])) { ?>
            <a href="#sec-<?= h($k) ?>"><?= h($s[1]) ?> <?= h($s[0]) ?></a>
        <?php } } ?>
        <a href="../consultas/menu_consultas.php">🔎 Consultas</a>
        <?php if ($esAdmin) { ?><a href="reporte_usuarios.php">👥 Usuarios y clientes</a><?php } ?>
    </div>

    <?php foreach (REP_SECCIONES as $k => $s) { if (!isset($porSeccion[$k])) continue; ?>
    <div class="seccion" id="sec-<?= h($k) ?>">
        <h2><?= h($s[1]) ?> <?= h($s[0]) ?></h2>
        <p class="desc"><?= h($s[2]) ?></p>

        <?php foreach ($porSeccion[$k] as $id => $def) { $fl = $def['filtros'] ?? []; ?>
        <details class="reporte">
            <summary><b><?= h($def['titulo']) ?></b><span><?= h($def['desc']) ?></span></summary>
            <form method="get" action="ver_reporte.php" target="_blank">
                <input type="hidden" name="r" value="<?= h($id) ?>">
                <?php if ($fl) { ?>
                <div class="campos">
                    <?php if (!empty($fl['fechas'])) { ?>
                        <div><label>Desde</label><input type="date" name="desde"></div>
                        <div><label>Hasta</label><input type="date" name="hasta"></div>
                    <?php } ?>
                    <?php foreach ($listas as $campo => [$etq, $ops]) { if (empty($fl[$campo])) continue; ?>
                        <div><label><?= h($etq) ?></label>
                            <select name="<?= h($campo) ?>">
                                <option value="0"><?= $campo === 'cliente' && $id === 'historial_cliente' ? 'Elegí un cliente' : 'Todos' ?></option>
                                <?php foreach ($ops as $v => $n) { ?><option value="<?= h($v) ?>"><?= h($n) ?></option><?php } ?>
                            </select></div>
                    <?php } ?>
                    <?php if (isset($fl['umbral'])) { $u = $fl['umbral']; ?>
                        <div><label><?= h($u['etiqueta'] ?? 'Límite') ?></label><input type="number" name="umbral" min="0" max="1000" value="<?= (int) ($u['defecto'] ?? 5) ?>"></div>
                    <?php } ?>
                    <?php if (isset($fl['top'])) { ?>
                        <div><label>Cantidad en el ranking</label><input type="number" name="top" min="1" max="50" value="10"></div>
                    <?php } ?>
                    <?php foreach (['estado', 'agrupar'] as $campo) { if (!isset($fl[$campo])) continue; $o = $fl[$campo]; ?>
                        <div><label><?= h($o['etiqueta'] ?? ucfirst($campo)) ?></label>
                            <select name="<?= $campo ?>">
                                <?php foreach ($o['opciones'] as $v => $n) { ?><option value="<?= h($v) ?>" <?= ($o['defecto'] ?? '') === (string) $v ? 'selected' : '' ?>><?= h($n) ?></option><?php } ?>
                            </select></div>
                    <?php } ?>
                </div>
                <?php } else { ?>
                <p class="sin-filtros">Este reporte no tiene filtros: muestra la situación de hoy.</p>
                <?php } ?>
                <div class="botones">
                    <button type="submit" name="formato" value="pdf" class="btn-pdf">📄 Descargar PDF</button>
                    <button type="submit" name="formato" value="xlsx" class="btn-xls">📊 Descargar Excel</button>
                </div>
            </form>
        </details>
        <?php } ?>
    </div>
    <?php } ?>
</div>

<script>
(function () {
    var q = document.getElementById('buscador');
    var secciones = document.querySelectorAll('.seccion');
    var vacio = document.getElementById('sinResultados');
    function limpiar(t) { return t.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ''); }
    q.addEventListener('input', function () {
        var txt = limpiar(q.value.trim());
        var total = 0;
        secciones.forEach(function (sec) {
            var visibles = 0;
            sec.querySelectorAll('.reporte').forEach(function (r) {
                var ok = txt === '' || limpiar(r.querySelector('summary').textContent + ' ' + sec.querySelector('h2').textContent).indexOf(txt) !== -1;
                r.style.display = ok ? '' : 'none';
                if (ok) visibles++;
            });
            sec.style.display = visibles ? '' : 'none';
            total += visibles;
        });
        vacio.style.display = total ? 'none' : '';
    });
})();
</script>
<script src="../js/theme-toggle.js"></script>
</body>
</html>