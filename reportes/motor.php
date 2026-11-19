<?php

if (!defined('REPORTES_OK')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/ReportePDF.php';

const REP_SECCIONES = [
    'ventas'       => ['Ventas', '💰', 'Qué se vendió, cuánto y a quién'],
    'inventario'   => ['Inventario', '📦', 'Stock, valor del inventario y movimientos'],
    'financieros'  => ['Financieros', '📈', 'Ingresos, costos y ganancias'],
    'compras'      => ['Compras y proveedores', '🚚', 'Qué se compra y a quién'],
    'clientes'     => ['Clientes', '👥', 'Quiénes compran y cuánto'],
    'pedidos'      => ['Pedidos y entregas', '🧾', 'Estado de pedidos y tiempos de entrega'],
    'personal'     => ['Usuarios y personal', '🛡️', 'Actividad dentro del sistema'],
    'devoluciones' => ['Devoluciones', '↩️', 'Qué se devuelve y por qué'],
];

function rq(mysqli $c, string $sql, string $tipos = '', array $p = []): array
{
    $st = $c->prepare($sql);
    if ($tipos !== '') {
        $st->bind_param($tipos, ...$p);
    }
    $st->execute();
    $res = $st->get_result();
    $out = [];
    while ($f = $res->fetch_assoc()) {
        $out[] = $f;
    }
    return $out;
}

function rq1(mysqli $c, string $sql, string $tipos = '', array $p = []): array
{
    return rq($c, $sql, $tipos, $p)[0] ?? [];
}

function rplata($v): string
{
    return '$' . number_format((float) $v, 2, ',', '.');
}

function rnum($v): string
{
    return number_format((float) $v, 0, ',', '.');
}

function rpct($parte, $total): string
{
    return $total > 0 ? number_format($parte / $total * 100, 1, ',', '.') . '%' : '0%';
}

function rfecha($f): string
{
    return $f ? date('d/m/Y', strtotime((string) $f)) : '-';
}

function rmes(string $ym): string
{
    return substr($ym, 5, 2) . '/' . substr($ym, 2, 2);
}

function rtop(array $datos, int $max = 5): array
{
    arsort($datos);
    if (count($datos) <= $max) {
        return $datos;
    }
    $top = array_slice($datos, 0, $max, true);
    $top['Otras'] = array_sum(array_slice($datos, $max, null, true));
    return $top;
}

function rrango(string $col, array $f, string &$tipos, array &$params): string
{
    $sql = '';
    if (($f['desde'] ?? '') !== '') {
        $sql .= " AND $col >= ?";
        $tipos .= 's';
        $params[] = $f['desde'];
    }
    if (($f['hasta'] ?? '') !== '') {
        $sql .= " AND $col <= ?";
        $tipos .= 's';
        $params[] = $f['hasta'];
    }
    return $sql;
}

function reportes_catalogo(): array
{
    static $cat = null;
    if ($cat !== null) {
        return $cat;
    }
    $cat = [];
    foreach (['ventas', 'inventario', 'financieros', 'compras', 'clientes', 'pedidos', 'personal', 'devoluciones'] as $archivo) {
        $ruta = __DIR__ . '/definiciones/' . $archivo . '.php';
        if (is_file($ruta)) {
            $cat += require $ruta;
        }
    }
    return $cat;
}

function reporte_filtros(array $def, array $g): array
{
    $fl = $def['filtros'] ?? [];
    $f = ['desde' => '', 'hasta' => '', 'categoria' => 0, 'marca' => 0, 'proveedor' => 0, 'cliente' => 0,
          'concepto' => 0, 'umbral' => 5, 'top' => 10, 'estado' => '', 'agrupar' => ''];

    $fechaOk = function ($x) {
        $d = DateTime::createFromFormat('Y-m-d', $x);
        return $d && $d->format('Y-m-d') === $x;
    };

    if (!empty($fl['fechas'])) {
        foreach (['desde', 'hasta'] as $k) {
            $v = trim((string) ($g[$k] ?? ''));
            $f[$k] = $fechaOk($v) ? $v : '';
        }
        if ($f['desde'] !== '' && $f['hasta'] !== '' && $f['desde'] > $f['hasta']) {
            [$f['desde'], $f['hasta']] = [$f['hasta'], $f['desde']];
        }
    }
    foreach (['categoria', 'marca', 'proveedor', 'cliente', 'concepto'] as $k) {
        if (!empty($fl[$k])) {
            $f[$k] = max(0, (int) ($g[$k] ?? 0));
        }
    }
    if (isset($fl['umbral'])) {
        $def_u = is_array($fl['umbral']) ? (int) ($fl['umbral']['defecto'] ?? 5) : 5;
        $f['umbral'] = min(1000, max(0, (int) ($g['umbral'] ?? $def_u)));
    }
    if (isset($fl['top'])) {
        $f['top'] = min(50, max(1, (int) ($g['top'] ?? 10)));
    }
    foreach (['estado', 'agrupar'] as $k) {
        if (isset($fl[$k])) {
            $op = $fl[$k]['opciones'];
            $v = (string) ($g[$k] ?? '');
            $f[$k] = array_key_exists($v, $op) ? $v : ($fl[$k]['defecto'] ?? (string) array_key_first($op));
        }
    }
    return $f;
}

function reporte_resumen_filtros(array $def, array $f, mysqli $c): string
{
    $fl = $def['filtros'] ?? [];
    $p = [];
    if (!empty($fl['fechas'])) {
        if ($f['desde'] !== '' && $f['hasta'] !== '') $p[] = 'Período: ' . rfecha($f['desde']) . ' al ' . rfecha($f['hasta']);
        elseif ($f['desde'] !== '') $p[] = 'Desde el ' . rfecha($f['desde']);
        elseif ($f['hasta'] !== '') $p[] = 'Hasta el ' . rfecha($f['hasta']);
        else $p[] = 'Todo el historial';
    }
    $nombres = [
        'categoria' => ['Categoría', "SELECT nombre FROM categorias WHERE id_categoria = ?"],
        'marca'     => ['Marca', "SELECT nombre_marca FROM marcas WHERE id_marca = ?"],
        'proveedor' => ['Proveedor', "SELECT nombre_apellido FROM proveedores WHERE id_proveedores = ?"],
        'cliente'   => ['Cliente', "SELECT CONCAT(nombre, ' ', apellido) FROM clientes WHERE id_cliente = ?"],
        'concepto'  => ['Concepto', "SELECT descripcion FROM conceptos_movimiento WHERE id_concepto = ?"],
    ];
    foreach ($nombres as $k => [$etq, $sql]) {
        if (!empty($f[$k])) {
            $st = $c->prepare($sql);
            $st->bind_param('i', $f[$k]);
            $st->execute();
            $r = $st->get_result()->fetch_row();
            if ($r) $p[] = $etq . ': ' . $r[0];
        }
    }
    foreach (['estado', 'agrupar'] as $k) {
        if (isset($fl[$k]) && $f[$k] !== '' && $f[$k] !== ($fl[$k]['defecto'] ?? null)) {
            $p[] = ($fl[$k]['etiqueta'] ?? ucfirst($k)) . ': ' . $fl[$k]['opciones'][$f[$k]];
        } elseif (isset($fl[$k]) && $k === 'agrupar') {
            $p[] = ($fl[$k]['etiqueta'] ?? 'Agrupado') . ': ' . $fl[$k]['opciones'][$f[$k]];
        }
    }
    if (isset($fl['umbral'])) $p[] = 'Stock considerado bajo: hasta ' . $f['umbral'] . ' unidades';
    if (isset($fl['top'])) $p[] = 'Mostrando los primeros ' . $f['top'];
    return implode(' · ', $p);
}

function reporte_celda($v, string $tipo): string
{
    if ($v === null || $v === '') {
        return '-';
    }
    switch ($tipo) {
        case 'int':   return rnum($v);
        case 'plata': return rplata($v);
        case 'pct':   return number_format((float) $v, 1, ',', '.') . '%';
        case 'dec':   return number_format((float) $v, 1, ',', '.');
        case 'fecha': return rfecha($v);
        default:      return (string) $v;
    }
}

function reporte_csv_celda($v, string $tipo): string
{
    if ($v === null) {
        return '';
    }
    switch ($tipo) {
        case 'int':   return (string) (int) $v;
        case 'plata':
        case 'pct':
        case 'dec':   return number_format((float) $v, 2, ',', '');
        case 'fecha': return $v ? date('d/m/Y', strtotime((string) $v)) : '';
        default:
            $t = (string) $v;
            return ($t !== '' && strpbrk($t[0], "=+-@") !== false) ? "'" . $t : $t;
    }
}

function reporte_codigo(string $id): string
{
    return 'REP-' . strtoupper(substr(preg_replace('/[^a-z]/i', '', $id), 0, 4)) . '-' . date('Ymd-His');
}

function reporte_archivo(string $titulo): string
{
    $t = iconv('UTF-8', 'ASCII//TRANSLIT', $titulo);
    return trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($t)), '_') . '_' . date('Y-m-d');
}

function reporte_pdf(string $id, array $def, array $res, array $f, mysqli $c): void
{
    $pdf = new ReportePDF($def['titulo']);
    $sub = reporte_resumen_filtros($def, $f, $c);
    if (!empty($res['subtitulo'])) {
        $sub = trim($sub . ' · ' . $res['subtitulo'], ' ·');
    }
    $pdf->portada($def['titulo'], $sub, reporte_codigo($id));

    if (!empty($res['kpis'])) {
        $pdf->seccion('Resumen');
        $pdf->kpis($res['kpis'], 3);
    }

    foreach (($res['graficos'] ?? []) as $g) {
        $pdf->seccion($g['titulo']);
        if (!empty($g['nota'])) {
            $pdf->nota($g['nota']);
        }
        $dinero = !empty($g['dinero']);
        $datos = $g['datos'];
        switch ($g['tipo']) {
            case 'torta':
                $pdf->torta(rtop($datos, 5), '', $dinero);
                break;
            case 'lineas':
                $pdf->lineas($datos, '', $dinero);
                break;
            default:
                $pdf->barras(array_slice($datos, 0, $g['max'] ?? 10, true), '', $dinero);
        }
    }

    if (!empty($res['tabla'])) {
        $t = $res['tabla'];
        $suma = array_sum(array_map(fn($c2) => $c2[1], $t['cols']));
        $anchos = $cab = $alin = $tipos = [];
        foreach ($t['cols'] as $col) {
            $cab[] = $col[0];
            $anchos[] = $col[1] / $suma * 180;
            $tipos[] = $col[2];
            $alin[] = in_array($col[2], ['txt', 'fecha'], true) ? 'L' : 'R';
        }
        $filas = [];
        foreach ($t['filas'] as $fila) {
            $out = [];
            foreach (array_values($fila) as $i => $v) {
                $out[] = reporte_celda($v, $tipos[$i] ?? 'txt');
            }
            $filas[] = $out;
        }
        $pdf->seccion($t['titulo'] ?? 'Detalle');
        $pdf->tabla($cab, $filas, $anchos, $alin);
    }

    if (!empty($res['analisis'])) {
        $pdf->seccion('Análisis');
        $pdf->analisis($res['analisis']);
    }

    $pdf->Output('I', reporte_archivo($def['titulo']) . '.pdf');
}

function reporte_csv(string $id, array $def, array $res, array $f, mysqli $c): void
{
    $quien = trim((string) ($_SESSION['usuario'] ?? ''));
    $archivo = reporte_archivo($def['titulo']) . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $archivo . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['CyberCore - ' . $def['titulo']], ';');
    fputcsv($out, ['Código', reporte_codigo($id)], ';');
    fputcsv($out, ['Generado por', $quien], ';');
    fputcsv($out, ['Fecha', date('d/m/Y H:i')], ';');
    $filtros = reporte_resumen_filtros($def, $f, $c);
    if ($filtros !== '') {
        fputcsv($out, ['Filtros', $filtros], ';');
    }
    fputcsv($out, [], ';');
    if (!empty($res['tabla'])) {
        $t = $res['tabla'];
        fputcsv($out, array_map(fn($col) => $col[0], $t['cols']), ';');
        foreach ($t['filas'] as $fila) {
            $linea = [];
            foreach (array_values($fila) as $i => $v) {
                $linea[] = reporte_csv_celda($v, $t['cols'][$i][2] ?? 'txt');
            }
            fputcsv($out, $linea, ';');
        }
    }
    fclose($out);
}

function rcategoria(array $f, string &$tipos, array &$params): string
{
    if (empty($f['categoria'])) {
        return '';
    }
    $tipos .= 'ii';
    $params[] = $f['categoria'];
    $params[] = $f['categoria'];
    return " AND (p.rela_id_categoria = ? OR c.rela_id_categoria_padre = ?)";
}

function rmarca(array $f, string &$tipos, array &$params): string
{
    if (empty($f['marca'])) {
        return '';
    }
    $tipos .= 'i';
    $params[] = $f['marca'];
    return " AND p.rela_id_marca = ?";
}

function rcompras_lineas(): string
{
    return "(SELECT cp.fecha_compra AS fecha, cp.rela_id_proveedores AS prov, CONVERT(cp.comprobante_compra USING utf8mb4) COLLATE utf8mb4_general_ci AS comprobante,
                    dc.rela_id_productos AS prod, dc.cantidad AS cantidad, dc.precio AS precio, dc.subtotal_linea AS subtotal
             FROM compras cp INNER JOIN detalle_compras dc ON dc.rela_id_compras = cp.id_compras
             UNION ALL
             SELECT mf.fecha, mf.rela_id_proveedores, CONVERT(mf.numero_comprobante USING utf8mb4) COLLATE utf8mb4_general_ci,
                    i.rela_id_productos, i.cantidad, i.precio_unitario, i.subtotal
             FROM movimiento_factura mf INNER JOIN movimiento_factura_items i ON i.rela_id_movimiento_factura = mf.id_movimiento_factura
             WHERE mf.tipo = 'Compra' AND mf.rela_id_proveedores IS NOT NULL) cl";
}

function rgraf(array $filas, string $k, string $v): array
{
    $o = [];
    foreach ($filas as $r) {
        $o[$r[$k]] = (float) $r[$v];
    }
    return $o;
}

function rtotal(array $filas, string $col): float
{
    return (float) array_sum(array_column($filas, $col));
}