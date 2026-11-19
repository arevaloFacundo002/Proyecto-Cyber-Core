<?php

if (!defined('REPORTES_OK')) {
    http_response_code(403);
    exit;
}

return [

    'ingresos' => [
        'seccion' => 'financieros',
        'titulo'  => 'Ingresos',
        'desc'    => 'Dinero realmente cobrado (pagos aprobados), por día, mes o año.',
        'filtros' => ['fechas' => true,
            'agrupar' => ['etiqueta' => 'Agrupar', 'defecto' => 'mes', 'opciones' => ['dia' => 'Por día', 'mes' => 'Por mes', 'anio' => 'Por año']]],
        'generar' => function (mysqli $c, array $f): array {
            $fmt = ['dia' => '%Y-%m-%d', 'mes' => '%Y-%m', 'anio' => '%Y'][$f['agrupar']];
            $t = ''; $p = [];
            $w = rrango('DATE(pg.fechas_pagos)', $f, $t, $p);
            $filas = rq($c, "SELECT DATE_FORMAT(pg.fechas_pagos, '$fmt') AS periodo, COUNT(*) AS pagos, SUM(pg.monto) AS total
                             FROM pagos pg WHERE pg.estado_pagos = 'Aprobado' $w GROUP BY periodo ORDER BY periodo ASC", $t, $p);
            $etq = function ($per) use ($f) {
                if ($f['agrupar'] === 'dia') return substr($per, 8, 2) . '/' . substr($per, 5, 2);
                if ($f['agrupar'] === 'mes') return rmes($per);
                return $per;
            };
            $serie = [];
            foreach (array_slice($filas, $f['agrupar'] === 'dia' ? -31 : -24) as $r) $serie[$etq($r['periodo'])] = (float) $r['total'];
            $total = rtotal($filas, 'total');
            $pagos = (int) rtotal($filas, 'pagos');
            return [
                'subtitulo' => 'Solo pagos aprobados',
                'kpis' => [['Total cobrado', rplata($total)], ['Pagos aprobados', $pagos], ['Pago promedio', rplata($pagos ? $total / $pagos : 0)]],
                'graficos' => [['tipo' => count($serie) >= 3 ? 'lineas' : 'barras', 'titulo' => 'Dinero cobrado por período', 'datos' => $serie, 'dinero' => true]],
                'tabla' => [
                    'cols' => [['Período', 3, 'txt'], ['Pagos', 2, 'int'], ['Total cobrado', 3, 'plata']],
                    'filas' => array_map(fn($r) => [$etq($r['periodo']), $r['pagos'], $r['total']], array_reverse($filas)),
                ],
                'analisis' => [$pagos . ' pagos aprobados por ' . rplata($total) . '. Los pagos pendientes o cancelados no se cuentan.'],
            ];
        },
    ],

    'ganancias_estimadas' => [
        'seccion' => 'financieros',
        'titulo'  => 'Ganancias estimadas',
        'desc'    => 'Lo vendido menos lo que costó la mercadería (según la última compra de cada producto).',
        'filtros' => ['fechas' => true, 'top' => true,
            'agrupar' => ['etiqueta' => 'Agrupar por', 'defecto' => 'producto', 'opciones' => ['producto' => 'Producto', 'categoria' => 'Categoría', 'marca' => 'Marca']]],
        'generar' => function (mysqli $c, array $f): array {
            $t = ''; $p = [];
            $w = rrango('pd.fecha_pedidos', $f, $t, $p);
            $campo = ['producto' => 'p.nombre', 'categoria' => "COALESCE(c.nombre,'Sin categoría')", 'marca' => 'm.nombre_marca'][$f['agrupar']];
            $filas = rq($c, "
                SELECT $campo AS grupo, SUM(dp.cantidad) AS unidades, SUM(dp.subtotal_final) AS ventas,
                       SUM(CASE WHEN k.costo IS NULL THEN 0 ELSE dp.cantidad * k.costo END) AS costo,
                       SUM(CASE WHEN k.costo IS NULL THEN 0 ELSE dp.subtotal_final END) AS ventas_con_costo,
                       SUM(CASE WHEN k.costo IS NULL THEN 1 ELSE 0 END) AS sin_costo
                FROM detalle_pedidos dp
                INNER JOIN pedidos pd ON pd.id_pedidos = dp.rela_id_pedidos
                INNER JOIN productos p ON p.id_producto = dp.rela_id_productos
                LEFT JOIN categorias c ON c.id_categoria = p.rela_id_categoria
                INNER JOIN marcas m ON m.id_marca = p.rela_id_marca
                LEFT JOIN (SELECT cl.prod, SUBSTRING_INDEX(GROUP_CONCAT(cl.precio ORDER BY cl.fecha DESC), ',', 1) AS costo
                           FROM " . rcompras_lineas() . " GROUP BY cl.prod) k ON k.prod = p.id_producto
                WHERE pd.estado <> 'Cancelado' $w
                GROUP BY $campo ORDER BY (SUM(CASE WHEN k.costo IS NULL THEN 0 ELSE dp.subtotal_final END) - SUM(CASE WHEN k.costo IS NULL THEN 0 ELSE dp.cantidad * k.costo END)) DESC", $t, $p);
            $ventas = 0; $costo = 0; $vcc = 0; $sin = 0;
            foreach ($filas as &$r) {
                $r['ganancia'] = $r['ventas_con_costo'] - $r['costo'];
                $r['margen'] = $r['ventas_con_costo'] > 0 ? $r['ganancia'] / $r['ventas_con_costo'] * 100 : null;
                $ventas += $r['ventas']; $costo += $r['costo']; $vcc += $r['ventas_con_costo']; $sin += $r['sin_costo'];
            }
            unset($r);
            $gan = $vcc - $costo;
            $graf = [];
            foreach ($filas as $r) $graf[$r['grupo']] = (float) $r['ganancia'];
            $etiq = ['producto' => 'Producto', 'categoria' => 'Categoría', 'marca' => 'Marca'][$f['agrupar']];
            return [
                'subtitulo' => 'Agrupado por ' . strtolower($etiq),
                'kpis' => [['Ventas con costo conocido', rplata($vcc)], ['Costo estimado', rplata($costo)], ['Ganancia estimada', rplata($gan)], ['Margen', $vcc > 0 ? rpct($gan, $vcc) : '-']],
                'graficos' => [['tipo' => 'barras', 'titulo' => "Ganancia estimada por " . strtolower($etiq), 'datos' => $graf, 'dinero' => true, 'max' => $f['top']]],
                'tabla' => [
                    'cols' => [[$etiq, 5, 'txt'], ['Unidades', 2, 'int'], ['Ventas', 3, 'plata'], ['Costo', 3, 'plata'], ['Ganancia', 3, 'plata'], ['Margen', 2, 'pct']],
                    'filas' => array_map(fn($r) => [$r['grupo'], $r['unidades'], $r['ventas_con_costo'], $r['costo'], $r['ganancia'], $r['margen']], $filas),
                ],
                'analisis' => [
                    'Es una estimación: el costo de cada producto es el precio de su última compra registrada.',
                    $sin ? "$sin línea(s) de venta corresponden a productos sin compras cargadas y no entran en el cálculo." : 'Todos los productos vendidos tienen costo cargado.',
                ],
            ];
        },
    ],

    'resumen_financiero' => [
        'seccion' => 'financieros',
        'titulo'  => 'Resumen financiero',
        'desc'    => 'Lo cobrado, lo comprado, lo devuelto y el saldo del período.',
        'filtros' => ['fechas' => true],
        'generar' => function (mysqli $c, array $f): array {
            $t = ''; $p = [];
            $w = rrango('DATE(pg.fechas_pagos)', $f, $t, $p);
            $cobrado = (float) (rq1($c, "SELECT COALESCE(SUM(pg.monto),0) AS v FROM pagos pg WHERE pg.estado_pagos = 'Aprobado' $w", $t, $p)['v'] ?? 0);

            $t = ''; $p = [];
            $w = rrango('pd.fecha_pedidos', $f, $t, $p);
            $vend = rq1($c, "SELECT COALESCE(SUM(pd.monto_total),0) AS v, COUNT(*) AS n FROM pedidos pd WHERE pd.estado <> 'Cancelado' $w", $t, $p);

            $t = ''; $p = [];
            $w = rrango('cl.fecha', $f, $t, $p);
            $compras = (float) (rq1($c, "SELECT COALESCE(SUM(cl.subtotal),0) AS v FROM " . rcompras_lineas() . " WHERE 1=1 $w", $t, $p)['v'] ?? 0);

            $t = ''; $p = [];
            $w = rrango('d.fecha_solicitud', $f, $t, $p);
            $dev = (float) (rq1($c, "SELECT COALESCE(SUM(d.monto_reembolso),0) AS v FROM devolucion d WHERE d.estado = 'Recibida' $w", $t, $p)['v'] ?? 0);

            $saldo = $cobrado - $compras - $dev;
            return [
                'kpis' => [['Vendido (pedidos)', rplata($vend['v'])], ['Cobrado', rplata($cobrado)], ['Compras a proveedores', rplata($compras)], ['Devoluciones reembolsadas', rplata($dev)], ['Saldo estimado', rplata($saldo)]],
                'graficos' => [['tipo' => 'barras', 'titulo' => 'Cobrado, comprado y devuelto', 'datos' => ['Cobrado' => $cobrado, 'Compras' => $compras, 'Devoluciones' => $dev], 'dinero' => true]],
                'tabla' => [
                    'cols' => [['Concepto', 6, 'txt'], ['Monto', 4, 'plata']],
                    'filas' => [['Vendido en pedidos (sin cancelados)', $vend['v']], ['Cobrado (pagos aprobados)', $cobrado], ['Compras a proveedores', -$compras], ['Devoluciones reembolsadas', -$dev], ['Saldo estimado', $saldo]],
                ],
                'analisis' => ['Saldo estimado = cobrado − compras − devoluciones reembolsadas. No incluye sueldos, impuestos ni otros gastos.',
                               'Se cuentan devoluciones en estado "Recibida".'],
            ];
        },
    ],

];