<?php

if (!defined('REPORTES_OK')) {
    http_response_code(403);
    exit;
}

return [

    'clientes_registrados' => [
        'seccion' => 'clientes',
        'titulo'  => 'Clientes registrados',
        'desc'    => 'Listado de clientes con su correo, fecha de alta y estado de la cuenta.',
        'filtros' => ['fechas' => true],
        'generar' => function (mysqli $c, array $f): array {
            $t = ''; $p = [];
            $w = rrango('DATE(cl.fecha_registro)', $f, $t, $p);
            $filas = rq($c, "SELECT CONCAT(cl.nombre, ' ', cl.apellido) AS cliente, u.correo, cl.fecha_registro AS alta, u.estado,
                                    (SELECT COUNT(*) FROM pedidos pd WHERE pd.rela_id_cliente = cl.id_cliente AND pd.estado <> 'Cancelado') AS pedidos
                             FROM clientes cl LEFT JOIN usuarios u ON u.id_usuario = cl.rela_id_usuario
                             WHERE 1=1 $w ORDER BY cl.fecha_registro DESC", $t, $p);
            $est = [];
            foreach ($filas as $r) { $k = ucfirst($r['estado'] ?? 'sin cuenta'); $est[$k] = ($est[$k] ?? 0) + 1; }
            $con = count(array_filter($filas, fn($r) => $r['pedidos'] > 0));
            return [
                'kpis' => [['Clientes', count($filas)], ['Compraron al menos una vez', $con], ['Nunca compraron', count($filas) - $con]],
                'graficos' => $est ? [['tipo' => 'torta', 'titulo' => 'Clientes según estado de la cuenta', 'datos' => $est]] : [],
                'tabla' => [
                    'cols' => [['Cliente', 5, 'txt'], ['Correo', 6, 'txt'], ['Alta', 3, 'fecha'], ['Estado', 2, 'txt'], ['Pedidos', 2, 'int']],
                    'filas' => array_map(fn($r) => [$r['cliente'], $r['correo'], $r['alta'], ucfirst((string) $r['estado']), $r['pedidos']], $filas),
                ],
                'analisis' => [count($filas) . " clientes registrados; $con ya compraron."],
            ];
        },
    ],

    'clientes_frecuentes' => [
        'seccion' => 'clientes',
        'titulo'  => 'Clientes frecuentes',
        'desc'    => 'Clientes que compraron más de una vez.',
        'filtros' => ['fechas' => true, 'top' => true],
        'generar' => function (mysqli $c, array $f): array {
            $t = ''; $p = [];
            $w = rrango('pd.fecha_pedidos', $f, $t, $p);
            $filas = rq($c, "SELECT CONCAT(cl.nombre, ' ', cl.apellido) AS cliente, COUNT(*) AS pedidos, SUM(pd.monto_total) AS total, MIN(pd.fecha_pedidos) AS primera, MAX(pd.fecha_pedidos) AS ultima
                             FROM pedidos pd INNER JOIN clientes cl ON cl.id_cliente = pd.rela_id_cliente
                             WHERE pd.estado <> 'Cancelado' $w GROUP BY cl.id_cliente, cl.nombre, cl.apellido
                             HAVING COUNT(*) >= 2 ORDER BY pedidos DESC, total DESC", $t, $p);
            return [
                'kpis' => [['Clientes frecuentes', count($filas)], ['Pedidos de ellos', (int) rtotal($filas, 'pedidos')], ['Dinero que compraron', rplata(rtotal($filas, 'total'))]],
                'graficos' => [['tipo' => 'barras', 'titulo' => 'Cantidad de pedidos por cliente', 'datos' => rgraf($filas, 'cliente', 'pedidos'), 'max' => $f['top']]],
                'tabla' => [
                    'cols' => [['Cliente', 5, 'txt'], ['Pedidos', 2, 'int'], ['Total comprado', 3, 'plata'], ['Primera compra', 3, 'fecha'], ['Última compra', 3, 'fecha']],
                    'filas' => array_map(fn($r) => [$r['cliente'], $r['pedidos'], $r['total'], $r['primera'], $r['ultima']], $filas),
                ],
                'analisis' => [$filas ? "{$filas[0]['cliente']} es el cliente más frecuente con {$filas[0]['pedidos']} pedidos." : 'Ningún cliente compró más de una vez en el período.'],
            ];
        },
    ],

    'clientes_facturacion' => [
        'seccion' => 'clientes',
        'titulo'  => 'Clientes por facturación',
        'desc'    => 'Cuánto se le facturó a cada cliente, con el IVA incluido.',
        'filtros' => ['fechas' => true, 'top' => true],
        'generar' => function (mysqli $c, array $f): array {
            $t = ''; $p = [];
            $w = rrango('DATE(fa.fecha_emicion)', $f, $t, $p);
            $filas = rq($c, "SELECT CONCAT(cl.nombre, ' ', cl.apellido) AS cliente, COUNT(*) AS facturas, SUM(fa.monto_total_bruto) AS neto, SUM(fa.monto_total_impuesto) AS iva, SUM(fa.monto_total) AS total
                             FROM facturas fa INNER JOIN pedidos pd ON pd.id_pedidos = fa.rela_id_pedidos
                             INNER JOIN clientes cl ON cl.id_cliente = pd.rela_id_cliente
                             WHERE 1=1 $w GROUP BY cl.id_cliente, cl.nombre, cl.apellido ORDER BY total DESC", $t, $p);
            return [
                'kpis' => [['Total facturado', rplata(rtotal($filas, 'total'))], ['Facturas', (int) rtotal($filas, 'facturas')], ['IVA', rplata(rtotal($filas, 'iva'))]],
                'graficos' => [['tipo' => 'barras', 'titulo' => 'Clientes con más facturación', 'datos' => rgraf($filas, 'cliente', 'total'), 'dinero' => true, 'max' => $f['top']]],
                'tabla' => [
                    'cols' => [['Cliente', 5, 'txt'], ['Facturas', 2, 'int'], ['Neto', 3, 'plata'], ['IVA', 3, 'plata'], ['Total', 3, 'plata']],
                    'filas' => array_map(fn($r) => [$r['cliente'], $r['facturas'], $r['neto'], $r['iva'], $r['total']], $filas),
                ],
                'analisis' => [$filas ? "{$filas[0]['cliente']} es el cliente con más facturación: " . rplata($filas[0]['total']) . '.' : 'No hay facturas emitidas en el período.'],
            ];
        },
    ],

    'clientes_inactivos' => [
        'seccion' => 'clientes',
        'titulo'  => 'Clientes inactivos',
        'desc'    => 'Clientes que hace tiempo no compran o nunca compraron.',
        'filtros' => ['umbral' => ['defecto' => 90, 'etiqueta' => 'Días sin comprar']],
        'generar' => function (mysqli $c, array $f): array {
            $dias = max(1, $f['umbral']);
            $filas = rq($c, "SELECT CONCAT(cl.nombre, ' ', cl.apellido) AS cliente, u.correo, cl.fecha_registro AS alta, x.ultima, x.pedidos
                             FROM clientes cl LEFT JOIN usuarios u ON u.id_usuario = cl.rela_id_usuario
                             LEFT JOIN (SELECT rela_id_cliente, MAX(fecha_pedidos) AS ultima, COUNT(*) AS pedidos FROM pedidos WHERE estado <> 'Cancelado' GROUP BY rela_id_cliente) x ON x.rela_id_cliente = cl.id_cliente
                             WHERE x.ultima IS NULL OR x.ultima < DATE_SUB(CURDATE(), INTERVAL ? DAY)
                             ORDER BY x.ultima IS NULL DESC, x.ultima ASC", 'i', [$dias]);
            $nunca = count(array_filter($filas, fn($r) => $r['ultima'] === null));
            return [
                'subtitulo' => "Sin comprar hace más de $dias días",
                'kpis' => [['Clientes inactivos', count($filas)], ['Nunca compraron', $nunca], ['Compraron hace tiempo', count($filas) - $nunca]],
                'graficos' => [['tipo' => 'torta', 'titulo' => 'Tipo de clientes inactivos', 'datos' => array_filter(['Nunca compraron' => $nunca, 'Compraron hace tiempo' => count($filas) - $nunca])]],
                'tabla' => [
                    'cols' => [['Cliente', 5, 'txt'], ['Correo', 6, 'txt'], ['Alta', 3, 'fecha'], ['Última compra', 3, 'fecha'], ['Pedidos', 2, 'int']],
                    'filas' => array_map(fn($r) => [$r['cliente'], $r['correo'], $r['alta'], $r['ultima'], $r['pedidos'] ?? 0], $filas),
                ],
                'analisis' => [count($filas) . " cliente(s) no compran hace más de $dias días; podrían recibir una promoción para volver."],
            ];
        },
    ],

    'nuevos_clientes' => [
        'seccion' => 'clientes',
        'titulo'  => 'Nuevos clientes',
        'desc'    => 'Cuántos clientes se registraron en cada mes.',
        'filtros' => ['fechas' => true],
        'generar' => function (mysqli $c, array $f): array {
            $t = ''; $p = [];
            $w = rrango('DATE(cl.fecha_registro)', $f, $t, $p);
            $filas = rq($c, "SELECT DATE_FORMAT(cl.fecha_registro, '%Y-%m') AS periodo, COUNT(*) AS nuevos,
                                    SUM(EXISTS(SELECT 1 FROM pedidos pd WHERE pd.rela_id_cliente = cl.id_cliente AND pd.estado <> 'Cancelado')) AS compraron
                             FROM clientes cl WHERE 1=1 $w GROUP BY periodo ORDER BY periodo ASC", $t, $p);
            $serie = [];
            foreach (array_slice($filas, -24) as $r) $serie[rmes($r['periodo'])] = (float) $r['nuevos'];
            $n = (int) rtotal($filas, 'nuevos');
            return [
                'kpis' => [['Clientes nuevos', $n], ['Ya compraron', (int) rtotal($filas, 'compraron')], ['Meses con altas', count($filas)]],
                'graficos' => [['tipo' => count($serie) >= 3 ? 'lineas' : 'barras', 'titulo' => 'Clientes registrados por mes', 'datos' => $serie]],
                'tabla' => [
                    'cols' => [['Mes', 3, 'txt'], ['Clientes nuevos', 3, 'int'], ['Ya compraron', 3, 'int']],
                    'filas' => array_map(fn($r) => [rmes($r['periodo']), $r['nuevos'], $r['compraron']], array_reverse($filas)),
                ],
                'analisis' => [$n . ' cliente(s) se registraron en el período.'],
            ];
        },
    ],

    'historial_cliente' => [
        'seccion' => 'clientes',
        'titulo'  => 'Historial de un cliente',
        'desc'    => 'Todos los pedidos de un cliente elegido. Hay que elegir el cliente.',
        'filtros' => ['fechas' => true, 'cliente' => true],
        'generar' => function (mysqli $c, array $f): array {
            if (!$f['cliente']) {
                return ['kpis' => [], 'graficos' => [], 'tabla' => ['cols' => [['Aviso', 10, 'txt']], 'filas' => [['Elegí un cliente en los filtros para ver su historial.']]], 'analisis' => []];
            }
            $cl = rq1($c, "SELECT CONCAT(nombre, ' ', apellido) AS n FROM clientes WHERE id_cliente = ?", 'i', [$f['cliente']]);
            $t = 'i'; $p = [$f['cliente']];
            $w = rrango('pd.fecha_pedidos', $f, $t, $p);
            $filas = rq($c, "SELECT pd.id_pedidos, pd.fecha_pedidos, pd.estado, pd.monto_total,
                                    (SELECT SUM(dp.cantidad) FROM detalle_pedidos dp WHERE dp.rela_id_pedidos = pd.id_pedidos) AS unidades,
                                    (SELECT mp.nombre FROM pagos pg INNER JOIN metodos_pago mp ON mp.id_metodo_pago = pg.rela_id_metodo_pago WHERE pg.rela_id_pedidos = pd.id_pedidos ORDER BY pg.id_pagos DESC LIMIT 1) AS metodo
                             FROM pedidos pd WHERE pd.rela_id_cliente = ? $w ORDER BY pd.fecha_pedidos DESC, pd.id_pedidos DESC", $t, $p);
            $validos = array_filter($filas, fn($r) => $r['estado'] !== 'Cancelado');
            $total = rtotal($validos, 'monto_total');
            $est = [];
            foreach ($filas as $r) $est[$r['estado']] = ($est[$r['estado']] ?? 0) + 1;
            return [
                'kpis' => [['Pedidos', count($filas)], ['Total comprado', rplata($total)], ['Ticket promedio', rplata($validos ? $total / count($validos) : 0)]],
                'graficos' => $est ? [['tipo' => 'torta', 'titulo' => 'Pedidos según estado', 'datos' => $est]] : [],
                'tabla' => [
                    'cols' => [['Pedido', 2, 'txt'], ['Fecha', 3, 'fecha'], ['Estado', 3, 'txt'], ['Unidades', 2, 'int'], ['Pago', 4, 'txt'], ['Monto', 3, 'plata']],
                    'filas' => array_map(fn($r) => ['#' . $r['id_pedidos'], $r['fecha_pedidos'], $r['estado'], $r['unidades'], $r['metodo'], $r['monto_total']], $filas),
                ],
                'analisis' => [($cl['n'] ?? 'El cliente') . ' tiene ' . count($filas) . ' pedido(s); el total comprado no cuenta los cancelados.'],
            ];
        },
    ],

];