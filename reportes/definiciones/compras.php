<?php

if (!defined('REPORTES_OK')) {
    http_response_code(403);
    exit;
}

$notaFuente = 'Incluye las compras cargadas en el módulo de compras y las facturas de compra del inventario.';

return [

    'compras_generales' => [
        'seccion' => 'compras',
        'titulo'  => 'Compras generales',
        'desc'    => 'Todo lo comprado a proveedores, por día, mes o año.',
        'filtros' => ['fechas' => true, 'proveedor' => true,
            'agrupar' => ['etiqueta' => 'Agrupar', 'defecto' => 'mes', 'opciones' => ['dia' => 'Por día', 'mes' => 'Por mes', 'anio' => 'Por año']]],
        'generar' => function (mysqli $c, array $f) use ($notaFuente): array {
            $fmt = ['dia' => '%Y-%m-%d', 'mes' => '%Y-%m', 'anio' => '%Y'][$f['agrupar']];
            $t = ''; $p = [];
            $w = rrango('cl.fecha', $f, $t, $p);
            if ($f['proveedor']) { $w .= ' AND cl.prov = ?'; $t .= 'i'; $p[] = $f['proveedor']; }
            $filas = rq($c, "SELECT DATE_FORMAT(cl.fecha, '$fmt') AS periodo, COUNT(DISTINCT cl.prov, cl.comprobante, cl.fecha) AS compras, SUM(cl.cantidad) AS unidades, SUM(cl.subtotal) AS total
                             FROM " . rcompras_lineas() . " WHERE 1=1 $w GROUP BY periodo ORDER BY periodo ASC", $t, $p);
            $etq = function ($per) use ($f) {
                if ($f['agrupar'] === 'dia') return substr($per, 8, 2) . '/' . substr($per, 5, 2);
                if ($f['agrupar'] === 'mes') return rmes($per);
                return $per;
            };
            $serie = [];
            foreach (array_slice($filas, $f['agrupar'] === 'dia' ? -31 : -24) as $r) $serie[$etq($r['periodo'])] = (float) $r['total'];
            $total = rtotal($filas, 'total');
            return [
                'kpis' => [['Total comprado', rplata($total)], ['Compras', (int) rtotal($filas, 'compras')], ['Unidades compradas', rnum(rtotal($filas, 'unidades'))]],
                'graficos' => [['tipo' => count($serie) >= 3 ? 'lineas' : 'barras', 'titulo' => 'Dinero gastado en compras', 'nota' => $notaFuente, 'datos' => $serie, 'dinero' => true]],
                'tabla' => [
                    'cols' => [['Período', 3, 'txt'], ['Compras', 2, 'int'], ['Unidades', 2, 'int'], ['Total', 3, 'plata']],
                    'filas' => array_map(fn($r) => [$etq($r['periodo']), $r['compras'], $r['unidades'], $r['total']], array_reverse($filas)),
                ],
                'analisis' => [$filas ? 'En el período se gastaron ' . rplata($total) . ' en compras a proveedores.' : 'No hay compras en el período.'],
            ];
        },
    ],

    'compras_proveedor' => [
        'seccion' => 'compras',
        'titulo'  => 'Compras por proveedor',
        'desc'    => 'A qué proveedor le compramos más.',
        'filtros' => ['fechas' => true],
        'generar' => function (mysqli $c, array $f) use ($notaFuente): array {
            $t = ''; $p = [];
            $w = rrango('cl.fecha', $f, $t, $p);
            $filas = rq($c, "SELECT pr.nombre_apellido AS proveedor, COUNT(DISTINCT cl.comprobante, cl.fecha) AS compras, SUM(cl.cantidad) AS unidades, SUM(cl.subtotal) AS total, MAX(cl.fecha) AS ultima
                             FROM " . rcompras_lineas() . " INNER JOIN proveedores pr ON pr.id_proveedores = cl.prov
                             WHERE 1=1 $w GROUP BY pr.id_proveedores, pr.nombre_apellido ORDER BY total DESC", $t, $p);
            $total = rtotal($filas, 'total');
            $graf = rgraf($filas, 'proveedor', 'total');
            return [
                'kpis' => [['Total comprado', rplata($total)], ['Proveedores', count($filas)], ['Proveedor principal', $filas[0]['proveedor'] ?? '-']],
                'graficos' => [['tipo' => 'barras', 'titulo' => 'Dinero comprado a cada proveedor', 'nota' => $notaFuente, 'datos' => $graf, 'dinero' => true],
                               ['tipo' => 'torta', 'titulo' => 'Participación de cada proveedor', 'datos' => $graf, 'dinero' => true]],
                'tabla' => [
                    'cols' => [['Proveedor', 5, 'txt'], ['Compras', 2, 'int'], ['Unidades', 2, 'int'], ['Total', 3, 'plata'], ['% total', 2, 'pct'], ['Última compra', 3, 'fecha']],
                    'filas' => array_map(fn($r) => [$r['proveedor'], $r['compras'], $r['unidades'], $r['total'], $total > 0 ? $r['total'] / $total * 100 : 0, $r['ultima']], $filas),
                ],
                'analisis' => $filas ? ["Al proveedor que más le compramos es {$filas[0]['proveedor']}: " . rplata($filas[0]['total']) . " (" . rpct($filas[0]['total'], $total) . " de lo comprado)."] : [],
            ];
        },
    ],

    'precios_proveedor' => [
        'seccion' => 'compras',
        'titulo'  => 'Precios por proveedor',
        'desc'    => 'Qué proveedor vende más barato cada producto.',
        'filtros' => ['fechas' => true, 'proveedor' => true, 'categoria' => true, 'marca' => true],
        'generar' => function (mysqli $c, array $f): array {
            $t = ''; $p = [];
            $w = rrango('cl.fecha', $f, $t, $p) . rcategoria($f, $t, $p) . rmarca($f, $t, $p);
            if ($f['proveedor']) { $w .= ' AND cl.prov = ?'; $t .= 'i'; $p[] = $f['proveedor']; }
            $filas = rq($c, "SELECT p.nombre AS producto, pr.nombre_apellido AS proveedor, AVG(cl.precio) AS promedio, MIN(cl.precio) AS minimo, SUM(cl.cantidad) AS unidades
                             FROM " . rcompras_lineas() . "
                             INNER JOIN proveedores pr ON pr.id_proveedores = cl.prov
                             INNER JOIN productos p ON p.id_producto = cl.prod
                             LEFT JOIN categorias c ON c.id_categoria = p.rela_id_categoria
                             WHERE 1=1 $w GROUP BY p.id_producto, p.nombre, pr.id_proveedores, pr.nombre_apellido
                             ORDER BY p.nombre ASC, promedio ASC", $t, $p);
            $porProd = [];
            foreach ($filas as $r) $porProd[$r['producto']][] = $r;
            $mejor = []; $gana = [];
            foreach ($porProd as $prod => $lst) {
                $mejor[$prod] = $lst[0]['proveedor'];
                $gana[$lst[0]['proveedor']] = ($gana[$lst[0]['proveedor']] ?? 0) + 1;
            }
            arsort($gana);
            $out = [];
            foreach ($filas as $r) {
                $out[] = [$r['producto'], $r['proveedor'], $r['promedio'], $r['minimo'], $r['unidades'], $mejor[$r['producto']] === $r['proveedor'] ? 'Más barato' : ''];
            }
            return [
                'kpis' => [['Productos comparados', count($porProd)], ['Proveedores', count($gana)], ['Más barato en más productos', array_key_first($gana) ?? '-']],
                'graficos' => $gana ? [['tipo' => 'barras', 'titulo' => 'Productos en los que cada proveedor es el más barato', 'datos' => $gana]] : [],
                'tabla' => [
                    'cols' => [['Producto', 5, 'txt'], ['Proveedor', 4, 'txt'], ['Precio promedio', 3, 'plata'], ['Precio mínimo', 3, 'plata'], ['Unidades', 2, 'int'], ['', 3, 'txt']],
                    'filas' => $out,
                ],
                'analisis' => $gana ? [array_key_first($gana) . ' tiene el precio promedio más bajo en ' . reset($gana) . ' producto(s).',
                                       'Para comparar de verdad, un producto debe tener compras a más de un proveedor; si solo tiene uno, ese figura como el más barato.'] : [],
            ];
        },
    ],

    'compras_producto' => [
        'seccion' => 'compras',
        'titulo'  => 'Compras por producto',
        'desc'    => 'Cuánto y a qué costo compramos cada producto.',
        'filtros' => ['fechas' => true, 'proveedor' => true, 'categoria' => true, 'marca' => true],
        'generar' => function (mysqli $c, array $f): array {
            $t = ''; $p = [];
            $w = rrango('cl.fecha', $f, $t, $p) . rcategoria($f, $t, $p) . rmarca($f, $t, $p);
            if ($f['proveedor']) { $w .= ' AND cl.prov = ?'; $t .= 'i'; $p[] = $f['proveedor']; }
            $filas = rq($c, "SELECT p.nombre AS producto, SUM(cl.cantidad) AS unidades, AVG(cl.precio) AS promedio, SUM(cl.subtotal) AS total, MAX(cl.fecha) AS ultima
                             FROM " . rcompras_lineas() . "
                             INNER JOIN productos p ON p.id_producto = cl.prod
                             LEFT JOIN categorias c ON c.id_categoria = p.rela_id_categoria
                             WHERE 1=1 $w GROUP BY p.id_producto, p.nombre ORDER BY total DESC", $t, $p);
            return [
                'kpis' => [['Total comprado', rplata(rtotal($filas, 'total'))], ['Unidades', rnum(rtotal($filas, 'unidades'))], ['Productos', count($filas)]],
                'graficos' => [['tipo' => 'barras', 'titulo' => 'Dinero gastado por producto', 'datos' => rgraf($filas, 'producto', 'total'), 'dinero' => true]],
                'tabla' => [
                    'cols' => [['Producto', 5, 'txt'], ['Unidades', 2, 'int'], ['Costo promedio', 3, 'plata'], ['Total', 3, 'plata'], ['Última compra', 3, 'fecha']],
                    'filas' => array_map(fn($r) => [$r['producto'], $r['unidades'], $r['promedio'], $r['total'], $r['ultima']], $filas),
                ],
                'analisis' => $filas ? ["El producto en el que más se gastó es {$filas[0]['producto']} (" . rplata($filas[0]['total']) . ")."] : [],
            ];
        },
    ],

];