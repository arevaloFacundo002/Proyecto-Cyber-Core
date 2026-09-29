 <?php

require_once __DIR__ . '/Database.php';

// Errores "esperables" (stock, datos faltantes...) cuyo mensaje sí se le muestra al usuario.
class PedidoException extends Exception {}

class Pedido
{
    private mysqli $conexion;

    public function __construct()
    {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    // =====================================================
    //  DATOS DEL CLIENTE (a partir del usuario logueado)
    // =====================================================
    public function obtenerCliente(int $id_usuario): ?array
    {
        $sql = "SELECT c.id_cliente, c.nombre, c.apellido,
                       d.calle, d.numero_exterior, d.barrio_colonia, d.piso_departamento,
                       l.id_localidad, l.nombre_localidad, l.codigo_postal,
                       p.nombre_provincia, p.dias_transitos_base
                FROM clientes c
                INNER JOIN direcciones d ON d.id_direccion = c.rela_id_direccion
                LEFT JOIN localidades l ON l.id_localidad = d.rela_id_localidad
                LEFT JOIN provincias p ON p.id_provincia = l.rela_id_provincia
                WHERE c.rela_id_usuario = ?
                LIMIT 1";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();

        $fila = $stmt->get_result()->fetch_assoc();

        return $fila ?: null;
    }

    public function direccionEnTexto(array $c): string
    {
        $partes = [];

        $calle = trim(($c['calle'] ?? '') . ' ' . ($c['numero_exterior'] ?? ''));
        if ($calle !== '') {
            $partes[] = $calle;
        }
        if (!empty($c['piso_departamento'])) {
            $partes[] = 'Piso/Depto ' . $c['piso_departamento'];
        }
        if (!empty($c['barrio_colonia'])) {
            $partes[] = $c['barrio_colonia'];
        }
        if (!empty($c['nombre_localidad'])) {
            $loc = $c['nombre_localidad'];
            if (!empty($c['codigo_postal'])) {
                $loc .= ' (CP ' . $c['codigo_postal'] . ')';
            }
            $partes[] = $loc;
        }
        if (!empty($c['nombre_provincia'])) {
            $partes[] = $c['nombre_provincia'];
        }

        return implode(', ', $partes);
    }

    // =====================================================
    //  MÉTODOS DE PAGO ACTIVOS
    // =====================================================
    public function listarMetodosPago(): array
    {
        $res = $this->conexion->query(
            "SELECT id_metodo_pago, nombre, descripcion
             FROM metodos_pago
             WHERE es_activo = 1
             ORDER BY nombre"
        );

        $metodos = [];
        while ($fila = $res->fetch_assoc()) {
            $metodos[] = $fila;
        }
        return $metodos;
    }

    // =====================================================
    //  ENVÍO
    // =====================================================
    private function buscarTarifa(?int $id_localidad): ?array
    {
        if ($id_localidad) {
            $stmt = $this->conexion->prepare(
                "SELECT * FROM tarifa_envios
                 WHERE rela_id_localidades = ?
                 ORDER BY id_tarifa_envios
                 LIMIT 1"
            );
            $stmt->bind_param("i", $id_localidad);
            $stmt->execute();
            $fila = $stmt->get_result()->fetch_assoc();
            if ($fila) {
                return $fila;
            }
        }

        // Si la localidad no tiene tarifa propia usamos la primera cargada.
        $res = $this->conexion->query(
            "SELECT * FROM tarifa_envios ORDER BY id_tarifa_envios LIMIT 1"
        );
        $fila = $res->fetch_assoc();

        return $fila ?: null;
    }

    // Costo = costo base + costo adicional por kg * peso total.
    private function costoEnvio(array $tarifa, float $pesoTotal): float
    {
        $base      = (float) $tarifa['costo_base'];
        $adicional = (float) $tarifa['costo_adicional_por_kg'];

        return round($base + ($adicional * $pesoTotal), 2);
    }

    // =====================================================
    //  LÍNEAS DEL CARRITO (precios y stock REALES, de la base)
    // =====================================================
    private function leerLineas(array $carrito, bool $bloquear): array
    {
        $lineas    = [];
        $problemas = [];

        $sql = "SELECT id_producto, nombre, precio, stock, imagen_url, peso_envio
                FROM productos
                WHERE id_producto = ?
                AND COALESCE(es_descontinuado, 0) = 0";

        if ($bloquear) {
            $sql .= " FOR UPDATE";
        }

        $stmt = $this->conexion->prepare($sql);

        // Ordenamos por id para bloquear siempre en el mismo orden.
        usort($carrito, function ($a, $b) {
            return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
        });

        foreach ($carrito as $item) {
            $id       = (int) ($item['id'] ?? 0);
            $cantidad = (int) ($item['cantidad'] ?? 0);

            if ($id <= 0 || $cantidad <= 0) {
                continue;
            }

            $stmt->bind_param("i", $id);
            $stmt->execute();
            $p = $stmt->get_result()->fetch_assoc();

            if (!$p) {
                $problemas[] = "Un producto de tu carrito ya no está disponible.";
                continue;
            }

            if ((int) $p['stock'] < $cantidad) {
                $problemas[] = "«" . $p['nombre'] . "»: pediste " . $cantidad
                             . " pero solo quedan " . (int) $p['stock'] . ".";
            }

            $precio = (float) $p['precio'];
            $peso   = (float) $p['peso_envio'];

            $lineas[] = [
                'id'       => (int) $p['id_producto'],
                'nombre'   => $p['nombre'],
                'imagen'   => $p['imagen_url'],
                'precio'   => $precio,
                'cantidad' => $cantidad,
                'subtotal' => round($precio * $cantidad, 2),
                'peso'     => $peso * $cantidad,
            ];
        }

        return [$lineas, $problemas];
    }

    // =====================================================
    //  DATOS PARA MOSTRAR EN EL CHECKOUT (sin escribir nada)
    // =====================================================
    public function prepararCheckout(int $id_usuario, array $carrito): array
    {
        $cliente = $this->obtenerCliente($id_usuario);

        if (!$cliente) {
            return ['error' => 'sin_cliente'];
        }

        [$lineas, $problemas] = $this->leerLineas($carrito, false);

        if (empty($lineas)) {
            return ['error' => 'vacio'];
        }

        $subtotal = 0.0;
        $peso     = 0.0;
        foreach ($lineas as $l) {
            $subtotal += $l['subtotal'];
            $peso     += $l['peso'];
        }

        $tarifa = $this->buscarTarifa($cliente['id_localidad'] ? (int) $cliente['id_localidad'] : null);
        $envio  = 0.0;

        if ($tarifa) {
            $envio = $this->costoEnvio($tarifa, $peso);
        } else {
            $problemas[] = "No hay tarifas de envío configuradas. Avisale a un administrador.";
        }

        $dias = $cliente['dias_transitos_base'] !== null ? (int) $cliente['dias_transitos_base'] : 5;

        return [
            'cliente'   => $cliente,
            'direccion' => $this->direccionEnTexto($cliente),
            'lineas'    => $lineas,
            'subtotal'  => round($subtotal, 2),
            'peso'      => $peso,
            'tarifa'    => $tarifa,
            'envio'     => $envio,
            'total'     => round($subtotal + $envio, 2),
            'dias'      => $dias,
            'metodos'   => $this->listarMetodosPago(),
            'problemas' => $problemas,
        ];
    }

    // =====================================================
    //  CREAR EL PEDIDO (todo o nada)
    // =====================================================
    public function crearPedido(int $id_usuario, array $carrito, int $id_metodo_pago): array
    {
        try {

            $this->conexion->begin_transaction();

            // ---- Cliente ----
            $cliente = $this->obtenerCliente($id_usuario);
            if (!$cliente) {
                throw new PedidoException("Tu cuenta todavía no tiene datos de cliente cargados.");
            }

            // ---- Método de pago ----
            $stmt = $this->conexion->prepare(
                "SELECT id_metodo_pago FROM metodos_pago
                 WHERE id_metodo_pago = ? AND es_activo = 1"
            );
            $stmt->bind_param("i", $id_metodo_pago);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new PedidoException("Elegí un método de pago válido.");
            }

            // ---- Concepto de stock "Venta a cliente" ----
            $res = $this->conexion->query(
                "SELECT id_concepto FROM conceptos_movimiento
                 WHERE tipo_movimiento = 'S'
                 AND descripcion = 'Venta a cliente'
                 AND es_activo = 1
                 LIMIT 1"
            );
            $concepto = $res->fetch_assoc();
            if (!$concepto) {
                throw new PedidoException("Falta el concepto de movimiento «Venta a cliente». Avisale a un administrador.");
            }
            $id_concepto = (int) $concepto['id_concepto'];

            // ---- Promoción neutra ----
            $res = $this->conexion->query(
                "SELECT id_promociones FROM promociones
                 WHERE nombre = 'Sin promoción'
                 LIMIT 1"
            );
            $promo = $res->fetch_assoc();
            if (!$promo) {
                throw new PedidoException("Falta ejecutar preparar_checkout.sql (promoción «Sin promoción»).");
            }
            $id_promo = (int) $promo['id_promociones'];

            // ---- Productos (bloqueados hasta terminar) ----
            [$lineas, $problemas] = $this->leerLineas($carrito, true);

            if (empty($lineas)) {
                throw new PedidoException("Tu carrito está vacío.");
            }
            if (!empty($problemas)) {
                throw new PedidoException(implode(' ', $problemas));
            }

            $subtotal = 0.0;
            $peso     = 0.0;
            foreach ($lineas as $l) {
                $subtotal += $l['subtotal'];
                $peso     += $l['peso'];
            }

            // ---- Envío ----
            $tarifa = $this->buscarTarifa($cliente['id_localidad'] ? (int) $cliente['id_localidad'] : null);
            if (!$tarifa) {
                throw new PedidoException("No hay tarifas de envío configuradas.");
            }

            $envio = $this->costoEnvio($tarifa, $peso);
            $total = round($subtotal + $envio, 2);

            // ---- Pedido (rela_id_envio se completa después) ----
            $stmt = $this->conexion->prepare(
                "INSERT INTO pedidos (fecha_pedidos, estado, moneda, monto_total, rela_id_envio, rela_id_cliente)
                 VALUES (CURDATE(), 'En proceso', 'ARS', ?, 0, ?)"
            );
            $id_cliente = (int) $cliente['id_cliente'];
            $stmt->bind_param("di", $total, $id_cliente);
            $stmt->execute();
            $id_pedido = (int) $this->conexion->insert_id;

            // ---- Detalle + stock + historial ----
            $stmtDetalle = $this->conexion->prepare(
                "INSERT INTO detalle_pedidos
                    (cantidad, precio_unitario_base, precio__base_final, subtotal_base,
                     subtotal_final, impuesto_aplicado, nota_detalles,
                     rela_id_productos, rela_id_pedidos, rela_id_promociones)
                 VALUES (?, ?, ?, ?, ?, 0, NULL, ?, ?, ?)"
            );

            $stmtStock = $this->conexion->prepare(
                "UPDATE productos SET stock = stock - ? WHERE id_producto = ?"
            );

            $stmtHist = $this->conexion->prepare(
                "INSERT INTO historial_movimientos
                    (fecha_movimiento, cantidad, referencia_ext, comentario,
                     rela_id_productos, rela_id_conceptos)
                 VALUES (CURDATE(), ?, ?, ?, ?, ?)"
            );

            $referencia = "PEDIDO-" . $id_pedido;
            $comentario = "Venta web";

            foreach ($lineas as $l) {
                $cant   = $l['cantidad'];
                $precio = $l['precio'];
                $sub    = $l['subtotal'];
                $idProd = $l['id'];

                $stmtDetalle->bind_param(
                    "iddddiii",
                    $cant, $precio, $precio, $sub, $sub,
                    $idProd, $id_pedido, $id_promo
                );
                $stmtDetalle->execute();

                $stmtStock->bind_param("ii", $cant, $idProd);
                $stmtStock->execute();

                $movimiento = -$cant;
                $stmtHist->bind_param(
                    "issii",
                    $movimiento, $referencia, $comentario, $idProd, $id_concepto
                );
                $stmtHist->execute();
            }

            // ---- Envío ----
            $dias = $cliente['dias_transitos_base'] !== null ? (int) $cliente['dias_transitos_base'] : 5;
            $fechaEstimada = date('Y-m-d H:i:s', strtotime("+{$dias} days"));
            $direccion     = $this->direccionEnTexto($cliente);
            $empresa       = $tarifa['proveedor_servicio'];
            $id_tarifa     = (int) $tarifa['id_tarifa_envios'];

            $stmt = $this->conexion->prepare(
                "INSERT INTO envios
                    (direccion_entrega, empresa_transporte, estado_envio,
                     fecha_estimada_entrega, rela_id_pedidos, rela_id_tarifa_envios)
                 VALUES (?, ?, 'Pendiente', ?, ?, ?)"
            );
            $stmt->bind_param("sssii", $direccion, $empresa, $fechaEstimada, $id_pedido, $id_tarifa);
            $stmt->execute();
            $id_envio = (int) $this->conexion->insert_id;

            $stmt = $this->conexion->prepare(
                "UPDATE pedidos SET rela_id_envio = ? WHERE id_pedidos = ?"
            );
            $stmt->bind_param("ii", $id_envio, $id_pedido);
            $stmt->execute();

            // ---- Pago (queda pendiente: no hay pasarela real) ----
            $refPago = "WEB-" . $id_pedido . "-" . time();

            $stmt = $this->conexion->prepare(
                "INSERT INTO pagos
                    (estado_pagos, fechas_pagos, monto, referencia_transaccion,
                     rela_id_pedidos, rela_id_metodo_pago)
                 VALUES ('Pendiente', NOW(), ?, ?, ?, ?)"
            );
            $stmt->bind_param("dsii", $total, $refPago, $id_pedido, $id_metodo_pago);
            $stmt->execute();

            $this->conexion->commit();

            return ['ok' => true, 'id_pedido' => $id_pedido];

        } catch (PedidoException $e) {

            $this->conexion->rollback();
            return ['ok' => false, 'error' => $e->getMessage()];

        } catch (Throwable $e) {

            $this->conexion->rollback();
            error_log("Error al crear pedido: " . $e->getMessage());
            return ['ok' => false, 'error' => "No pudimos procesar tu pedido. Probá de nuevo en unos minutos."];
        }
    }

    // =====================================================
    //  UN PEDIDO (solo si es del usuario logueado)
    // =====================================================
    public function obtenerPedido(int $id_pedido, int $id_usuario): ?array
    {
        $sql = "SELECT p.id_pedidos, p.fecha_pedidos, p.estado, p.moneda, p.monto_total,
                       e.direccion_entrega, e.empresa_transporte, e.estado_envio,
                       e.fecha_estimada_entrega,
                       pg.estado_pagos, pg.referencia_transaccion,
                       mp.nombre AS metodo_pago
                FROM pedidos p
                INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
                LEFT JOIN envios e ON e.rela_id_pedidos = p.id_pedidos
                LEFT JOIN pagos pg ON pg.rela_id_pedidos = p.id_pedidos
                LEFT JOIN metodos_pago mp ON mp.id_metodo_pago = pg.rela_id_metodo_pago
                WHERE p.id_pedidos = ? AND c.rela_id_usuario = ?
                LIMIT 1";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("ii", $id_pedido, $id_usuario);
        $stmt->execute();

        $pedido = $stmt->get_result()->fetch_assoc();

        if (!$pedido) {
            return null;
        }

        $stmt = $this->conexion->prepare(
            "SELECT dp.cantidad, dp.precio_unitario_base, dp.subtotal_final, pr.nombre
             FROM detalle_pedidos dp
             INNER JOIN productos pr ON pr.id_producto = dp.rela_id_productos
             WHERE dp.rela_id_pedidos = ?"
        );
        $stmt->bind_param("i", $id_pedido);
        $stmt->execute();

        $items = [];
        $res = $stmt->get_result();
        while ($fila = $res->fetch_assoc()) {
            $items[] = $fila;
        }

        $pedido['items'] = $items;

        return $pedido;
    }
}