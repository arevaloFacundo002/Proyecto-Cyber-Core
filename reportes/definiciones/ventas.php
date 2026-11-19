<?php

if (!defined('REPORTES_OK')) {
    http_response_code(403);
    exit;
}

$notaVentas = 'Se cuentan como ventas todos los pedidos que no fueron cancelados.';

return [

    'ventas_periodo' => [
        'seccion' => 'ventas',
        'titulo'  => 'Ventas por período',
        'desc'    => 'Total vendido, cantidad de ventas y ticket promedio por día, mes o año.',
        'filtros' => [
            'fechas'  => true,
            'agrupar' => ['etiqueta' => 'Agrupar', 'defecto' => 'mes',
                          'opciones' => ['dia' => 'Por día', 'mes' => 'Por mes', 'anio' => 'Por año']],
        ],
        'generar' => function (mysqli $c, array $f) use ($notaVentas): array {
            $fmt = ['dia' => '%Y-%m-%d', 'mes' => '%Y-%m', 'anio' => '%Y'][$f['agrupar']];
            $t = ''; $p = [];
            $w = rrango('pd.fecha_pedidos', $f, $t, $p);
            $filas = rq($c, "
                SELECT DATE_FORMAT(pd.fecha_pedidos, '$fmt') AS periodo, COUNT(*) AS ventas, SUM(pd.monto_total) AS total
                FROM pedidos pd WHERE pd.estado <> 'Cancelado' $w
                GROUP BY periodo ORDER BY periodo ASC", $t, $p);

            $t = ''; $p = [];
            $w = rrango('pd.fecha_pedidos', $f, $t, $p);
            $u = rq1($c, "SELECT COALESCE(SUM(dp.cantidad),0) AS u FROM detalle_pedidos dp
                          INNER JOIN pedidos pd ON pd.id_pedidos = dp.rela_id_pedidos
                          WHERE pd.estado <> 'Cancelado' $w", $t, $p);

            $total = array_sum(array_column($filas, 'total'));
            $ventas = array_sum(array_column($filas, 'ventas'));
            $etq = function ($per) use ($f) {
                if ($f['agrupar'] === 'dia') return substr($per, 8, 2) . '/' . substr($per, 5, 2);
                if ($f['agrupar'] === 'mes') return rmes($per);
                return $per;
            };
            $serie = [];
            foreach (array_slice($filas, $f['agrupar'] === 'dia' ? -31 : -24) as $r) {
                $serie[$etq($r['periodo'])] = (float) $r['total'];
            }
            $mejor = null;
            foreach ($filas as $r) {
                if ($mejor === null || (float) $r['total'] > (float) $mejor['total']) $mejor = $r;
            }
            $analisis = [];
            if ($filas) {
                $analisis[] = "Se realizaron $ventas ventas por " . rplata($total) . ", con un ticket promedio de " . rplata($ventas ? $total / $ventas : 0) . ".";
                $analisis[] = "El mejor período fue " . $etq($mejor['periodo']) . " con " . rplata($mejor['total']) . " en " . $mejor['ventas'] . " venta(s).";
            }
            return [
                'kpis' => [
                    ['Total vendido', rplata($total)],
                    ['Cantidad de ventas', $ventas],
                    ['Ticket promedio', rplata($ventas ? $total / $ventas : 0)],
                    ['Unidades vendidas', rnum($u['u'] ?? 0)],
                ],
                'graficos' => [[
                    'tipo' => count($serie) >= 3 ? 'lineas' : 'barras', 'titulo' => 'Total vendido por período',
                    'nota' => $notaVentas . ' Incluye envío e impuestos.', 'datos' => $serie, 'dinero' => true,
                ]],
                'tabla' => [
                    'titulo' => 'Detalle por período',
                    'cols' => [['Período', 3, 'txt'], ['Ventas', 2, 'int'], ['Total', 3, 'plata'], ['Ticket promedio', 3, 'plata']],
                    'filas' => array_map(fn($r) => [$etq($r['periodo']), $r['ventas'], $r['total'], $r['ventas'] ? $r['total'] / $r['ventas'] : 0], array_reverse($filas)),
                ],
                'analisis' => $analisis,
            ];
        },
    ],

    'ventas_producto' => [
        'seccion' => 'ventas',
        'titulo'  => 'Ventas por producto',
        'desc'    => 'Unidades vendidas y dinero generado por cada producto.',
        'filtros' => ['fechas' => true, 'categoria' => true, 'marca' => true],
        'generar' => function (mysqli $c, array $f) use ($notaVentas): array {
            $t = ''; $p = [];
            $w = rrango('pd.fecha_pedidos', $f, $t, $p) . rcategoria($f, $t, $p) . rmarca($f, $t, $p);
            $filas = rq($c, "
                SELECT p.nombre AS producto, COALESCE(c.nombre,'Sin categoría') AS categoria, m.nombre_marca AS marca,
                       SUM(dp.cantidad) AS unidades, SUM(dp.subtotal_final) AS monto
                FROM detalle_pedidos dp
                INNER JOIN pedidos pd ON pd.id_pedidos = dp.rela_id_pedidos
                INNER JOIN productos p ON p.id_producto = dp.rela_id_productos
                LEFT JOIN categorias c ON c.id_categoria = p.rela_id_categoria
                INNER JOIN marcas m ON m.id_marca = p.rela_id_marca
                WHERE pd.estado <> 'Cancelado' $w
                GROUP BY p.id_producto, p.nombre, c.nombre, m.nombre_marca
                ORDER BY monto DESC, p.nombre ASC", $t, $p);
            $monto = array_sum(array_column($filas, 'monto'));
            $unid = array_sum(array_column($filas, 'unidades'));
            $graf = [];
            foreach ($filas as $r) $graf[$r['producto']] = (float) $r['monto'];
            return [
                'kpis' => [['Dinero generado', rplata($monto)], ['Unidades vendidas', rnum($unid)], ['Productos vendidos', count($filas)]],
                'graficos' => [
                    ['tipo' => 'barras', 'titulo' => 'Productos que más dinero generaron', 'nota' => $notaVentas . ' Los montos son por producto, sin envío.', 'datos' => $graf, 'dinero' => true],
                    ['tipo' => 'torta', 'titulo' => 'Participación en el dinero generado', 'datos' => $graf, 'dinero' => true],
                ],
                'tabla' => [
                    'cols' => [['Producto', 5, 'txt'], ['Categoría', 3, 'txt'], ['Marca', 3, 'txt'], ['Unidades', 2, 'int'], ['Monto', 3, 'plata'], ['% total', 2, 'pct']],
                    'filas' => array_map(fn($r) => [$r['producto'], $r['categoria'], $r['marca'], $r['unidades'], $r['monto'], $monto > 0 ? $r['monto'] / $monto * 100 : 0], $filas),
                ],
                'analisis' => $filas ? ["El producto que más dinero generó fue {$filas[0]['producto']} con " . rplata($filas[0]['monto']) . " (" . rpct($filas[0]['monto'], $monto) . " del total)."] : [],
            ];
        },
    ],

    'ventas_categoria' => [
        'seccion' => 'ventas',
        'titulo'  => 'Ventas por categoría',
        'desc'    => 'Ventas de cada categoría de productos.',
        'filtros' => ['fechas' => true],
        'generar' => function (mysqli $c, array $f): array {
            $t = ''; $p = [];
            $w = rrango('pd.fecha_pedidos', $f, $t, $p);
            $filas = rq($c, "
                SELECT COALESCE(c.nombre,'Sin categoría') AS categoria, SUM(dp.cantidad) AS unidades, SUM(dp.subtotal_final) AS monto
                FROM detalle_pedidos dp
                INNER JOIN pedidos pd ON pd.id_pedidos = dp.rela_id_pedidos
                INNER JOIN productos p ON p.id_producto = dp.rela_id_productos
                LEFT JOIN categorias c ON c.id_categoria = p.rela_id_categoria
                WHERE pd.estado <> 'Cancelado' $w
                GROUP BY c.id_categoria, c.nombre ORDER BY monto DESC", $t, $p);
            $monto = array_sum(array_column($filas, 'monto'));
            $graf = [];
            foreach ($filas as $r) $graf[$r['categoria']] = (float) $r['monto'];
            return [
                'kpis' => [['Dinero generado', rplata($monto)], ['Categorías con ventas', count($filas)], ['Categoría líder', $filas[0]['categoria'] ?? '-']],
                'graficos' => [
                    ['tipo' => 'torta', 'titulo' => 'Participación de cada categoría', 'datos' => $graf, 'dinero' => true],
                    ['tipo' => 'barras', 'titulo' => 'Dinero generado por categoría', 'datos' => $graf, 'dinero' => true],
                ],
                'tabla' => [
                    'cols' => [['Categoría', 5, 'txt'], ['Unidades', 2, 'int'], ['Monto', 3, 'plata'], ['% total', 2, 'pct']],
                    'filas' => array_map(fn($r) => [$r['categoria'], $r['unidades'], $r['monto'], $monto > 0 ? $r['monto'] / $monto * 100 : 0], $filas),
                ],
                'analisis' => $filas ? ["La categoría con más ventas es {$filas[0]['categoria']}, con " . rpct($filas[0]['monto'], $monto) . " del dinero generado."] : [],
            ];
        },
    ],

    'ventas_marca' => [
        'seccion' => 'ventas',
        'titulo'  => 'Ventas por marca',
        'desc'    => 'Rendimiento comercial de cada marca.',
        'filtros' => ['fechas' => true],
        'generar' => function (mysqli $c, array $f): array {
            $t = ''; $p = [];
            $w = rrango('pd.fecha_pedidos', $f, $t, $p);
            $filas = rq($c, "
                SELECT m.nombre_marca AS marca, SUM(dp.cantidad) AS unidades, SUM(dp.subtotal_final) AS monto
                FROM detalle_pedidos dp
                INNER JOIN pedidos pd ON pd.id_pedidos = dp.rela_id_pedidos
                INNER JOIN productos p ON p.id_producto = dp.rela_id_productos
                INNER JOIN marcas m ON m.id_marca = p.rela_id_marca
                WHERE pd.estado <> 'Cancelado' $w
                GROUP BY m.id_marca, m.nombre_marca ORDER BY monto DESC", $t, $p);
            $monto = array_sum(array_column($filas, 'monto'));
            $graf = [];
            foreach ($filas as $r) $graf[$r['marca']] = (float) $r['monto'];
            return [
                'kpis' => [['Dinero generado', rplata($monto)], ['Marcas con ventas', count($filas)], ['Marca líder', $filas[0]['marca'] ?? '-']],
                'graficos' => [
                    ['tipo' => 'torta', 'titulo' => 'Participación de cada marca', 'datos' => $graf, 'dinero' => true],
                    ['tipo' => 'barras', 'titulo' => 'Dinero generado por marca', 'datos' => $graf, 'dinero' => true],
                ],
                'tabla' => [
                    'cols' => [['Marca', 5, 'txt'], ['Unidades', 2, 'int'], ['Monto', 3, 'plata'], ['% total', 2, 'pct']],
                    'filas' => array_map(fn($r) => [$r['marca'], $r['unidades'], $r['monto'], $monto > 0 ? $r['monto'] / $monto * 100 : 0], $filas),
                ],
                'analisis' => $filas ? ["La marca que más vendió es {$filas[0]['marca']}, con " . rpct($filas[0]['monto'], $monto) . " del dinero generado."] : [],
            ];
        },
    ],

    'ventas_cliente' => [
        'seccion' => 'ventas',
        'titulo'  => 'Ventas por cliente',
        'desc'    => 'Cuánto compró cada cliente.',
        'filtros' => ['fechas' => true, 'top' => true],
        'generar' => function (mysqli $c, array $f): array {
            $t = ''; $p = [];
            $w = rrango('pd.fecha_pedidos', $f, $t, $p);
            $filas = rq($c, "
                SELECT CONCAT(cl.nombre, ' ', cl.apellido) AS cliente, COUNT(*) AS pedidos, SUM(pd.monto_total) AS total, MAX(pd.fecha_pedidos) AS ultima
                FROM pedidos pd INNER JOIN clientes cl ON cl.id_cliente = pd.rela_id_cliente
                WHERE pd.estado <> 'Cancelado' $w
                GROUP BY cl.id_cliente, cl.nombre, cl.apellido ORDER BY total DESC", $t, $p);
            $total = array_sum(array_column($filas, 'total'));
            $graf = [];
            foreach ($filas as $r) $graf[$r['cliente']] = (float) $r['total'];
            return [
                'kpis' => [['Total vendido', rplata($total)], ['Clientes que compraron', count($filas)], ['Cliente que más compró', $filas[0]['cliente'] ?? '-']],
                'graficos' => [['tipo' => 'barras', 'titulo' => "Clientes que más compraron (primeros {$f['top']})", 'datos' => $graf, 'dinero' => true, 'max' => $f['top']]],
                'tabla' => [
                    'cols' => [['Cliente', 5, 'txt'], ['Pedidos', 2, 'int'], ['Total comprado', 3, 'plata'], ['% total', 2, 'pct'], ['Última compra', 3, 'fecha']],
                    'filas' => array_map(fn($r) => [$r['cliente'], $r['pedidos'], $r['total'], $total > 0 ? $r['total'] / $total * 100 : 0, $r['ultima']], $filas),
                ],
                'analisis' => $filas ? ["{$filas[0]['cliente']} fue el cliente que más compró: " . rplata($filas[0]['total']) . " en {$filas[0]['pedidos']} pedido(s)."] : [],
            ];
        },
    ],

    'ventas_metodo' => [
        'seccion' => 'ventas',
        'titulo'  => 'Ventas por método de pago',
        'desc'    => 'Cuánto se cobró con cada medio de pago.',
        'filtros' => ['fechas' => true],
        'generar' => function (mysqli $c, array $f): array {
            $t = ''; $p = [];
            $w = rrango('DATE(pg.fechas_pagos)', $f, $t, $p);
            $filas = rq($c, "
                SELECT COALESCE(mp.nombre,'Sin método') AS metodo, COUNT(*) AS pagos, SUM(pg.monto) AS total
                FROM pagos pg LEFT JOIN metodos_pago mp ON mp.id_metodo_pago = pg.rela_id_metodo_pago
                WHERE pg.estado_pagos = 'Aprobado' $w
                GROUP BY mp.id_metodo_pago, mp.nombre ORDER BY total DESC", $t, $p);
            $total = array_sum(array_column($filas, 'total'));
            $graf = [];
            foreach ($filas as $r) $graf[$r['metodo']] = (float) $r['total'];
            return [
                'subtitulo' => 'Solo pagos aprobados',
                'kpis' => [['Cobrado', rplata($total)], ['Pagos aprobados', array_sum(array_column($filas, 'pagos'))], ['Método más usado', $filas[0]['metodo'] ?? '-']],
                'graficos' => [
                    ['tipo' => 'torta', 'titulo' => 'Participación de cada método', 'datos' => $graf, 'dinero' => true],
                    ['tipo' => 'barras', 'titulo' => 'Dinero cobrado por método', 'datos' => $graf, 'dinero' => true],
                ],
                'tabla' => [
                    'cols' => [['Método de pago', 5, 'txt'], ['Pagos', 2, 'int'], ['Total cobrado', 3, 'plata'], ['% total', 2, 'pct']],
                    'filas' => array_map(fn($r) => [$r['metodo'], $r['pagos'], $r['total'], $total > 0 ? $r['total'] / $total * 100 : 0], $filas),
                ],
                'analisis' => $filas ? ["El método más usado fue {$filas[0]['metodo']}, con " . rpct($filas[0]['total'], $total) . " del dinero cobrado."] : [],
            ];
        },
    ],

    'ventas_empleado' => [
        'seccion' => 'ventas',
        'titulo'  => 'Ventas por empleado',
        'desc'    => 'Pedidos y dinero de los pedidos que atendió cada empleado.',
        'solo_admin' => true,
        'filtros' => ['fechas' => true],
        'generar' => function (mysqli $c, array $f): array {
            $col = $c->query("SHOW COLUMNS FROM pedidos LIKE 'rela_id_usuario_atiende'");
            if (!$col || $col->num_rows === 0) {
                return ['kpis' => [], 'graficos' => [], 'tabla' => ['cols' => [['Aviso', 10, 'txt']], 'filas' => [['Falta ejecutar el script de la base de datos (columna "rela_id_usuario_atiende" en pedidos).']]], 'analisis' => []];
            }
            $t = ''; $p = [];
            $w = rrango('pd.fecha_pedidos', $f, $t, $p);
            $filas = rq($c, "
                SELECT COALESCE(u.nombre, 'Sin asignar') AS empleado, COALESCE(pf.nombre_perfil, '') AS perfil,
                       COUNT(*) AS pedidos, SUM(pd.estado = 'Entregado') AS entregados, SUM(pd.monto_total) AS total
                FROM pedidos pd
                LEFT JOIN usuarios u ON u.id_usuario = pd.rela_id_usuario_atiende
                LEFT JOIN perfiles pf ON pf.id_perfil = u.rela_id_perfil
                WHERE pd.estado <> 'Cancelado' $w
                GROUP BY u.id_usuario, u.nombre, pf.nombre_perfil ORDER BY total DESC", $t, $p);
            $total = rtotal($filas, 'total');
            $graf = rgraf($filas, 'empleado', 'total');
            $sin = 0;
            foreach ($filas as $r) if ($r['empleado'] === 'Sin asignar') $sin = (int) $r['pedidos'];
            return [
                'kpis' => [['Total vendido', rplata($total)], ['Pedidos', (int) rtotal($filas, 'pedidos')], ['Empleado que más vendió', ($filas[0]['empleado'] ?? '-')]],
                'graficos' => [
                    ['tipo' => 'barras', 'titulo' => 'Dinero de los pedidos atendidos por cada empleado', 'datos' => $graf, 'dinero' => true],
                    ['tipo' => 'torta', 'titulo' => 'Participación de cada empleado', 'datos' => $graf, 'dinero' => true],
                ],
                'tabla' => [
                    'cols' => [['Empleado', 4, 'txt'], ['Perfil', 3, 'txt'], ['Pedidos', 2, 'int'], ['Entregados', 2, 'int'], ['Total', 3, 'plata'], ['% total', 2, 'pct']],
                    'filas' => array_map(fn($r) => [$r['empleado'], ucfirst($r['perfil']), $r['pedidos'], $r['entregados'], $r['total'], $total > 0 ? $r['total'] / $total * 100 : 0], $filas),
                ],
                'analisis' => [
                    'Cada pedido se asigna al primer empleado que lo gestiona (cambia su estado o aprueba el pago). No se cuentan pedidos cancelados.',
                    $sin ? "$sin pedido(s) figuran \"Sin asignar\": son pedidos que nadie gestionó todavía." : 'Todos los pedidos tienen un empleado asignado.',
                ],
            ];
        },
    ],

    'ventas_canceladas' => [
        'seccion' => 'ventas',
        'titulo'  => 'Ventas canceladas',
        'desc'    => 'Pedidos cancelados, cuánto dinero representan y por qué.',
        'filtros' => ['fechas' => true],
        'generar' => function (mysqli $c, array $f): array {
            $t = ''; $p = [];
            $w = rrango('pd.fecha_pedidos', $f, $t, $p);
            $filas = rq($c, "
                SELECT pd.id_pedidos, pd.fecha_pedidos, CONCAT(cl.nombre, ' ', cl.apellido) AS cliente, pd.monto_total,
                       (SELECT h.nota FROM pedido_historial h WHERE h.rela_id_pedidos = pd.id_pedidos AND h.estado = 'Cancelado' ORDER BY h.id_historial DESC LIMIT 1) AS motivo
                FROM pedidos pd INNER JOIN clientes cl ON cl.id_cliente = pd.rela_id_cliente
                WHERE pd.estado = 'Cancelado' $w ORDER BY pd.id_pedidos DESC", $t, $p);
            $t = ''; $p = [];
            $w = rrango('pd.fecha_pedidos', $f, $t, $p);
            $todos = (int) (rq1($c, "SELECT COUNT(*) AS n FROM pedidos pd WHERE 1=1 $w", $t, $p)['n'] ?? 0);
            $monto = array_sum(array_column($filas, 'monto_total'));
            $mes = [];
            foreach (array_reverse($filas) as $r) {
                $k = rmes(substr($r['fecha_pedidos'], 0, 7));
                $mes[$k] = ($mes[$k] ?? 0) + 1;
            }
            return [
                'kpis' => [['Pedidos cancelados', count($filas)], ['Dinero no concretado', rplata($monto)], ['% sobre todos los pedidos', rpct(count($filas), $todos)]],
                'graficos' => $mes ? [['tipo' => 'barras', 'titulo' => 'Cancelaciones por mes', 'datos' => $mes]] : [],
                'tabla' => [
                    'cols' => [['Pedido', 2, 'txt'], ['Fecha', 3, 'fecha'], ['Cliente', 5, 'txt'], ['Monto', 3, 'plata'], ['Motivo', 6, 'txt']],
                    'filas' => array_map(fn($r) => ['#' . $r['id_pedidos'], $r['fecha_pedidos'], $r['cliente'], $r['monto_total'], $r['motivo'] ?: 'Sin motivo registrado'], $filas),
                ],
                'analisis' => ["Se cancelaron " . count($filas) . " de $todos pedidos (" . rpct(count($filas), $todos) . ")."],
            ];
        },
    ],

];