<?php

if (!defined('REPORTES_OK')) {
    http_response_code(403);
    exit;
}

return [

    'actividad_personal' => [
        'seccion' => 'personal',
        'titulo'  => 'Actividad del personal',
        'desc'    => 'Qué hizo cada persona del equipo: cambios de estado de pedidos, facturas de inventario y movimientos de caja.',
        'solo_admin' => true,
        'filtros' => ['fechas' => true],
        'generar' => function (mysqli $c, array $f): array {
            $gente = [];
            $sumar = function (string $nombre, string $campo, int $n) use (&$gente) {
                $nombre = trim($nombre) !== '' ? trim($nombre) : 'Sin identificar';
                $gente[$nombre] = $gente[$nombre] ?? ['pedidos' => 0, 'facturas' => 0, 'caja' => 0];
                $gente[$nombre][$campo] += $n;
            };

            $t = ''; $p = [];
            $w = rrango('DATE(h.fecha_evento)', $f, $t, $p);
            foreach (rq($c, "SELECT h.autor AS n, COUNT(*) AS q FROM pedido_historial h WHERE 1=1 $w GROUP BY h.autor", $t, $p) as $r) $sumar((string) $r['n'], 'pedidos', (int) $r['q']);

            $t = ''; $p = [];
            $w = rrango('mf.fecha', $f, $t, $p);
            foreach (rq($c, "SELECT u.nombre AS n, COUNT(*) AS q FROM movimiento_factura mf LEFT JOIN usuarios u ON u.id_usuario = mf.rela_id_usuario WHERE 1=1 $w GROUP BY u.nombre", $t, $p) as $r) $sumar((string) $r['n'], 'facturas', (int) $r['q']);

            $t = ''; $p = [];
            $w = rrango('DATE(cm.fecha)', $f, $t, $p);
            foreach (rq($c, "SELECT u.nombre AS n, COUNT(*) AS q FROM caja_movimientos cm LEFT JOIN usuarios u ON u.id_usuario = cm.rela_id_usuario WHERE 1=1 $w GROUP BY u.nombre", $t, $p) as $r) $sumar((string) $r['n'], 'caja', (int) $r['q']);

            $filas = [];
            foreach ($gente as $n => $v) $filas[] = ['persona' => $n, 'pedidos' => $v['pedidos'], 'facturas' => $v['facturas'], 'caja' => $v['caja'], 'total' => array_sum($v)];
            usort($filas, fn($a, $b) => $b['total'] <=> $a['total']);
            return [
                'kpis' => [['Personas con actividad', count($filas)], ['Acciones registradas', (int) rtotal($filas, 'total')], ['Más activa', $filas[0]['persona'] ?? '-']],
                'graficos' => [['tipo' => 'barras', 'titulo' => 'Acciones registradas por persona', 'datos' => rgraf($filas, 'persona', 'total')]],
                'tabla' => [
                    'cols' => [['Persona', 5, 'txt'], ['Cambios en pedidos', 3, 'int'], ['Facturas de inventario', 3, 'int'], ['Movimientos de caja', 3, 'int'], ['Total', 2, 'int']],
                    'filas' => array_map(fn($r) => [$r['persona'], $r['pedidos'], $r['facturas'], $r['caja'], $r['total']], $filas),
                ],
                'analisis' => ['Cuenta lo que quedó registrado en el sistema. Las ventas por empleado están en la sección Ventas.'],
            ];
        },
    ],

];