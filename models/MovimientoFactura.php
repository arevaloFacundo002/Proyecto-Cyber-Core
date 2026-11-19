<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../includes/avisos.php';

class MovimientoFactura
{
    public const MAX_LINEAS   = 100;
    public const MAX_CANTIDAD = 1000000;

    private mysqli $conexion;

    public function __construct()
    {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    public function proveedores(): array
    {
        $r = $this->conexion->query("SELECT id_proveedores, nombre_apellido FROM proveedores WHERE es_activo = 1 ORDER BY nombre_apellido");
        return $r->fetch_all(MYSQLI_ASSOC);
    }

    public function productos(): array
    {
        $r = $this->conexion->query(
            "SELECT id_producto, codigo, nombre, precio, stock
             FROM productos
             WHERE es_activo = 1 AND COALESCE(es_descontinuado, 0) = 0
             ORDER BY nombre"
        );
        return $r->fetch_all(MYSQLI_ASSOC);
    }

    private function concepto(string $tipoFactura): ?int
    {
        $tipoMov = $tipoFactura === 'Compra' ? 'E' : 'S';
        $desc    = $tipoFactura === 'Compra' ? 'Compra de proveedor' : 'Venta a cliente';

        $st = $this->conexion->prepare(
            "SELECT id_concepto FROM conceptos_movimiento
             WHERE tipo_movimiento = ? AND descripcion = ? AND es_activo = 1 LIMIT 1"
        );
        $st->bind_param("ss", $tipoMov, $desc);
        $st->execute();
        $f = $st->get_result()->fetch_assoc();
        return $f ? (int) $f['id_concepto'] : null;
    }

    // Valida lo que llega del formulario y arma las líneas (sumando los productos repetidos).
    public function validar(array $d): array
    {
        $tipo   = $d['tipo'] ?? '';
        $fecha  = trim((string) ($d['fecha'] ?? ''));
        $numero = mb_substr(trim((string) ($d['numero_comprobante'] ?? '')), 0, 50);
        $contra = mb_substr(trim((string) ($d['contraparte'] ?? '')), 0, 150);
        $coment = mb_substr(trim((string) ($d['comentario'] ?? '')), 0, 255);
        $id_prov = (int) ($d['id_proveedor'] ?? 0);

        if (!in_array($tipo, ['Compra', 'Venta'], true)) {
            return ['error' => 'Elegí si la factura es de compra o de venta.'];
        }

        $t = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$t || $t->format('Y-m-d') !== $fecha) {
            return ['error' => 'La fecha de la factura no es válida.'];
        }
        if ($fecha > date('Y-m-d')) {
            return ['error' => 'La fecha de la factura no puede ser futura.'];
        }

        if ($tipo === 'Compra') {
            if ($id_prov <= 0) {
                return ['error' => 'Elegí el proveedor de la compra.'];
            }
            $st = $this->conexion->prepare("SELECT 1 FROM proveedores WHERE id_proveedores = ? AND es_activo = 1");
            $st->bind_param("i", $id_prov);
            $st->execute();
            if ($st->get_result()->num_rows === 0) {
                return ['error' => 'El proveedor elegido no existe o está inactivo.'];
            }
            if ($numero === '') {
                return ['error' => 'Escribí el número de la factura del proveedor.'];
            }
        } else {
            $id_prov = 0;
        }

        $ids = (array) ($d['producto'] ?? []);
        $cants = (array) ($d['cantidad'] ?? []);
        $precios = (array) ($d['precio'] ?? []);

        $lineas = [];
        foreach ($ids as $i => $idp) {
            $idp = (int) $idp;
            $cantTxt = trim((string) ($cants[$i] ?? ''));
            $precioTxt = trim((string) ($precios[$i] ?? ''));

            if ($idp <= 0 && $cantTxt === '') {
                continue;
            }
            if ($idp <= 0) {
                return ['error' => 'Hay una fila con cantidad pero sin producto.'];
            }
            if (!ctype_digit($cantTxt) || (int) $cantTxt < 1 || (int) $cantTxt > self::MAX_CANTIDAD) {
                return ['error' => 'La cantidad tiene que ser un número entero mayor a cero.'];
            }
            $precio = (float) str_replace(',', '.', $precioTxt === '' ? '0' : $precioTxt);
            if ($precio < 0 || $precio > 100000000) {
                return ['error' => 'El precio de una fila no es válido.'];
            }

            if (isset($lineas[$idp])) {
                $lineas[$idp]['cantidad'] += (int) $cantTxt;
                $lineas[$idp]['precio']    = $precio;
            } else {
                $lineas[$idp] = ['id' => $idp, 'cantidad' => (int) $cantTxt, 'precio' => $precio];
            }
        }

        if (!$lineas) {
            return ['error' => 'Agregá al menos un producto a la factura.'];
        }
        if (count($lineas) > self::MAX_LINEAS) {
            return ['error' => 'Una factura puede tener hasta ' . self::MAX_LINEAS . ' productos distintos.'];
        }

        foreach ($lineas as &$l) {
            if ($l['cantidad'] > self::MAX_CANTIDAD) {
                return ['error' => 'La cantidad total de un producto es demasiado alta.'];
            }
        }
        unset($l);

        ksort($lineas);

        return ['ok' => true, 'tipo' => $tipo, 'fecha' => $fecha, 'numero' => $numero, 'contraparte' => $contra,
                'comentario' => $coment, 'id_proveedor' => $id_prov, 'lineas' => array_values($lineas)];
    }

    // Guarda la factura completa como UN solo movimiento y actualiza el stock de todos los productos en la misma transacción.
    public function crear(array $v, int $id_usuario): array
    {
        $concepto = $this->concepto($v['tipo']);
        if (!$concepto) {
            return ['ok' => false, 'error' => 'Falta el concepto de movimiento "' . ($v['tipo'] === 'Compra' ? 'Compra de proveedor' : 'Venta a cliente') . '".'];
        }

        try {
            $this->conexion->begin_transaction();

            if ($v['numero'] !== '') {
                if ($v['tipo'] === 'Compra') {
                    $st = $this->conexion->prepare("SELECT id_movimiento_factura FROM movimiento_factura WHERE tipo = 'Compra' AND rela_id_proveedores = ? AND numero_comprobante = ? LIMIT 1");
                    $st->bind_param("is", $v['id_proveedor'], $v['numero']);
                } else {
                    $st = $this->conexion->prepare("SELECT id_movimiento_factura FROM movimiento_factura WHERE tipo = 'Venta' AND numero_comprobante = ? LIMIT 1");
                    $st->bind_param("s", $v['numero']);
                }
                $st->execute();
                if ($st->get_result()->num_rows > 0) {
                    throw new RuntimeException('Esa factura ya fue cargada.');
                }
            }

            $stProd = $this->conexion->prepare(
                "SELECT id_producto, nombre, stock FROM productos
                 WHERE id_producto = ? AND COALESCE(es_descontinuado, 0) = 0 FOR UPDATE"
            );

            $total = 0.0;
            $filas = [];
            $faltantes = [];

            foreach ($v['lineas'] as $l) {
                $stProd->bind_param("i", $l['id']);
                $stProd->execute();
                $p = $stProd->get_result()->fetch_assoc();
                if (!$p) {
                    throw new RuntimeException('Uno de los productos ya no existe o está descontinuado.');
                }
                if ($v['tipo'] === 'Venta' && (int) $p['stock'] < $l['cantidad']) {
                    $faltantes[] = $p['nombre'] . ' (hay ' . (int) $p['stock'] . ', pediste ' . $l['cantidad'] . ')';
                }
                $sub = round($l['cantidad'] * $l['precio'], 2);
                $total += $sub;
                $filas[] = ['id' => (int) $p['id_producto'], 'cantidad' => $l['cantidad'], 'precio' => $l['precio'], 'subtotal' => $sub];
            }

            if ($faltantes) {
                throw new RuntimeException('No hay stock suficiente: ' . implode('; ', $faltantes) . '.');
            }

            $total = round($total, 2);
            $prov  = $v['id_proveedor'] > 0 ? $v['id_proveedor'] : null;
            $num   = $v['numero'] !== '' ? $v['numero'] : null;
            $contra = $v['contraparte'] !== '' ? $v['contraparte'] : null;
            $coment = $v['comentario'] !== '' ? $v['comentario'] : null;

            $st = $this->conexion->prepare(
                "INSERT INTO movimiento_factura
                    (tipo, fecha, numero_comprobante, rela_id_proveedores, contraparte, total, comentario, rela_id_usuario, fecha_registro)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $st->bind_param("sssisdsi", $v['tipo'], $v['fecha'], $num, $prov, $contra, $total, $coment, $id_usuario);
            $st->execute();
            $id = (int) $this->conexion->insert_id;

            $stItem = $this->conexion->prepare(
                "INSERT INTO movimiento_factura_items (rela_id_movimiento_factura, rela_id_productos, cantidad, precio_unitario, subtotal)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $signo = $v['tipo'] === 'Compra' ? 1 : -1;
            $stStock = $this->conexion->prepare("UPDATE productos SET stock = stock + ? WHERE id_producto = ?");
            $stHist = $this->conexion->prepare(
                "INSERT INTO historial_movimientos (fecha_movimiento, cantidad, referencia_ext, comentario, rela_id_productos, rela_id_conceptos)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $ref  = 'FACT-' . $id;
            $comH = 'Factura de ' . strtolower($v['tipo']) . ($num ? ' ' . $num : '');
            $comH = mb_substr($comH, 0, 200);

            foreach ($filas as $f) {
                $stItem->bind_param("iiidd", $id, $f['id'], $f['cantidad'], $f['precio'], $f['subtotal']);
                $stItem->execute();

                $mov = $signo * $f['cantidad'];
                $stStock->bind_param("ii", $mov, $f['id']);
                $stStock->execute();

                $stHist->bind_param("sissii", $v['fecha'], $mov, $ref, $comH, $f['id'], $concepto);
                $stHist->execute();

                try {
                    $uMov = (int) ($_SESSION['id_usuario'] ?? 0);
                    if ($uMov > 0) {
                        $idMov = (int) $this->conexion->insert_id;
                        $qMov = $this->conexion->prepare("UPDATE historial_movimientos SET rela_id_usuario = ? WHERE id_movimientos = ?");
                        $qMov->bind_param("ii", $uMov, $idMov);
                        $qMov->execute();
                    }
                } catch (\Throwable $e) {
                    // la columna rela_id_usuario todavía no existe: no se interrumpe el movimiento
                }
            }

            $this->conexion->commit();
        } catch (RuntimeException $e) {
            $this->conexion->rollback();
            return ['ok' => false, 'error' => $e->getMessage()];
        } catch (Throwable $e) {
            $this->conexion->rollback();
            error_log('MovimientoFactura::crear: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'No se pudo guardar la factura. No se modificó el stock.'];
        }

        if ($v['tipo'] === 'Venta') {
            avisar_stock_bajo($this->conexion, array_column($filas, 'id'));
        }

        return ['ok' => true, 'id' => $id, 'total' => $total, 'lineas' => count($filas)];
    }

    public function listar(string $tipo = '', string $desde = '', string $hasta = '', string $buscar = ''): array
    {
        $where = [];
        $tipos = '';
        $par   = [];

        if (in_array($tipo, ['Compra', 'Venta'], true)) { $where[] = 'mf.tipo = ?'; $tipos .= 's'; $par[] = $tipo; }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) { $where[] = 'mf.fecha >= ?'; $tipos .= 's'; $par[] = $desde; }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) { $where[] = 'mf.fecha <= ?'; $tipos .= 's'; $par[] = $hasta; }
        if ($buscar !== '') {
            $where[] = '(mf.numero_comprobante LIKE ? OR pr.nombre_apellido LIKE ? OR mf.contraparte LIKE ?)';
            $tipos .= 'sss';
            $like = '%' . $buscar . '%';
            array_push($par, $like, $like, $like);
        }

        $sql = "SELECT mf.id_movimiento_factura, mf.tipo, mf.fecha, mf.numero_comprobante, mf.total, mf.contraparte,
                       pr.nombre_apellido AS proveedor, u.nombre AS usuario,
                       (SELECT COUNT(*) FROM movimiento_factura_items i WHERE i.rela_id_movimiento_factura = mf.id_movimiento_factura) AS lineas,
                       (SELECT COALESCE(SUM(i.cantidad), 0) FROM movimiento_factura_items i WHERE i.rela_id_movimiento_factura = mf.id_movimiento_factura) AS unidades
                FROM movimiento_factura mf
                LEFT JOIN proveedores pr ON pr.id_proveedores = mf.rela_id_proveedores
                LEFT JOIN usuarios u ON u.id_usuario = mf.rela_id_usuario"
             . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
             . " ORDER BY mf.id_movimiento_factura DESC LIMIT 300";

        $st = $this->conexion->prepare($sql);
        if ($par) {
            $st->bind_param($tipos, ...$par);
        }
        $st->execute();
        return $st->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function obtener(int $id): ?array
    {
        $st = $this->conexion->prepare(
            "SELECT mf.*, pr.nombre_apellido AS proveedor, u.nombre AS usuario
             FROM movimiento_factura mf
             LEFT JOIN proveedores pr ON pr.id_proveedores = mf.rela_id_proveedores
             LEFT JOIN usuarios u ON u.id_usuario = mf.rela_id_usuario
             WHERE mf.id_movimiento_factura = ?"
        );
        $st->bind_param("i", $id);
        $st->execute();
        $f = $st->get_result()->fetch_assoc();
        if (!$f) {
            return null;
        }

        $st = $this->conexion->prepare(
            "SELECT i.cantidad, i.precio_unitario, i.subtotal, p.codigo, p.nombre
             FROM movimiento_factura_items i
             INNER JOIN productos p ON p.id_producto = i.rela_id_productos
             WHERE i.rela_id_movimiento_factura = ? ORDER BY i.id_item"
        );
        $st->bind_param("i", $id);
        $st->execute();
        $f['items'] = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        return $f;
    }
}