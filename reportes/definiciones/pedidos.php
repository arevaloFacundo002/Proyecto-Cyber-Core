<?php

if (!defined('REPORTES_OK')) {
    http_response_code(403);
    exit;
}

return [

    'pedidos_estado' => [
        'seccion' => 'pedidos',
        'titulo'  => 'Pedidos por estado',
        'desc'    => 'Cuántos pedidos hay en cada estado y cuánto dinero representan.',
        'filtros' => ['fechas' => true],
        'generar' => function (mysqli $c, array $f): array {
            $t = ''; $p = [];
            $w = rrango('pd.fecha_pedidos', $f, $t, $p);
            $filas = rq($c, "SELECT pd.estado, COUNT(*) AS pedidos, SUM(pd.monto_total) AS total FROM pedidos pd WHERE 1=1 $w GROUP BY pd.estado ORDER BY pedidos DESC", $t, $p);
            $n = (int) rtotal($filas, 'pedidos');
            return [
                'kpis' => [['Pedidos', $n], ['Dinero total', rplata(rtotal($filas, 'total'))], ['Estado más común', $filas[0]['estado'] ?? '-']],
                'graficos' => [['tipo' => 'torta', 'titulo' => 'Pedidos por estado', 'datos' => rgraf($filas, 'estado', 'pedidos')],
                               ['tipo' => 'barras', 'titulo' => 'Dinero por estado', 'datos' => rgraf($filas, 'estado', 'total'), 'dinero' => true]],
                'tabla' => [
                    'cols' => [['Estado', 5, 'txt'], ['Pedidos', 2, 'int'], ['% del total', 2, 'pct'], ['Monto', 3, 'plata']],
                    'filas' => array_map(fn($r) => [$r['estado'], $r['pedidos'], $n ? $r['pedidos'] / $n * 100 : 0, $r['total']], $filas),
                ],
                'analisis' => $filas ? ["De $n pedidos, la mayoría está en estado \"{$filas[0]['estado']}\" ({$filas[0]['pedidos']})."] : [],
            ];
        },
    ],

    'pedidos_pendientes' => [
        'seccion' => 'pedidos',
        'titulo'  => 'Pedidos pendientes',
        'desc'    => 'Pedidos que todavía no se entregaron ni se cancelaron, con los días que llevan esperando.',
        'filtros' => [],
        'generar' => function (mysqli $c, array $f): array {
            $filas = rq($c, "SELECT pd.id_pedidos, pd.fecha_pedidos, pd.estado, CONCAT(cl.nombre, ' ', cl.apellido) AS cliente, pd.monto_total, DATEDIFF(CURDATE(), pd.fecha_pedidos) AS dias
                             FROM pedidos pd INNER JOIN clientes cl ON cl.id_cliente = pd.rela_id_cliente
                             WHERE pd.estado NOT IN ('Entregado','Cancelado') ORDER BY pd.fecha_pedidos ASC, pd.id_pedidos ASC");
            $est = [];
            foreach ($filas as $r) $est[$r['estado']] = ($est[$r['estado']] ?? 0) + 1;
            $max = $filas ? max(array_column($filas, 'dias')) : 0;
            return [
                'kpis' => [['Pedidos pendientes', count($filas)], ['Dinero pendiente', rplata(rtotal($filas, 'monto_total'))], ['Espera más larga', $max . ' días']],
                'graficos' => $est ? [['tipo' => 'barras', 'titulo' => 'Pendientes por estado', 'datos' => $est]] : [],
                'tabla' => [
                    'cols' => [['Pedido', 2, 'txt'], ['Fecha', 3, 'fecha'], ['Cliente', 5, 'txt'], ['Estado', 3, 'txt'], ['Días', 2, 'int'], ['Monto', 3, 'plata']],
                    'filas' => array_map(fn($r) => ['#' . $r['id_pedidos'], $r['fecha_pedidos'], $r['cliente'], $r['estado'], $r['dias'], $r['monto_total']], $filas),
                ],
                'analisis' => [count($filas) . ' pedido(s) esperan ser entregados; los más antiguos aparecen primero.'],
            ];
        },
    ],

    'entregas' => [
        'seccion' => 'pedidos',
        'titulo'  => 'Entregas y envíos',
        'desc'    => 'Estado de los envíos y empresas de transporte utilizadas.',
        'filtros' => ['fechas' => true],
        'generar' => function (mysqli $c, array $f): array {
            $t = ''; $p = [];
            $w = rrango('e.fecha_envio', $f, $t, $p);
            $filas = rq($c, "SELECT e.rela_id_pedidos AS pedido, e.fecha_envio, COALESCE(e.empresa_transporte,'Sin asignar') AS empresa, e.num_seguimiento, COALESCE(e.estado_envio,'Sin estado') AS estado, e.fecha_estimada_entrega AS estimada
                             FROM envios e WHERE 1=1 $w ORDER BY e.fecha_envio DESC, e.id_envios DESC", $t, $p);
            $est = []; $emp = [];
            foreach ($filas as $r) { $est[$r['estado']] = ($est[$r['estado']] ?? 0) + 1; $emp[$r['empresa']] = ($emp[$r['empresa']] ?? 0) + 1; }
            return [
                'kpis' => [['Envíos', count($filas)], ['Entregados', $est['Entregado'] ?? 0], ['En camino', $est['En camino'] ?? 0]],
                'graficos' => [['tipo' => 'torta', 'titulo' => 'Envíos por estado', 'datos' => $est], ['tipo' => 'barras', 'titulo' => 'Envíos por empresa de transporte', 'datos' => $emp]],
                'tabla' => [
                    'cols' => [['Pedido', 2, 'txt'], ['Fecha envío', 3, 'fecha'], ['Empresa', 4, 'txt'], ['Seguimiento', 4, 'txt'], ['Estado', 3, 'txt'], ['Entrega estimada', 3, 'fecha']],
                    'filas' => array_map(fn($r) => ['#' . $r['pedido'], $r['fecha_envio'], $r['empresa'], $r['num_seguimiento'], $r['estado'], $r['estimada']], $filas),
                ],
                'analisis' => [count($filas) . ' envío(s) en el período; ' . ($est['Entregado'] ?? 0) . ' ya fueron entregados.'],
            ];
        },
    ],

    'tiempos_entrega' => [
        'seccion' => 'pedidos',
        'titulo'  => 'Tiempos de entrega',
        'desc'    => 'Cuántos días pasan desde que se hace el pedido hasta que se entrega.',
        'filtros' => ['fechas' => true],
        'generar' => function (mysqli $c, array $f): array {
            $t = ''; $p = [];
            $w = rrango('pd.fecha_pedidos', $f, $t, $p);
            $filas = rq($c, "SELECT pd.id_pedidos, pd.fecha_pedidos, CONCAT(cl.nombre, ' ', cl.apellido) AS cliente, COALESCE(e.empresa_transporte,'Sin asignar') AS empresa,
                                    h.entrega, DATEDIFF(h.entrega, pd.fecha_pedidos) AS dias
                             FROM pedidos pd INNER JOIN clientes cl ON cl.id_cliente = pd.rela_id_cliente
                             INNER JOIN (SELECT rela_id_pedidos, MIN(fecha_evento) AS entrega FROM pedido_historial WHERE estado = 'Entregado' GROUP BY rela_id_pedidos) h ON h.rela_id_pedidos = pd.id_pedidos
                             LEFT JOIN envios e ON e.rela_id_pedidos = pd.id_pedidos
                             WHERE pd.estado = 'Entregado' $w ORDER BY pd.fecha_pedidos DESC", $t, $p);
            $dias = array_map('intval', array_column($filas, 'dias'));
            $prom = $dias ? array_sum($dias) / count($dias) : 0;
            $emp = [];
            foreach ($filas as $r) $emp[$r['empresa']][] = (int) $r['dias'];
            $graf = [];
            foreach ($emp as $k => $v) $graf[$k] = round(array_sum($v) / count($v), 1);
            return [
                'kpis' => [['Pedidos entregados', count($filas)], ['Promedio', number_format($prom, 1, ',', '.') . ' días'], ['Más rápido', ($dias ? min($dias) : 0) . ' días'], ['Más lento', ($dias ? max($dias) : 0) . ' días']],
                'graficos' => $graf ? [['tipo' => 'barras', 'titulo' => 'Días promedio por empresa de transporte', 'datos' => $graf]] : [],
                'tabla' => [
                    'cols' => [['Pedido', 2, 'txt'], ['Fecha pedido', 3, 'fecha'], ['Cliente', 5, 'txt'], ['Empresa', 4, 'txt'], ['Entregado', 3, 'fecha'], ['Días', 2, 'int']],
                    'filas' => array_map(fn($r) => ['#' . $r['id_pedidos'], $r['fecha_pedidos'], $r['cliente'], $r['empresa'], $r['entrega'], $r['dias']], $filas),
                ],
                'analisis' => ['Se mide desde la fecha del pedido hasta el momento en que se marcó como "Entregado". Solo entran pedidos entregados con ese registro.'],
            ];
        },
    ],

];