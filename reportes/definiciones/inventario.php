<?php

if (!defined('REPORTES_OK')) {
    http_response_code(403);
    exit;
}

$baseProd = "FROM productos p
             LEFT JOIN categorias c ON c.id_categoria = p.rela_id_categoria
             INNER JOIN marcas m ON m.id_marca = p.rela_id_marca";

$rankingVentas = function (mysqli $c, array $f, string $orden): array {
    $t = ''; $p = [];
    $w = rrango('pd.fecha_pedidos', $f, $t, $p) . rcategoria($f, $t, $p) . rmarca($f, $t, $p);
    return rq($c, "
        SELECT p.nombre AS producto, m.nombre_marca AS marca, p.stock, SUM(dp.cantidad) AS unidades, SUM(dp.subtotal_final) AS monto
        FROM detalle_pedidos dp
        INNER JOIN pedidos pd ON pd.id_pedidos = dp.rela_id_pedidos
        INNER JOIN productos p ON p.id_producto = dp.rela_id_productos
        LEFT JOIN categorias c ON c.id_categoria = p.rela_id_categoria
        INNER JOIN marcas m ON m.id_marca = p.rela_id_marca
        WHERE pd.estado <> 'Cancelado' $w
        GROUP BY p.id_producto, p.nombre, m.nombre_marca, p.stock
        ORDER BY unidades $orden, p.nombre ASC LIMIT " . (int) $f['top'], $t, $p);
};

return [

    'inventario_general' => [
        'seccion' => 'inventario',
        'titulo'  => 'Inventario general',
        'desc'    => 'Todos los productos con su stock, precio y valor.',
        'filtros' => ['categoria' => true, 'marca' => true,
            'estado' => ['etiqueta' => 'Stock', 'defecto' => 'todos',
                'opciones' => ['todos' => 'Todos', 'con' => 'Con stock', 'sin' => 'Sin stock']]],
        'generar' => function (mysqli $c, array $f) use ($baseProd): array {
            $t = ''; $p = [];
            $w = rcategoria($f, $t, $p) . rmarca($f, $t, $p);
            if ($f['estado'] === 'con') $w .= ' AND p.stock > 0';
            if ($f['estado'] === 'sin') $w .= ' AND p.stock <= 0';
            $filas = rq($c, "SELECT p.codigo, p.nombre AS producto, COALESCE(c.nombre,'Sin categoría') AS categoria, m.nombre_marca AS marca,
                                    p.stock, p.precio, p.stock * p.precio AS valor
                             $baseProd WHERE p.es_activo = 1 $w ORDER BY p.nombre ASC", $t, $p);
            $porCat = [];
            foreach ($filas as $r) $porCat[$r['categoria']] = ($porCat[$r['categoria']] ?? 0) + (int) $r['stock'];
            arsort($porCat);
            $sin = count(array_filter($filas, fn($r) => $r['stock'] <= 0));
            return [
                'kpis' => [['Productos', count($filas)], ['Unidades en stock', rnum(array_sum(array_column($filas, 'stock')))],
                           ['Valor a precio de venta', rplata(rtotal($filas, 'valor'))], ['Productos sin stock', $sin]],
                'graficos' => [['tipo' => 'barras', 'titulo' => 'Unidades en stock por categoría', 'datos' => $porCat]],
                'tabla' => [
                    'cols' => [['Código', 2, 'txt'], ['Producto', 5, 'txt'], ['Categoría', 3, 'txt'], ['Marca', 3, 'txt'], ['Stock', 2, 'int'], ['Precio', 3, 'plata'], ['Valor', 3, 'plata']],
                    'filas' => array_map(fn($r) => [$r['codigo'], $r['producto'], $r['categoria'], $r['marca'], $r['stock'], $r['precio'], $r['valor']], $filas),
                ],
                'analisis' => $filas ? [count($filas) . " productos activos, de los cuales $sin no tienen stock."] : [],
            ];
        },
    ],

    'stock_bajo' => [
        'seccion' => 'inventario',
        'titulo'  => 'Stock bajo y sin stock',
        'desc'    => 'Productos que están por agotarse o ya se agotaron.',
        'filtros' => ['categoria' => true, 'marca' => true, 'umbral' => ['defecto' => 5, 'etiqueta' => 'Stock menor o igual a']],
        'generar' => function (mysqli $c, array $f) use ($baseProd): array {
            $t = 'i'; $p = [$f['umbral']];
            $w = rcategoria($f, $t, $p) . rmarca($f, $t, $p);
            $filas = rq($c, "SELECT p.nombre AS producto, COALESCE(c.nombre,'Sin categoría') AS categoria, m.nombre_marca AS marca, p.stock,
                                    (SELECT MAX(pd.fecha_pedidos) FROM detalle_pedidos dp INNER JOIN pedidos pd ON pd.id_pedidos = dp.rela_id_pedidos
                                     WHERE dp.rela_id_productos = p.id_producto AND pd.estado <> 'Cancelado') AS ultima_venta
                             $baseProd WHERE p.es_activo = 1 AND p.stock <= ? $w ORDER BY p.stock ASC, p.nombre ASC", $t, $p);
            $sin = count(array_filter($filas, fn($r) => $r['stock'] <= 0));
            $graf = [];
            foreach (array_slice($filas, 0, 12) as $r) $graf[$r['producto']] = (float) $r['stock'];
            return [
                'subtitulo' => 'Stock menor o igual a ' . $f['umbral'],
                'kpis' => [['Productos con stock bajo', count($filas)], ['Sin stock', $sin], ['Límite elegido', $f['umbral'] . ' unidades']],
                'graficos' => $graf ? [['tipo' => 'barras', 'titulo' => 'Productos con menos stock', 'datos' => $graf]] : [],
                'tabla' => [
                    'cols' => [['Producto', 5, 'txt'], ['Categoría', 3, 'txt'], ['Marca', 3, 'txt'], ['Stock', 2, 'int'], ['Última venta', 3, 'fecha']],
                    'filas' => array_map(fn($r) => [$r['producto'], $r['categoria'], $r['marca'], $r['stock'], $r['ultima_venta']], $filas),
                ],
                'analisis' => $filas ? ["Hay " . count($filas) . " producto(s) con {$f['umbral']} unidades o menos; $sin ya no tienen stock y conviene reponerlos."] : ['Ningún producto está por debajo del límite elegido.'],
            ];
        },
    ],

    'valorizacion_inventario' => [
        'seccion' => 'inventario',
        'titulo'  => 'Valorización del inventario',
        'desc'    => 'Cuánto dinero hay invertido en mercadería (a costo) y cuánto valdría vendida.',
        'filtros' => ['categoria' => true, 'marca' => true],
        'generar' => function (mysqli $c, array $f) use ($baseProd): array {
            $t = ''; $p = [];
            $w = rcategoria($f, $t, $p) . rmarca($f, $t, $p);
            $filas = rq($c, "SELECT p.nombre AS producto, COALESCE(c.nombre,'Sin categoría') AS categoria, p.stock, p.precio,
                                    (SELECT cl.precio FROM " . rcompras_lineas() . " WHERE cl.prod = p.id_producto ORDER BY cl.fecha DESC LIMIT 1) AS costo
                             $baseProd WHERE p.es_activo = 1 AND p.stock > 0 $w ORDER BY p.nombre ASC", $t, $p);
            $venta = 0; $costo = 0; $ventaConCosto = 0; $sinCosto = 0; $porCat = [];
            foreach ($filas as &$r) {
                $r['v_venta'] = $r['stock'] * $r['precio'];
                $r['v_costo'] = $r['costo'] !== null ? $r['stock'] * $r['costo'] : null;
                $venta += $r['v_venta'];
                if ($r['v_costo'] === null) { $sinCosto++; } else { $costo += $r['v_costo']; $ventaConCosto += $r['v_venta']; }
                $porCat[$r['categoria']] = ($porCat[$r['categoria']] ?? 0) + $r['v_venta'];
            }
            unset($r);
            arsort($porCat);
            return [
                'kpis' => [['Valor a costo', rplata($costo)], ['Valor a precio de venta', rplata($venta)], ['Ganancia potencial', rplata($ventaConCosto - $costo)], ['Productos sin costo cargado', $sinCosto]],
                'graficos' => [['tipo' => 'torta', 'titulo' => 'Valor a precio de venta por categoría', 'datos' => $porCat, 'dinero' => true]],
                'tabla' => [
                    'cols' => [['Producto', 5, 'txt'], ['Stock', 2, 'int'], ['Costo unit.', 3, 'plata'], ['Precio venta', 3, 'plata'], ['Valor a costo', 3, 'plata'], ['Valor a venta', 3, 'plata']],
                    'filas' => array_map(fn($r) => [$r['producto'], $r['stock'], $r['costo'], $r['precio'], $r['v_costo'], $r['v_venta']], $filas),
                ],
                'analisis' => ['El costo es el precio de la última compra de cada producto; ' . ($sinCosto ? "$sinCosto producto(s) no tienen compras cargadas y no suman al valor a costo." : 'todos los productos tienen costo cargado.'),
                               'La ganancia potencial solo cuenta los productos con costo cargado. Es una estimación: no incluye descuentos ni impuestos.'],
            ];
        },
    ],

    'productos_mas_vendidos' => [
        'seccion' => 'inventario',
        'titulo'  => 'Productos más vendidos',
        'desc'    => 'Ranking de los productos que más unidades vendieron.',
        'filtros' => ['fechas' => true, 'categoria' => true, 'marca' => true, 'top' => true],
        'generar' => function (mysqli $c, array $f) use ($rankingVentas): array {
            $filas = $rankingVentas($c, $f, 'DESC');
            return [
                'kpis' => [['Unidades (ranking)', rnum(array_sum(array_column($filas, 'unidades')))], ['Dinero (ranking)', rplata(rtotal($filas, 'monto'))], ['Producto líder', $filas[0]['producto'] ?? '-']],
                'graficos' => [['tipo' => 'barras', 'titulo' => 'Unidades vendidas', 'datos' => rgraf($filas, 'producto', 'unidades')]],
                'tabla' => [
                    'cols' => [['#', 1, 'int'], ['Producto', 6, 'txt'], ['Marca', 3, 'txt'], ['Unidades', 2, 'int'], ['Monto', 3, 'plata'], ['Stock actual', 2, 'int']],
                    'filas' => array_map(fn($r, $i) => [$i + 1, $r['producto'], $r['marca'], $r['unidades'], $r['monto'], $r['stock']], $filas, array_keys($filas)),
                ],
                'analisis' => $filas ? ["{$filas[0]['producto']} es el más vendido con {$filas[0]['unidades']} unidades."] : [],
            ];
        },
    ],

    'productos_menos_vendidos' => [
        'seccion' => 'inventario',
        'titulo'  => 'Productos menos vendidos',
        'desc'    => 'Productos con ventas, ordenados de los que menos salen.',
        'filtros' => ['fechas' => true, 'categoria' => true, 'marca' => true, 'top' => true],
        'generar' => function (mysqli $c, array $f) use ($rankingVentas): array {
            $filas = $rankingVentas($c, $f, 'ASC');
            return [
                'kpis' => [['Unidades (ranking)', rnum(array_sum(array_column($filas, 'unidades')))], ['Producto con menos ventas', $filas[0]['producto'] ?? '-']],
                'graficos' => [['tipo' => 'barras', 'titulo' => 'Unidades vendidas', 'datos' => rgraf($filas, 'producto', 'unidades')]],
                'tabla' => [
                    'cols' => [['#', 1, 'int'], ['Producto', 6, 'txt'], ['Marca', 3, 'txt'], ['Unidades', 2, 'int'], ['Monto', 3, 'plata'], ['Stock actual', 2, 'int']],
                    'filas' => array_map(fn($r, $i) => [$i + 1, $r['producto'], $r['marca'], $r['unidades'], $r['monto'], $r['stock']], $filas, array_keys($filas)),
                ],
                'analisis' => ['Solo se listan productos que tuvieron al menos una venta; los que nunca se vendieron están en "Productos sin movimiento".'],
            ];
        },
    ],

    'productos_sin_movimiento' => [
        'seccion' => 'inventario',
        'titulo'  => 'Productos sin movimiento',
        'desc'    => 'Productos que no se vendieron en el período elegido.',
        'filtros' => ['fechas' => true, 'categoria' => true, 'marca' => true],
        'generar' => function (mysqli $c, array $f) use ($baseProd): array {
            $t = ''; $p = [];
            $sub = rrango('pd.fecha_pedidos', $f, $t, $p);
            $w = rcategoria($f, $t, $p) . rmarca($f, $t, $p);
            $filas = rq($c, "SELECT p.nombre AS producto, COALESCE(c.nombre,'Sin categoría') AS categoria, m.nombre_marca AS marca, p.stock, p.precio, p.stock * p.precio AS valor
                             $baseProd WHERE p.es_activo = 1 $w
                             AND NOT EXISTS (SELECT 1 FROM detalle_pedidos dp INNER JOIN pedidos pd ON pd.id_pedidos = dp.rela_id_pedidos
                                             WHERE dp.rela_id_productos = p.id_producto AND pd.estado <> 'Cancelado' $sub)
                             ORDER BY valor DESC, p.nombre ASC", $t, $p);
            $porCat = [];
            foreach ($filas as $r) $porCat[$r['categoria']] = ($porCat[$r['categoria']] ?? 0) + 1;
            arsort($porCat);
            return [
                'kpis' => [['Productos sin ventas', count($filas)], ['Dinero parado en stock', rplata(rtotal($filas, 'valor'))]],
                'graficos' => $porCat ? [['tipo' => 'barras', 'titulo' => 'Productos sin ventas por categoría', 'datos' => $porCat]] : [],
                'tabla' => [
                    'cols' => [['Producto', 5, 'txt'], ['Categoría', 3, 'txt'], ['Marca', 3, 'txt'], ['Stock', 2, 'int'], ['Precio', 3, 'plata'], ['Valor parado', 3, 'plata']],
                    'filas' => array_map(fn($r) => [$r['producto'], $r['categoria'], $r['marca'], $r['stock'], $r['precio'], $r['valor']], $filas),
                ],
                'analisis' => [count($filas) . ' producto(s) no tuvieron ventas en el período, con ' . rplata(rtotal($filas, 'valor')) . ' en mercadería parada (a precio de venta).'],
            ];
        },
    ],

    'productos_descontinuados' => [
        'seccion' => 'inventario',
        'titulo'  => 'Productos descontinuados',
        'desc'    => 'Productos que ya no se fabrican o venden, y el stock que quedó.',
        'filtros' => ['categoria' => true, 'marca' => true],
        'generar' => function (mysqli $c, array $f) use ($baseProd): array {
            $t = ''; $p = [];
            $w = rcategoria($f, $t, $p) . rmarca($f, $t, $p);
            $filas = rq($c, "SELECT p.nombre AS producto, COALESCE(c.nombre,'Sin categoría') AS categoria, m.nombre_marca AS marca, p.stock, p.precio, p.stock * p.precio AS valor
                             $baseProd WHERE p.es_descontinuado = 1 $w ORDER BY p.nombre ASC", $t, $p);
            return [
                'kpis' => [['Productos descontinuados', count($filas)], ['Unidades que quedan', rnum(array_sum(array_column($filas, 'stock')))], ['Valor del stock', rplata(rtotal($filas, 'valor'))]],
                'graficos' => [],
                'tabla' => [
                    'cols' => [['Producto', 5, 'txt'], ['Categoría', 3, 'txt'], ['Marca', 3, 'txt'], ['Stock', 2, 'int'], ['Precio', 3, 'plata'], ['Valor', 3, 'plata']],
                    'filas' => array_map(fn($r) => [$r['producto'], $r['categoria'], $r['marca'], $r['stock'], $r['precio'], $r['valor']], $filas),
                ],
                'analisis' => [$filas ? 'Quedan ' . rnum(array_sum(array_column($filas, 'stock'))) . ' unidades de productos descontinuados.' : 'No hay productos descontinuados.'],
            ];
        },
    ],

    'inventario_empleado' => [
        'seccion' => 'inventario',
        'titulo'  => 'Cambios de inventario por empleado',
        'desc'    => 'Quién cargó cada movimiento de stock: facturas, ajustes y devoluciones.',
        'solo_admin' => true,
        'filtros' => ['fechas' => true, 'concepto' => true],
        'generar' => function (mysqli $c, array $f): array {
            $col = $c->query("SHOW COLUMNS FROM historial_movimientos LIKE 'rela_id_usuario'");
            if (!$col || $col->num_rows === 0) {
                return ['kpis' => [], 'graficos' => [], 'tabla' => ['cols' => [['Aviso', 10, 'txt']], 'filas' => [['Falta ejecutar el script de la base de datos (columna "rela_id_usuario" en historial_movimientos).']]], 'analisis' => []];
            }
            $t = ''; $p = [];
            $w = rrango('h.fecha_movimiento', $f, $t, $p);
            if ($f['concepto']) { $w .= ' AND h.rela_id_conceptos = ?'; $t .= 'i'; $p[] = $f['concepto']; }
            $filas = rq($c, "SELECT COALESCE(u.nombre, 'Sistema / tienda online') AS persona, COALESCE(pf.nombre_perfil, '') AS perfil,
                                    COUNT(*) AS movimientos,
                                    SUM(CASE WHEN h.cantidad > 0 THEN h.cantidad ELSE 0 END) AS entradas,
                                    SUM(CASE WHEN h.cantidad < 0 THEN -h.cantidad ELSE 0 END) AS salidas,
                                    MAX(h.fecha_movimiento) AS ultimo
                             FROM historial_movimientos h
                             LEFT JOIN usuarios u ON u.id_usuario = h.rela_id_usuario
                             LEFT JOIN perfiles pf ON pf.id_perfil = u.rela_id_perfil
                             WHERE 1=1 $w GROUP BY u.id_usuario, u.nombre, pf.nombre_perfil ORDER BY movimientos DESC", $t, $p);
            $n = (int) rtotal($filas, 'movimientos');
            return [
                'kpis' => [['Movimientos', $n], ['Unidades que entraron', rnum(rtotal($filas, 'entradas'))], ['Unidades que salieron', rnum(rtotal($filas, 'salidas'))], ['Persona más activa', $filas[0]['persona'] ?? '-']],
                'graficos' => [['tipo' => 'barras', 'titulo' => 'Movimientos de stock por persona', 'datos' => rgraf($filas, 'persona', 'movimientos')]],
                'tabla' => [
                    'cols' => [['Persona', 5, 'txt'], ['Perfil', 3, 'txt'], ['Movimientos', 3, 'int'], ['Entradas', 2, 'int'], ['Salidas', 2, 'int'], ['Último', 3, 'fecha']],
                    'filas' => array_map(fn($r) => [$r['persona'], ucfirst($r['perfil']), $r['movimientos'], $r['entradas'], $r['salidas'], $r['ultimo']], $filas),
                ],
                'analisis' => ['"Sistema / tienda online" agrupa las ventas web, las cancelaciones y los movimientos anteriores a esta mejora, que no guardaban quién los hizo.',
                               'Las facturas de compra y venta cargadas antes de la mejora se completaron con el usuario que las registró.'],
            ];
        },
    ],

    'movimientos_inventario' => [
        'seccion' => 'inventario',
        'titulo'  => 'Movimientos de inventario',
        'desc'    => 'Entradas, salidas y ajustes de stock con su motivo.',
        'filtros' => ['fechas' => true, 'concepto' => true],
        'generar' => function (mysqli $c, array $f): array {
            $t = ''; $p = [];
            $w = rrango('h.fecha_movimiento', $f, $t, $p);
            if ($f['concepto']) { $w .= ' AND h.rela_id_conceptos = ?'; $t .= 'i'; $p[] = $f['concepto']; }
            $filas = rq($c, "SELECT h.fecha_movimiento AS fecha, p.nombre AS producto, cm.descripcion AS concepto, cm.tipo_movimiento AS tipo, h.cantidad, h.referencia_ext AS ref, h.comentario
                             FROM historial_movimientos h
                             INNER JOIN productos p ON p.id_producto = h.rela_id_productos
                             INNER JOIN conceptos_movimiento cm ON cm.id_concepto = h.rela_id_conceptos
                             WHERE 1=1 $w ORDER BY h.fecha_movimiento DESC, h.id_movimientos DESC", $t, $p);
            $porC = [];
            $ent = 0; $sal = 0;
            foreach ($filas as $r) {
                $porC[$r['concepto']] = ($porC[$r['concepto']] ?? 0) + 1;
                if ($r['tipo'] === 'E') $ent += abs($r['cantidad']);
                if ($r['tipo'] === 'S') $sal += abs($r['cantidad']);
            }
            return [
                'kpis' => [['Movimientos', count($filas)], ['Unidades que entraron', rnum($ent)], ['Unidades que salieron', rnum($sal)]],
                'graficos' => $porC ? [['tipo' => 'torta', 'titulo' => 'Movimientos por motivo', 'datos' => $porC]] : [],
                'tabla' => [
                    'cols' => [['Fecha', 3, 'fecha'], ['Producto', 5, 'txt'], ['Motivo', 4, 'txt'], ['Cantidad', 2, 'int'], ['Referencia', 3, 'txt'], ['Comentario', 4, 'txt']],
                    'filas' => array_map(fn($r) => [$r['fecha'], $r['producto'], $r['concepto'], $r['cantidad'], $r['ref'], $r['comentario']], $filas),
                ],
                'analisis' => [count($filas) . " movimientos registrados: entraron " . rnum($ent) . " unidades y salieron " . rnum($sal) . "."],
            ];
        },
    ],

];