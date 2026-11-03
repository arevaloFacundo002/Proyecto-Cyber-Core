<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../includes/avisos.php';

class Devolucion
{
    public const PLAZO_DIAS = 30;

    public const ESTADOS = [
        'Pendiente' => ['emoji' => '🟡', 'color' => '#facc15'],
        'Aprobada'  => ['emoji' => '🟢', 'color' => '#22c55e'],
        'Rechazada' => ['emoji' => '🔴', 'color' => '#ef4444'],
    ];

    private mysqli $conexion;

    public function __construct()
    {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    public static function estaAbierta(?string $estado): bool
    {
        return !in_array($estado, ['Aprobada', 'Rechazada'], true);
    }

    public static function infoEstado(?string $estado): array
    {
        return self::ESTADOS[self::estaAbierta($estado) ? 'Pendiente' : $estado];
    }

    private function fechaEntrega(int $id_pedido, string $fecha_pedido): string
    {
        $fecha = null;

        try {
            $st = $this->conexion->prepare(
                "SELECT MIN(fecha_evento) FROM pedido_historial WHERE rela_id_pedidos = ? AND estado = 'Entregado'"
            );
            $st->bind_param("i", $id_pedido);
            $st->execute();
            $fecha = $st->get_result()->fetch_row()[0] ?? null;
        } catch (Throwable $e) {
            $fecha = null;
        }

        return $fecha ?: $fecha_pedido;
    }

    public function lineasDevolvibles(int $id_pedido): array
    {
        $st = $this->conexion->prepare(
            "SELECT dp.id_detalle_pedidos, dp.rela_id_productos AS id_producto, dp.cantidad, dp.subtotal_final,
                    pr.nombre, pr.codigo,
                    COALESCE((SELECT SUM(di.cantidad)
                              FROM devolucion_items di
                              INNER JOIN devolucion d ON d.id_devolucion = di.rela_id_devolucion
                              WHERE di.rela_id_detalle_pedidos = dp.id_detalle_pedidos
                                AND (d.estado IS NULL OR d.estado <> 'Rechazada')), 0) AS ya_devuelta
             FROM detalle_pedidos dp
             INNER JOIN productos pr ON pr.id_producto = dp.rela_id_productos
             WHERE dp.rela_id_pedidos = ?
             ORDER BY dp.id_detalle_pedidos"
        );
        $st->bind_param("i", $id_pedido);
        $st->execute();

        $lineas = [];
        foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $l) {
            $cant = max(1, (int) $l['cantidad']);
            $l['cantidad']   = (int) $l['cantidad'];
            $l['ya_devuelta'] = (int) $l['ya_devuelta'];
            $l['disponible'] = max(0, $l['cantidad'] - $l['ya_devuelta']);
            $l['precio_unit'] = round((float) $l['subtotal_final'] / $cant, 2);
            $lineas[] = $l;
        }
        return $lineas;
    }

    public function puedeSolicitar(int $id_pedido, int $id_usuario): array
    {
        $st = $this->conexion->prepare(
            "SELECT p.estado, p.fecha_pedidos
             FROM pedidos p
             INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
             WHERE p.id_pedidos = ? AND c.rela_id_usuario = ?"
        );
        $st->bind_param("ii", $id_pedido, $id_usuario);
        $st->execute();
        $p = $st->get_result()->fetch_assoc();

        if (!$p) {
            return ['ok' => false, 'error' => 'El pedido no existe.'];
        }
        if ($p['estado'] !== 'Entregado') {
            return ['ok' => false, 'error' => 'Solo se pueden devolver productos de pedidos entregados.'];
        }

        $entrega = $this->fechaEntrega($id_pedido, $p['fecha_pedidos']);
        $dias    = (int) floor((time() - strtotime($entrega)) / 86400);
        if ($dias > self::PLAZO_DIAS) {
            return ['ok' => false, 'error' => 'Pasó el plazo de ' . self::PLAZO_DIAS . ' días para pedir una devolución.'];
        }

        $hay = false;
        foreach ($this->lineasDevolvibles($id_pedido) as $l) {
            if ($l['disponible'] > 0) {
                $hay = true;
                break;
            }
        }
        if (!$hay) {
            return ['ok' => false, 'error' => 'Todos los productos de este pedido ya tienen una devolución pedida.'];
        }

        $limite = date('d/m/Y', strtotime($entrega . ' +' . self::PLAZO_DIAS . ' days'));
        return ['ok' => true, 'limite' => $limite];
    }

    public function solicitar(int $id_pedido, int $id_usuario, string $motivo, array $cantidades): array
    {
        $motivo = trim($motivo);
        if ($motivo === '') {
            return ['ok' => false, 'error' => 'Contanos el motivo de la devolución.'];
        }
        $motivo = mb_substr($motivo, 0, 1000);

        try {
            $this->conexion->begin_transaction();

            $st = $this->conexion->prepare(
                "SELECT p.id_pedidos FROM pedidos p
                 INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
                 WHERE p.id_pedidos = ? AND c.rela_id_usuario = ? FOR UPDATE"
            );
            $st->bind_param("ii", $id_pedido, $id_usuario);
            $st->execute();
            if (!$st->get_result()->fetch_assoc()) {
                throw new Exception('El pedido no existe.');
            }

            $puede = $this->puedeSolicitar($id_pedido, $id_usuario);
            if (!$puede['ok']) {
                throw new Exception($puede['error']);
            }

            $items = [];
            $total = 0.0;
            foreach ($this->lineasDevolvibles($id_pedido) as $l) {
                $pedida = (int) ($cantidades[$l['id_detalle_pedidos']] ?? 0);
                if ($pedida <= 0) {
                    continue;
                }
                if ($pedida > $l['disponible']) {
                    throw new Exception('Pediste devolver más unidades de ' . $l['nombre'] . ' de las que podés.');
                }
                $monto = round($l['precio_unit'] * $pedida, 2);
                $items[] = [$l['id_detalle_pedidos'], $l['id_producto'], $pedida, $monto];
                $total += $monto;
            }
            if (!$items) {
                throw new Exception('Elegí al menos un producto para devolver.');
            }

            $st = $this->conexion->prepare(
                "INSERT INTO devolucion (motivo, estado, monto_reembolso, fecha_solicitud, rela_id_pedidos)
                 VALUES (?, 'Pendiente', ?, CURDATE(), ?)"
            );
            $st->bind_param("sdi", $motivo, $total, $id_pedido);
            $st->execute();
            $id_devolucion = (int) $this->conexion->insert_id;

            $st = $this->conexion->prepare(
                "INSERT INTO devolucion_items (cantidad, monto, rela_id_devolucion, rela_id_detalle_pedidos, rela_id_productos)
                 VALUES (?, ?, ?, ?, ?)"
            );
            foreach ($items as [$id_det, $id_prod, $cant, $monto]) {
                $st->bind_param("idiii", $cant, $monto, $id_devolucion, $id_det, $id_prod);
                $st->execute();
            }

            $this->conexion->commit();
        } catch (Throwable $e) {
            $this->conexion->rollback();
            return ['ok' => false, 'error' => $e->getMessage() ?: 'No se pudo registrar la devolución.'];
        }

        avisar_devolucion_solicitada($this->conexion, $id_devolucion);

        return ['ok' => true, 'id_devolucion' => $id_devolucion, 'monto' => $total];
    }

    public function itemsDeDevolucion(int $id_devolucion): array
    {
        $st = $this->conexion->prepare(
            "SELECT di.cantidad, di.monto, di.rela_id_productos AS id_producto, pr.nombre, pr.codigo
             FROM devolucion_items di
             INNER JOIN productos pr ON pr.id_producto = di.rela_id_productos
             WHERE di.rela_id_devolucion = ?
             ORDER BY di.id_item"
        );
        $st->bind_param("i", $id_devolucion);
        $st->execute();
        return $st->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function deDelPedido(int $id_pedido): array
    {
        $st = $this->conexion->prepare(
            "SELECT id_devolucion, motivo, estado, monto_reembolso, fecha_solicitud, fecha_resolucion, nota_admin
             FROM devolucion WHERE rela_id_pedidos = ? ORDER BY id_devolucion DESC"
        );
        $st->bind_param("i", $id_pedido);
        $st->execute();

        $lista = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach ($lista as &$d) {
            $d['items'] = $this->itemsDeDevolucion((int) $d['id_devolucion']);
        }
        return $lista;
    }

    public function listarAdmin(string $estado = 'todos', string $buscar = ''): array
    {
        $sql = "SELECT d.id_devolucion, d.estado, d.monto_reembolso, d.fecha_solicitud, d.rela_id_pedidos,
                       c.nombre, c.apellido
                FROM devolucion d
                INNER JOIN pedidos p ON p.id_pedidos = d.rela_id_pedidos
                INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
                WHERE 1=1";
        $tipos = '';
        $vals  = [];

        if ($estado === 'Pendiente') {
            $sql .= " AND (d.estado IS NULL OR d.estado NOT IN ('Aprobada','Rechazada'))";
        } elseif ($estado === 'Aprobada' || $estado === 'Rechazada') {
            $sql .= " AND d.estado = ?";
            $tipos .= 's';
            $vals[] = $estado;
        }

        if ($buscar !== '') {
            $sql .= " AND (d.id_devolucion = ? OR d.rela_id_pedidos = ? OR CONCAT(c.nombre, ' ', c.apellido) LIKE ?)";
            $id = (int) preg_replace('/\D/', '', $buscar);
            $tipos .= 'iis';
            array_push($vals, $id, $id, '%' . $buscar . '%');
        }

        $sql .= " ORDER BY d.id_devolucion DESC";
        $st = $this->conexion->prepare($sql);
        if ($vals) {
            $st->bind_param($tipos, ...$vals);
        }
        $st->execute();
        return $st->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function contarPorEstado(): array
    {
        $r = ['Pendiente' => 0, 'Aprobada' => 0, 'Rechazada' => 0];
        $res = $this->conexion->query("SELECT estado, COUNT(*) AS n FROM devolucion GROUP BY estado");
        foreach ($res->fetch_all(MYSQLI_ASSOC) as $f) {
            $r[self::estaAbierta($f['estado']) ? 'Pendiente' : $f['estado']] += (int) $f['n'];
        }
        return $r;
    }

    public function obtenerAdmin(int $id_devolucion): ?array
    {
        $st = $this->conexion->prepare(
            "SELECT d.*, p.estado AS estado_pedido, p.monto_total, p.fecha_pedidos,
                    c.nombre, c.apellido, c.rela_id_usuario AS id_usuario
             FROM devolucion d
             INNER JOIN pedidos p ON p.id_pedidos = d.rela_id_pedidos
             INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
             WHERE d.id_devolucion = ?"
        );
        $st->bind_param("i", $id_devolucion);
        $st->execute();
        $d = $st->get_result()->fetch_assoc();
        if (!$d) {
            return null;
        }
        $d['items'] = $this->itemsDeDevolucion($id_devolucion);
        return $d;
    }

    private function idConceptoDevolucion(): int
    {
        $res = $this->conexion->query(
            "SELECT id_concepto FROM conceptos_movimiento
             WHERE tipo_movimiento = 'E' AND es_activo = 1 AND descripcion LIKE 'Devoluci%cliente%'
             ORDER BY id_concepto LIMIT 1"
        );
        if ($c = $res->fetch_assoc()) {
            return (int) $c['id_concepto'];
        }
        $this->conexion->query(
            "INSERT INTO conceptos_movimiento (descripcion, tipo_movimiento, es_activo)
             VALUES ('Devolucion de cliente', 'E', 1)"
        );
        return (int) $this->conexion->insert_id;
    }

    private function tablaExiste(string $tabla): bool
    {
        $st = $this->conexion->prepare(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?"
        );
        $st->bind_param("s", $tabla);
        $st->execute();
        return (int) $st->get_result()->fetch_row()[0] > 0;
    }

    public function aprobar(int $id_devolucion, string $autor, ?string $nota = null): array
    {
        $hayHistorial = $this->tablaExiste('pedido_historial');
        $nota = $nota !== null ? mb_substr(trim($nota), 0, 255) : null;
        $autor = mb_substr(trim($autor) !== '' ? trim($autor) : 'Sistema', 0, 100);

        try {
            $this->conexion->begin_transaction();

            $st = $this->conexion->prepare(
                "SELECT d.estado, d.monto_reembolso, d.rela_id_pedidos, p.estado AS estado_pedido
                 FROM devolucion d INNER JOIN pedidos p ON p.id_pedidos = d.rela_id_pedidos
                 WHERE d.id_devolucion = ? FOR UPDATE"
            );
            $st->bind_param("i", $id_devolucion);
            $st->execute();
            $d = $st->get_result()->fetch_assoc();

            if (!$d) {
                throw new Exception('La devolución no existe.');
            }
            if (!self::estaAbierta($d['estado'])) {
                throw new Exception('Esta devolución ya fue ' . strtolower($d['estado']) . '.');
            }

            $items = $this->itemsDeDevolucion($id_devolucion);
            $monto = $items ? array_sum(array_column($items, 'monto')) : (float) $d['monto_reembolso'];

            if ($items) {
                $id_concepto = $this->idConceptoDevolucion();
                $ref = 'DEVOLUCION-' . $id_devolucion;

                $stStock = $this->conexion->prepare("UPDATE productos SET stock = stock + ? WHERE id_producto = ?");
                $stHist  = $this->conexion->prepare(
                    "INSERT INTO historial_movimientos
                        (fecha_movimiento, cantidad, referencia_ext, comentario, rela_id_productos, rela_id_conceptos)
                     VALUES (CURDATE(), ?, ?, 'Devolución de cliente aprobada', ?, ?)"
                );
                foreach ($items as $it) {
                    $cant = (int) $it['cantidad'];
                    $idp  = (int) $it['id_producto'];
                    $stStock->bind_param("ii", $cant, $idp);
                    $stStock->execute();
                    $stHist->bind_param("isii", $cant, $ref, $idp, $id_concepto);
                    $stHist->execute();
                }
            }

            $st = $this->conexion->prepare(
                "UPDATE devolucion SET estado = 'Aprobada', monto_reembolso = ?, fecha_resolucion = NOW(),
                        nota_admin = ?, autor_resolucion = ? WHERE id_devolucion = ?"
            );
            $st->bind_param("dssi", $monto, $nota, $autor, $id_devolucion);
            $st->execute();

            if ($hayHistorial) {
                $texto = 'Devolución #' . $id_devolucion . ' aprobada. Reembolso: $' . number_format($monto, 2, ',', '.') . '.';
                $st = $this->conexion->prepare(
                    "INSERT INTO pedido_historial (rela_id_pedidos, estado, autor, nota) VALUES (?, ?, ?, ?)"
                );
                $st->bind_param("isss", $d['rela_id_pedidos'], $d['estado_pedido'], $autor, $texto);
                $st->execute();
            }

            $this->conexion->commit();
        } catch (Throwable $e) {
            $this->conexion->rollback();
            return ['ok' => false, 'error' => $e->getMessage() ?: 'No se pudo aprobar la devolución.'];
        }

        avisar_devolucion_resuelta($this->conexion, $id_devolucion);

        return ['ok' => true, 'monto' => $monto];
    }

    public function rechazar(int $id_devolucion, string $autor, string $nota): array
    {
        $nota = mb_substr(trim($nota), 0, 255);
        if ($nota === '') {
            return ['ok' => false, 'error' => 'Escribí el motivo del rechazo para que el cliente lo vea.'];
        }
        $hayHistorial = $this->tablaExiste('pedido_historial');
        $autor = mb_substr(trim($autor) !== '' ? trim($autor) : 'Sistema', 0, 100);

        try {
            $this->conexion->begin_transaction();

            $st = $this->conexion->prepare(
                "SELECT d.estado, d.rela_id_pedidos, p.estado AS estado_pedido
                 FROM devolucion d INNER JOIN pedidos p ON p.id_pedidos = d.rela_id_pedidos
                 WHERE d.id_devolucion = ? FOR UPDATE"
            );
            $st->bind_param("i", $id_devolucion);
            $st->execute();
            $d = $st->get_result()->fetch_assoc();

            if (!$d) {
                throw new Exception('La devolución no existe.');
            }
            if (!self::estaAbierta($d['estado'])) {
                throw new Exception('Esta devolución ya fue ' . strtolower($d['estado']) . '.');
            }

            $st = $this->conexion->prepare(
                "UPDATE devolucion SET estado = 'Rechazada', fecha_resolucion = NOW(),
                        nota_admin = ?, autor_resolucion = ? WHERE id_devolucion = ?"
            );
            $st->bind_param("ssi", $nota, $autor, $id_devolucion);
            $st->execute();

            if ($hayHistorial) {
                $texto = 'Devolución #' . $id_devolucion . ' rechazada. ' . $nota;
                $st = $this->conexion->prepare(
                    "INSERT INTO pedido_historial (rela_id_pedidos, estado, autor, nota) VALUES (?, ?, ?, ?)"
                );
                $st->bind_param("isss", $d['rela_id_pedidos'], $d['estado_pedido'], $autor, $texto);
                $st->execute();
            }

            $this->conexion->commit();
        } catch (Throwable $e) {
            $this->conexion->rollback();
            return ['ok' => false, 'error' => $e->getMessage() ?: 'No se pudo rechazar la devolución.'];
        }

        avisar_devolucion_resuelta($this->conexion, $id_devolucion);

        return ['ok' => true];
    }
}