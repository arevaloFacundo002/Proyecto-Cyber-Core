<?php

require_once '../auth/auth.php';
require "../conexion.php";
require_once __DIR__ . "/ReportePDF.php";

function filas(mysqli $c, string $sql, string $tipos = '', array $params = []): array
{
    $st = $c->prepare($sql);
    if ($tipos !== '') {
        $st->bind_param($tipos, ...$params);
    }
    $st->execute();
    $res = $st->get_result();
    $out = [];
    while ($f = $res->fetch_assoc()) {
        $out[] = $f;
    }
    return $out;
}

function plata($v): string
{
    return '$' . number_format((float) $v, 2, ',', '.');
}

function fechaCorta($f): string
{
    return $f ? date('d/m/Y', strtotime($f)) : '-';
}

function fechaOk(string $f): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $f);
    return $d && $d->format('Y-m-d') === $f;
}

function pct($parte, $total): string
{
    return $total > 0 ? number_format($parte / $total * 100, 1, ',', '.') . '%' : '0%';
}

function principales(array $datos, int $max = 5): array
{
    arsort($datos);
    if (count($datos) <= $max) {
        return $datos;
    }
    $top = array_slice($datos, 0, $max, true);
    $top['Otras'] = array_sum(array_slice($datos, $max, null, true));
    return $top;
}

function periodoTexto(string $desde, string $hasta): string
{
    if ($desde !== '' && $hasta !== '') return 'Período: ' . fechaCorta($desde) . ' al ' . fechaCorta($hasta);
    if ($desde !== '') return 'Desde el ' . fechaCorta($desde);
    if ($hasta !== '') return 'Hasta el ' . fechaCorta($hasta);
    return 'Todo el historial';
}

$tipo = $_GET['tipo'] ?? '';

// ===== Filtros de fechas (los usan pedidos y transacciones) =====
$desde = trim($_GET['desde'] ?? '');
$hasta = trim($_GET['hasta'] ?? '');
if ($desde !== '' && !fechaOk($desde)) $desde = '';
if ($hasta !== '' && !fechaOk($hasta)) $hasta = '';
if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
    [$desde, $hasta] = [$hasta, $desde];
}

switch ($tipo) {

    // ------------------------------------------------------------
    case 'precios':
        $datos = filas($conexion, "
            SELECT p.nombre AS producto, m.nombre_marca AS marca, pr.nombre_apellido AS proveedor,
                   dc.precio AS precio, cp.fecha_compra
            FROM productos p
            INNER JOIN marcas m ON p.rela_id_marca = m.id_marca
            LEFT JOIN detalle_compras dc ON dc.rela_id_productos = p.id_producto
            LEFT JOIN compras cp ON cp.id_compras = dc.rela_id_compras
            LEFT JOIN proveedores pr ON cp.rela_id_proveedores = pr.id_proveedores
            WHERE cp.fecha_compra = (
                SELECT MAX(c2.fecha_compra)
                FROM detalle_compras dc2
                INNER JOIN compras c2 ON c2.id_compras = dc2.rela_id_compras
                WHERE dc2.rela_id_productos = p.id_producto)
            ORDER BY cp.fecha_compra DESC");

        $precios = array_map(fn($f) => (float) $f['precio'], $datos);
        $pdf = new ReportePDF("Últimos precios de compra");
        $pdf->portada("Últimos precios de compra", "Último precio al que se compró cada producto");

        $pdf->seccion("Resumen");
        $pdf->kpis([
            ["Productos con compra registrada", count($datos)],
            ["Precio promedio", $precios ? plata(array_sum($precios) / count($precios)) : plata(0)],
            ["Precio más alto", $precios ? plata(max($precios)) : plata(0)],
        ]);

        $top = [];
        $orden = $datos;
        usort($orden, fn($a, $b) => (float) $b['precio'] <=> (float) $a['precio']);
        foreach (array_slice($orden, 0, 10) as $f) {
            $top[$f['producto']] = (float) $f['precio'];
        }
        $pdf->seccion("Los 10 productos con el precio de compra más alto");
        $pdf->barras($top, '', true);

        $pdf->seccion("Detalle");
        $pdf->tabla(
            ["Producto", "Marca", "Proveedor", "Último precio", "Fecha"],
            array_map(fn($f) => [$f['producto'], $f['marca'], $f['proveedor'] ?: '-', plata($f['precio']), fechaCorta($f['fecha_compra'])], $datos),
            [52, 28, 42, 30, 28],
            ['L', 'L', 'L', 'R', 'R']
        );
        $archivo = 'ultimos_precios_de_compra';
        break;

    // ------------------------------------------------------------
    case 'pedidos':
        $estado = $_GET['estado'] ?? 'todos';
        $buscar = trim($_GET['buscar'] ?? '');
        require_once __DIR__ . '/../models/Pedido.php';
        if ($estado !== 'todos' && !array_key_exists($estado, Pedido::ESTADOS)) $estado = 'todos';

        $donde = []; $tipos = ''; $params = [];
        if ($desde !== '') { $donde[] = "DATE(pd.fecha_pedidos) >= ?"; $tipos .= 's'; $params[] = $desde; }
        if ($hasta !== '') { $donde[] = "DATE(pd.fecha_pedidos) <= ?"; $tipos .= 's'; $params[] = $hasta; }
        if ($estado !== 'todos') { $donde[] = "pd.estado = ?"; $tipos .= 's'; $params[] = $estado; }
        if ($buscar !== '') {
            $dig = preg_replace('/\D/', '', $buscar);
            $id  = ($dig !== '' && strlen($dig) < 10) ? (int) $dig : 0;
            $like = '%' . $buscar . '%';
            $donde[] = "(pd.id_pedidos = ? OR c.nombre LIKE ? OR c.apellido LIKE ? OR CONCAT(c.nombre,' ',c.apellido) LIKE ?)";
            $tipos .= 'isss';
            array_push($params, $id, $like, $like, $like);
        }

        $datos = filas($conexion, "
            SELECT pd.id_pedidos, pd.fecha_pedidos, pd.estado, pd.monto_total, c.nombre, c.apellido
            FROM pedidos pd
            INNER JOIN clientes c ON c.id_cliente = pd.rela_id_cliente"
            . ($donde ? " WHERE " . implode(' AND ', $donde) : "")
            . " ORDER BY pd.id_pedidos DESC", $tipos, $params);

        $porEstado = []; $porMes = []; $monto = 0.0; $validos = 0;
        foreach ($datos as $f) {
            $porEstado[$f['estado']] = ($porEstado[$f['estado']] ?? 0) + 1;
            if ($f['estado'] !== 'Cancelado') {
                $monto += (float) $f['monto_total'];
                $validos++;
                $mes = date('Y-m', strtotime($f['fecha_pedidos']));
                $porMes[$mes] = ($porMes[$mes] ?? 0) + (float) $f['monto_total'];
            }
        }
        ksort($porMes);
        $porMes = array_slice($porMes, -12, null, true);
        $mesesTxt = [];
        foreach ($porMes as $m => $v) {
            $mesesTxt[substr($m, 5, 2) . '/' . substr($m, 2, 2)] = $v;
        }

        $sub = periodoTexto($desde, $hasta) . ($estado !== 'todos' ? ' · Estado: ' . $estado : '') . ($buscar !== '' ? ' · Búsqueda: ' . $buscar : '');
        $pdf = new ReportePDF("Pedidos detallados");
        $pdf->portada("Pedidos detallados", $sub);

        $pdf->seccion("Resumen");
        $pdf->kpis([
            ["Pedidos", count($datos)],
            ["Monto vendido (sin cancelados)", plata($monto)],
            ["Valor promedio por pedido", plata($validos ? $monto / $validos : 0)],
        ]);

        $pdf->seccion("Pedidos por estado");
        $pdf->torta($porEstado);

        $pdf->seccion("Monto vendido por mes");
        $pdf->nota("No incluye los pedidos cancelados.");
        $pdf->barras($mesesTxt, '', true);

        $pdf->seccion("Detalle de pedidos");
        $pdf->tabla(
            ["Pedido", "Fecha", "Cliente", "Estado", "Total"],
            array_map(fn($f) => ['#' . $f['id_pedidos'], fechaCorta($f['fecha_pedidos']), $f['nombre'] . ' ' . $f['apellido'], $f['estado'], plata($f['monto_total'])], $datos),
            [22, 28, 62, 34, 34],
            ['L', 'L', 'L', 'L', 'R']
        );
        $archivo = 'pedidos_detallados';
        break;

    // ------------------------------------------------------------
    case 'vendidos':
        $datos = filas($conexion, "
            SELECT p.nombre AS producto, m.nombre_marca AS marca, SUM(dp.cantidad) AS total
            FROM detalle_pedidos dp
            INNER JOIN productos p ON p.id_producto = dp.rela_id_productos
            INNER JOIN marcas m ON m.id_marca = p.rela_id_marca
            GROUP BY p.id_producto, p.nombre, m.nombre_marca
            ORDER BY total DESC, p.nombre ASC
            LIMIT 5");
        $tot = filas($conexion, "SELECT COALESCE(SUM(cantidad),0) AS t FROM detalle_pedidos");
        $unidadesTotales = (int) ($tot[0]['t'] ?? 0);
        $unidadesTop = array_sum(array_map(fn($f) => (int) $f['total'], $datos));

        $pdf = new ReportePDF("Productos más vendidos");
        $pdf->portada("Productos más vendidos", "Los 5 productos con más unidades vendidas");

        $pdf->seccion("Resumen");
        $pdf->kpis([
            ["Unidades vendidas (top 5)", $unidadesTop],
            ["Unidades vendidas en total", $unidadesTotales],
            ["Peso del top 5 sobre el total", pct($unidadesTop, $unidadesTotales)],
        ]);

        $grafico = [];
        foreach ($datos as $f) $grafico[$f['producto']] = (int) $f['total'];
        $pdf->seccion("Unidades vendidas por producto");
        $pdf->barras($grafico);

        $pdf->seccion("Detalle");
        $n = 0;
        $pdf->tabla(
            ["Puesto", "Producto", "Marca", "Unidades", "% del total"],
            array_map(function ($f) use (&$n, $unidadesTotales) {
                $n++;
                return [$n . '°', $f['producto'], $f['marca'], $f['total'], pct($f['total'], $unidadesTotales)];
            }, $datos),
            [20, 75, 35, 25, 25],
            ['L', 'L', 'L', 'R', 'R']
        );
        $archivo = 'productos_mas_vendidos';
        break;

    // ------------------------------------------------------------
    case 'clientes':
        $datos = filas($conexion, "
            SELECT c.nombre, c.apellido, u.correo, COUNT(p.id_pedidos) AS cantidad
            FROM pedidos p
            INNER JOIN clientes c ON p.rela_id_cliente = c.id_cliente
            LEFT JOIN usuarios u ON c.rela_id_usuario = u.id_usuario
            GROUP BY c.id_cliente, c.nombre, c.apellido, u.correo
            ORDER BY cantidad DESC, c.apellido ASC");
        $pedidosTotal = array_sum(array_map(fn($f) => (int) $f['cantidad'], $datos));

        $pdf = new ReportePDF("Mejores clientes");
        $pdf->portada("Mejores clientes", "Clientes ordenados por cantidad de pedidos realizados");

        $pdf->seccion("Resumen");
        $pdf->kpis([
            ["Clientes con pedidos", count($datos)],
            ["Pedidos realizados", $pedidosTotal],
            ["Promedio de pedidos por cliente", $datos ? number_format($pedidosTotal / count($datos), 1, ',', '.') : '0'],
        ]);

        $grafico = [];
        foreach (array_slice($datos, 0, 10) as $f) $grafico[trim($f['nombre'] . ' ' . $f['apellido'])] = (int) $f['cantidad'];
        $pdf->seccion("Los 10 clientes con más pedidos");
        $pdf->barras($grafico);

        $pdf->seccion("Detalle");
        $pdf->tabla(
            ["Cliente", "Email", "Pedidos", "% del total"],
            array_map(fn($f) => [$f['nombre'] . ' ' . $f['apellido'], $f['correo'] ?: 'N/A', $f['cantidad'], pct($f['cantidad'], $pedidosTotal)], $datos),
            [55, 72, 26, 27],
            ['L', 'L', 'R', 'R']
        );
        $archivo = 'mejores_clientes';
        break;

    // ------------------------------------------------------------
    case 'marcas':
        $datos = filas($conexion, "
            SELECT m.nombre_marca AS marca, COUNT(p.id_producto) AS cantidad
            FROM marcas m
            INNER JOIN productos p ON p.rela_id_marca = m.id_marca
            GROUP BY m.id_marca, m.nombre_marca
            ORDER BY cantidad DESC, m.nombre_marca ASC");
        $productosTotal = array_sum(array_map(fn($f) => (int) $f['cantidad'], $datos));

        $pdf = new ReportePDF("Marcas con más productos");
        $pdf->portada("Marcas con más productos", "Cantidad de productos del catálogo por marca");

        $pdf->seccion("Resumen");
        $pdf->kpis([
            ["Marcas con productos", count($datos)],
            ["Productos en total", $productosTotal],
            ["Marca con más productos", $datos ? $datos[0]['marca'] : '-'],
        ]);

        $grafico = [];
        foreach ($datos as $f) $grafico[$f['marca']] = (int) $f['cantidad'];
        $pdf->seccion("Participación de cada marca");
        $pdf->torta(principales($grafico, 5));

        $pdf->seccion("Productos por marca");
        $pdf->barras(array_slice($grafico, 0, 10, true));

        $pdf->seccion("Detalle");
        $pdf->tabla(
            ["Marca", "Productos", "% del total"],
            array_map(fn($f) => [$f['marca'], $f['cantidad'], pct($f['cantidad'], $productosTotal)], $datos),
            [100, 40, 40],
            ['L', 'R', 'R']
        );
        $archivo = 'marcas_con_mas_productos';
        break;

    // ------------------------------------------------------------
    case 'transacciones':
        $metodo = (int) ($_GET['metodo'] ?? 0);
        $estado = $_GET['estado'] ?? '';
        $estadosOk = ['Aprobado', 'Pendiente', 'A reembolsar', 'Cancelado'];
        if ($estado !== '' && !in_array($estado, $estadosOk, true)) $estado = '';

        $donde = []; $tipos = ''; $params = [];
        if ($desde !== '')  { $donde[] = "DATE(pg.fechas_pagos) >= ?"; $tipos .= 's'; $params[] = $desde; }
        if ($hasta !== '')  { $donde[] = "DATE(pg.fechas_pagos) <= ?"; $tipos .= 's'; $params[] = $hasta; }
        if ($metodo > 0)    { $donde[] = "pg.rela_id_metodo_pago = ?"; $tipos .= 'i'; $params[] = $metodo; }
        if ($estado !== '') { $donde[] = "pg.estado_pagos = ?";        $tipos .= 's'; $params[] = $estado; }

        $datos = filas($conexion, "
            SELECT pg.fechas_pagos, pg.monto, pg.estado_pagos, pg.rela_id_pedidos, mp.nombre AS metodo, c.nombre, c.apellido
            FROM pagos pg
            INNER JOIN pedidos p ON p.id_pedidos = pg.rela_id_pedidos
            INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
            LEFT JOIN metodos_pago mp ON mp.id_metodo_pago = pg.rela_id_metodo_pago"
            . ($donde ? " WHERE " . implode(' AND ', $donde) : "")
            . " ORDER BY pg.fechas_pagos DESC, pg.id_pagos DESC", $tipos, $params);

        $monto = array_fill_keys($estadosOk, 0.0);
        $cant  = array_fill_keys($estadosOk, 0);
        $porMetodo = [];
        foreach ($datos as $f) {
            $s = $f['estado_pagos'];
            if (isset($monto[$s])) {
                $monto[$s] += (float) $f['monto'];
                $cant[$s]++;
            }
            if ($s === 'Aprobado') {
                $m = $f['metodo'] ?: 'Sin método';
                $porMetodo[$m] = ($porMetodo[$m] ?? 0) + (float) $f['monto'];
            }
        }
        arsort($porMetodo);
        $totalPagos = array_sum($monto);

        $sub = periodoTexto($desde, $hasta) . ($estado !== '' ? ' · Estado: ' . $estado : '');
        $pdf = new ReportePDF("Transacciones");
        $pdf->portada("Transacciones", $sub);

        $pdf->seccion("Resumen");
        $pdf->kpis([
            ["Cobrado (aprobado) · " . $cant['Aprobado'] . " pago(s)", plata($monto['Aprobado'])],
            ["Pendiente de cobro · " . $cant['Pendiente'] . " pago(s)", plata($monto['Pendiente'])],
            ["A reembolsar · " . $cant['A reembolsar'] . " pago(s)", plata($monto['A reembolsar'])],
            ["Cancelado · " . $cant['Cancelado'] . " pago(s)", plata($monto['Cancelado'])],
            ["Pagos en total", count($datos)],
            ["Monto total de pagos", plata($totalPagos)],
        ]);

        $pdf->seccion("Monto por estado del pago");
        $pdf->torta(array_filter($monto, fn($v) => $v > 0), '', true);

        $pdf->seccion("Cobrado por método de pago");
        $pdf->nota("Solo cuenta los pagos aprobados.");
        $pdf->barras($porMetodo, '', true);

        $pdf->seccion("Detalle de pagos");
        $pdf->tabla(
            ["Fecha", "Pedido", "Cliente", "Método", "Estado", "Monto"],
            array_map(fn($f) => [fechaCorta($f['fechas_pagos']), '#' . $f['rela_id_pedidos'], $f['nombre'] . ' ' . $f['apellido'], $f['metodo'] ?: '-', $f['estado_pagos'], plata($f['monto'])], $datos),
            [24, 20, 44, 32, 28, 32],
            ['L', 'L', 'L', 'L', 'L', 'R']
        );
        $archivo = 'transacciones';
        break;

    default:
        http_response_code(400);
        die("Reporte no válido. Volvé a Consultas y Reportes y elegí uno.");
}

$pdf->Output('I', $archivo . '_' . date('Y-m-d') . '.pdf');