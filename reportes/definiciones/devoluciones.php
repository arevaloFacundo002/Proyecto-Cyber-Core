<?php

if (!defined('REPORTES_OK')) {
    http_response_code(403);
    exit;
}

return [

    'devoluciones_general' => [
        'seccion' => 'devoluciones',
        'titulo'  => 'Devoluciones generales',
        'desc'    => 'Todas las devoluciones, su estado y el dinero reembolsado.',
        'filtros' => ['fechas' => true],
        'generar' => function (mysqli $c, array $f): array {
            $t = ''; $p = [];
            $w = rrango('d.fecha_solicitud', $f, $t, $p);
            $filas = rq($c, "SELECT d.id_devolucion, d.fecha_solicitud, d.rela_id_pedidos AS pedido, d.estado, COALESCE(d.tipo_motivo, d.motivo) AS motivo, d.monto_reembolso
                             FROM devolucion d WHERE 1=1 $w ORDER BY d.fecha_solicitud DESC, d.id_devolucion DESC", $t, $p);
            $est = [];
            foreach ($filas as $r) $est[$r['estado']] = ($est[$r['estado']] ?? 0) + 1;
            $reemb = rtotal(array_filter($filas, fn($r) => $r['estado'] === 'Recibida'), 'monto_reembolso');
            return [
                'kpis' => [['Devoluciones', count($filas)], ['Monto solicitado', rplata(rtotal($filas, 'monto_reembolso'))], ['Reembolsado (recibidas)', rplata($reemb)]],
                'graficos' => $est ? [['tipo' => 'torta', 'titulo' => 'Devoluciones por estado', 'datos' => $est]] : [],
                'tabla' => [
                    'cols' => [['N°', 1, 'txt'], ['Fecha', 3, 'fecha'], ['Pedido', 2, 'txt'], ['Estado', 3, 'txt'], ['Motivo', 6, 'txt'], ['Monto', 3, 'plata']],
                    'filas' => array_map(fn($r) => ['#' . $r['id_devolucion'], $r['fecha_solicitud'], '#' . $r['pedido'], $r['estado'], $r['motivo'], $r['monto_reembolso']], $filas),
                ],
                'analisis' => [count($filas) . ' devolución(es) en el período. Se considera reembolsada cuando la mercadería fue recibida.'],
            ];
        },
    ],

    'devoluciones_producto' => [
        'seccion' => 'devoluciones',
        'titulo'  => 'Devoluciones por producto',
        'desc'    => 'Qué productos se devuelven más.',
        'filtros' => ['fechas' => true, 'top' => true],
        'generar' => function (mysqli $c, array $f): array {
            $t = ''; $p = [];
            $w = rrango('d.fecha_solicitud', $f, $t, $p);
            $filas = rq($c, "SELECT p.nombre AS producto, SUM(i.cantidad) AS unidades, COUNT(DISTINCT d.id_devolucion) AS devoluciones, SUM(i.monto) AS monto
                             FROM devolucion_items i INNER JOIN devolucion d ON d.id_devolucion = i.rela_id_devolucion
                             INNER JOIN productos p ON p.id_producto = i.rela_id_productos
                             WHERE 1=1 $w GROUP BY p.id_producto, p.nombre ORDER BY unidades DESC, monto DESC", $t, $p);
            return [
                'kpis' => [['Unidades devueltas', rnum(rtotal($filas, 'unidades'))], ['Productos', count($filas)], ['Dinero', rplata(rtotal($filas, 'monto'))]],
                'graficos' => [['tipo' => 'barras', 'titulo' => 'Unidades devueltas por producto', 'datos' => rgraf($filas, 'producto', 'unidades'), 'max' => $f['top']]],
                'tabla' => [
                    'cols' => [['Producto', 6, 'txt'], ['Devoluciones', 3, 'int'], ['Unidades', 2, 'int'], ['Monto', 3, 'plata']],
                    'filas' => array_map(fn($r) => [$r['producto'], $r['devoluciones'], $r['unidades'], $r['monto']], $filas),
                ],
                'analisis' => [$filas ? "El producto más devuelto es {$filas[0]['producto']} ({$filas[0]['unidades']} unidades)." : 'No hay devoluciones con productos en el período.'],
            ];
        },
    ],

    'devoluciones_motivo' => [
        'seccion' => 'devoluciones',
        'titulo'  => 'Devoluciones por motivo',
        'desc'    => 'Por qué los clientes devuelven productos.',
        'filtros' => ['fechas' => true],
        'generar' => function (mysqli $c, array $f): array {
            $t = ''; $p = [];
            $w = rrango('d.fecha_solicitud', $f, $t, $p);
            $filas = rq($c, "SELECT COALESCE(d.tipo_motivo, 'Sin clasificar') AS motivo, COUNT(*) AS cantidad, SUM(d.monto_reembolso) AS monto
                             FROM devolucion d WHERE 1=1 $w GROUP BY motivo ORDER BY cantidad DESC", $t, $p);
            $n = (int) rtotal($filas, 'cantidad');
            return [
                'kpis' => [['Devoluciones', $n], ['Motivo más común', ucfirst($filas[0]['motivo'] ?? '-')], ['Dinero involucrado', rplata(rtotal($filas, 'monto'))]],
                'graficos' => [['tipo' => 'torta', 'titulo' => 'Devoluciones por motivo', 'datos' => rgraf($filas, 'motivo', 'cantidad')]],
                'tabla' => [
                    'cols' => [['Motivo', 5, 'txt'], ['Cantidad', 2, 'int'], ['% del total', 2, 'pct'], ['Monto', 3, 'plata']],
                    'filas' => array_map(fn($r) => [ucfirst($r['motivo']), $r['cantidad'], $n ? $r['cantidad'] / $n * 100 : 0, $r['monto']], $filas),
                ],
                'analisis' => [$filas ? 'El motivo más frecuente es "' . $filas[0]['motivo'] . '". Las devoluciones viejas sin clasificar figuran como "Sin clasificar".' : 'No hay devoluciones en el período.'],
            ];
        },
    ],

];