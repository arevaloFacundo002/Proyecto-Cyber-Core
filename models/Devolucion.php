<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../includes/avisos.php';
require_once __DIR__ . '/../includes/avisos_devolucion.php';

class Devolucion
{
    // Politicas de devolucion
    public const PLAZO_DIAS = 30;               // fallas: producto defectuoso, danado o equivocado
    public const PLAZO_ARREPENTIMIENTO = 10;    // arrepentimiento u otro motivo

    public const MOTIVOS = [
        'defectuoso'      => ['texto' => 'El producto no funciona o viene con una falla', 'falla' => true],
        'danado'          => ['texto' => 'Llegó dañado',                                   'falla' => true],
        'equivocado'      => ['texto' => 'Me enviaron otro producto',                      'falla' => true],
        'arrepentimiento' => ['texto' => 'Me arrepentí de la compra',                      'falla' => false],
        'otro'            => ['texto' => 'Otro motivo',                                    'falla' => false],
    ];

    public static function esFalla(?string $tipo): bool
    {
        return (bool) (self::MOTIVOS[$tipo ?? '']['falla'] ?? false);
    }

    public static function plazoMotivo(?string $tipo): int
    {
        return self::esFalla($tipo) ? self::PLAZO_DIAS : self::PLAZO_ARREPENTIMIENTO;
    }

    public const ESTADOS = [
        'Pendiente' => ['emoji' => '🟡', 'color' => '#facc15'],
        'Aprobada'  => ['emoji' => '🟢', 'color' => '#22c55e'],
        'Producto enviado' => ['emoji' => '📦', 'color' => '#38bdf8'],
        'Recibida'  => ['emoji' => '✅', 'color' => '#16a34a'],
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
        return !in_array($estado, ['Aprobada', 'Producto enviado', 'Recibida', 'Rechazada'], true);
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

        $limite       = date('d/m/Y', strtotime($entrega . ' +' . self::PLAZO_DIAS . ' days'));
        $limite_arrep = date('d/m/Y', strtotime($entrega . ' +' . self::PLAZO_ARREPENTIMIENTO . ' days'));
        return [
            'ok'           => true,
            'limite'       => $limite,
            'limite_arrep' => $limite_arrep,
            'arrep_vigente' => $dias <= self::PLAZO_ARREPENTIMIENTO,
            'dias'         => $dias,
        ];
    }

    public function solicitar(int $id_pedido, int $id_usuario, string $motivo, array $cantidades, string $tipo_motivo = 'otro'): array
    {
        if (!isset(self::MOTIVOS[$tipo_motivo])) {
            return ['ok' => false, 'error' => 'Elegí el motivo de la devolución.'];
        }

        $comentario = trim($motivo);
        if ($tipo_motivo === 'otro' && $comentario === '') {
            return ['ok' => false, 'error' => 'Contanos el motivo de la devolución.'];
        }
        $motivo = self::MOTIVOS[$tipo_motivo]['texto'] . ($comentario !== '' ? ' — ' . $comentario : '');
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
            $plazo = self::plazoMotivo($tipo_motivo);
            if ($puede['dias'] > $plazo) {
                throw new Exception('Pasó el plazo de ' . $plazo . ' días para devolver por este motivo.');
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

            // Si el problema es nuestro (falla, danado o equivocado) tambien se devuelve la parte proporcional del envio
            $monto_envio = 0.0;
            if (self::esFalla($tipo_motivo)) {
                $st = $this->conexion->prepare(
                    "SELECT p.monto_total, COALESCE(SUM(dp.subtotal_final), 0) AS productos
                     FROM pedidos p LEFT JOIN detalle_pedidos dp ON dp.rela_id_pedidos = p.id_pedidos
                     WHERE p.id_pedidos = ? GROUP BY p.id_pedidos, p.monto_total"
                );
                $st->bind_param("i", $id_pedido);
                $st->execute();
                $r = $st->get_result()->fetch_assoc();
                if ($r && (float) $r['productos'] > 0) {
                    $envio_pedido = max(0.0, (float) $r['monto_total'] - (float) $r['productos']);
                    $monto_envio  = round($envio_pedido * ($total / (float) $r['productos']), 2);
                }
            }
            $reembolso = round($total + $monto_envio, 2);

            $st = $this->conexion->prepare(
                "INSERT INTO devolucion (motivo, tipo_motivo, estado, monto_reembolso, monto_envio, fecha_solicitud, rela_id_pedidos)
                 VALUES (?, ?, 'Pendiente', ?, ?, CURDATE(), ?)"
            );
            $st->bind_param("ssddi", $motivo, $tipo_motivo, $reembolso, $monto_envio, $id_pedido);
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

        return ['ok' => true, 'id_devolucion' => $id_devolucion, 'monto' => $reembolso];
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
            "SELECT id_devolucion, motivo, estado, monto_reembolso, monto_envio, fecha_solicitud, fecha_resolucion, nota_admin,
                    metodo_entrega, empresa_transporte, num_seguimiento, fecha_despacho, fecha_recibida, nota_recepcion
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
            $sql .= " AND (d.estado IS NULL OR d.estado NOT IN ('Aprobada','Producto enviado','Recibida','Rechazada'))";
        } elseif ($estado === 'EnCurso') {
            $sql .= " AND d.estado IN ('Aprobada','Producto enviado')";
        } elseif ($estado === 'Recibida' || $estado === 'Rechazada') {
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
        $r = ['Pendiente' => 0, 'EnCurso' => 0, 'Recibida' => 0, 'Rechazada' => 0];
        $res = $this->conexion->query("SELECT estado, COUNT(*) AS n FROM devolucion GROUP BY estado");
        foreach ($res->fetch_all(MYSQLI_ASSOC) as $f) {
            $clave = self::estaAbierta($f['estado']) ? 'Pendiente'
                   : (in_array($f['estado'], ['Aprobada', 'Producto enviado'], true) ? 'EnCurso' : $f['estado']);
            $r[$clave] += (int) $f['n'];
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

        // Datos del cliente para poder contactarlo
        $st = $this->conexion->prepare("SELECT correo FROM usuarios WHERE id_usuario = ?");
        $st->bind_param("i", $d['id_usuario']);
        $st->execute();
        $d['correo'] = $st->get_result()->fetch_assoc()['correo'] ?? '';

        $st = $this->conexion->prepare(
            "SELECT t.descripcion AS tipo, co.valor
             FROM contactos co
             INNER JOIN tipos_contacto t ON t.id_tipo_contacto = co.rela_id_tipo_contacto
             INNER JOIN clientes c ON c.id_cliente = co.rela_id_cliente
             WHERE c.rela_id_usuario = ?"
        );
        $st->bind_param("i", $d['id_usuario']);
        $st->execute();
        $d['contactos'] = $st->get_result()->fetch_all(MYSQLI_ASSOC);

        // Como se le entrego el pedido original
        $st = $this->conexion->prepare(
            "SELECT direccion_entrega, recibe_nombre, recibe_telefono, empresa_transporte AS empresa_pedido, num_seguimiento AS seguimiento_pedido
             FROM envios WHERE rela_id_pedidos = ? ORDER BY id_envios DESC LIMIT 1"
        );
        $st->bind_param("i", $d['rela_id_pedidos']);
        $st->execute();
        $d['envio_pedido'] = $st->get_result()->fetch_assoc() ?: [];

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

    // APROBAR = autorizar la devolucion. El stock y el reembolso se resuelven cuando llega el producto.
    public function aprobar(int $id_devolucion, string $autor, ?string $nota = null): array
    {
        $hayHistorial = $this->tablaExiste('pedido_historial');
        $nota = $nota !== null ? mb_substr(trim($nota), 0, 255) : null;
        $autor = mb_substr(trim($autor) !== '' ? trim($autor) : 'Sistema', 0, 100);

        try {
            $this->conexion->begin_transaction();

            $st = $this->conexion->prepare(
                "SELECT d.estado, d.monto_reembolso, d.monto_envio, d.rela_id_pedidos, p.estado AS estado_pedido
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
            $monto = $items ? array_sum(array_column($items, 'monto')) + (float) $d['monto_envio'] : (float) $d['monto_reembolso'];

            $st = $this->conexion->prepare(
                "UPDATE devolucion SET estado = 'Aprobada', monto_reembolso = ?, fecha_resolucion = NOW(),
                        nota_admin = ?, autor_resolucion = ? WHERE id_devolucion = ?"
            );
            $st->bind_param("dssi", $monto, $nota, $autor, $id_devolucion);
            $st->execute();

            if ($hayHistorial) {
                $texto = 'Devolución #' . $id_devolucion . ' aprobada. Reembolso: $' . number_format($monto, 2, ',', '.') . '. Esperando que el cliente envíe el producto.';
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

        avisar_devolucion_aprobada($this->conexion, $id_devolucion);

        return ['ok' => true, 'monto' => $monto];
    }

    // EL CLIENTE AVISA QUE YA DESPACHO EL PRODUCTO (o que lo va a llevar al local)
    public function informarEnvio(int $id_devolucion, int $id_usuario, string $metodo, string $empresa, string $seguimiento): array
    {
        if (!in_array($metodo, ['envio', 'local'], true)) {
            return ['ok' => false, 'error' => 'Elegí cómo nos vas a hacer llegar el producto.'];
        }
        $empresa = mb_substr(trim($empresa), 0, 100);
        $seguimiento = mb_substr(trim($seguimiento), 0, 100);
        if ($metodo === 'envio' && ($empresa === '' || $seguimiento === '')) {
            return ['ok' => false, 'error' => 'Escribí la empresa de transporte y el número de seguimiento.'];
        }
        if ($metodo === 'local') {
            $empresa = null;
            $seguimiento = null;
        }

        try {
            $this->conexion->begin_transaction();

            $st = $this->conexion->prepare(
                "SELECT d.estado, d.rela_id_pedidos, p.estado AS estado_pedido
                 FROM devolucion d
                 INNER JOIN pedidos p ON p.id_pedidos = d.rela_id_pedidos
                 INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
                 WHERE d.id_devolucion = ? AND c.rela_id_usuario = ? FOR UPDATE"
            );
            $st->bind_param("ii", $id_devolucion, $id_usuario);
            $st->execute();
            $d = $st->get_result()->fetch_assoc();

            if (!$d) {
                throw new Exception('La devolución no existe.');
            }
            if ($d['estado'] !== 'Aprobada') {
                throw new Exception('Solo podés avisar el envío cuando la devolución está aprobada.');
            }

            $st = $this->conexion->prepare(
                "UPDATE devolucion SET estado = 'Producto enviado', metodo_entrega = ?, empresa_transporte = ?,
                        num_seguimiento = ?, fecha_despacho = NOW() WHERE id_devolucion = ?"
            );
            $st->bind_param("sssi", $metodo, $empresa, $seguimiento, $id_devolucion);
            $st->execute();

            if ($this->tablaExiste('pedido_historial')) {
                $texto = 'Devolución #' . $id_devolucion . ': el cliente avisó que ' . ($metodo === 'local'
                    ? 'va a llevar el producto al local.'
                    : 'despachó el producto por ' . $empresa . ' (seguimiento ' . $seguimiento . ').');
                $autor = 'Cliente';
                $st = $this->conexion->prepare(
                    "INSERT INTO pedido_historial (rela_id_pedidos, estado, autor, nota) VALUES (?, ?, ?, ?)"
                );
                $st->bind_param("isss", $d['rela_id_pedidos'], $d['estado_pedido'], $autor, $texto);
                $st->execute();
            }

            $this->conexion->commit();
        } catch (Throwable $e) {
            $this->conexion->rollback();
            return ['ok' => false, 'error' => $e->getMessage() ?: 'No se pudo registrar el aviso.'];
        }

        avisar_devolucion_enviada($this->conexion, $id_devolucion);

        return ['ok' => true];
    }

    // EL NEGOCIO RECIBE Y REVISA EL PRODUCTO: aca se decide si vuelve al stock
    public function recibir(int $id_devolucion, string $autor, bool $vuelve_stock, ?string $nota = null): array
    {
        $nota = $nota !== null ? mb_substr(trim($nota), 0, 255) : null;
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
            if (!in_array($d['estado'], ['Aprobada', 'Producto enviado'], true)) {
                throw new Exception('Solo se puede marcar como recibida una devolución aprobada.');
            }

            $items = $this->itemsDeDevolucion($id_devolucion);
            if ($items && $vuelve_stock) {
                $id_concepto = $this->idConceptoDevolucion();
                $ref = 'DEVOLUCION-' . $id_devolucion;

                $stStock = $this->conexion->prepare("UPDATE productos SET stock = stock + ? WHERE id_producto = ?");
                $stHist  = $this->conexion->prepare(
                    "INSERT INTO historial_movimientos
                        (fecha_movimiento, cantidad, referencia_ext, comentario, rela_id_productos, rela_id_conceptos)
                     VALUES (CURDATE(), ?, ?, 'Devolución de cliente recibida', ?, ?)"
                );
                foreach ($items as $it) {
                    $cant = (int) $it['cantidad'];
                    $idp  = (int) $it['id_producto'];
                    $stStock->bind_param("ii", $cant, $idp);
                    $stStock->execute();
                    $stHist->bind_param("isii", $cant, $ref, $idp, $id_concepto);
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
            }

            $vs = $vuelve_stock ? 1 : 0;
            $st = $this->conexion->prepare(
                "UPDATE devolucion SET estado = 'Recibida', vuelve_stock = ?, fecha_recibida = NOW(), nota_recepcion = ?
                 WHERE id_devolucion = ?"
            );
            $st->bind_param("isi", $vs, $nota, $id_devolucion);
            $st->execute();

            if ($this->tablaExiste('pedido_historial')) {
                $texto = 'Devolución #' . $id_devolucion . ': producto recibido. '
                       . ($vuelve_stock ? 'Volvió al stock.' : 'No volvió al stock.');
                $st = $this->conexion->prepare(
                    "INSERT INTO pedido_historial (rela_id_pedidos, estado, autor, nota) VALUES (?, ?, ?, ?)"
                );
                $st->bind_param("isss", $d['rela_id_pedidos'], $d['estado_pedido'], $autor, $texto);
                $st->execute();
            }

            $this->conexion->commit();
        } catch (Throwable $e) {
            $this->conexion->rollback();
            return ['ok' => false, 'error' => $e->getMessage() ?: 'No se pudo registrar la recepción.'];
        }

        avisar_devolucion_recibida($this->conexion, $id_devolucion);

        return ['ok' => true, 'vuelve_stock' => $vuelve_stock];
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