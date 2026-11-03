<?php

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/MovimientoStock.php';

/*
 * COMPRAS A PROVEEDORES (la factura que llega con la mercadería).
 *
 * Tablas: compras (cabecera) + detalle_compras (líneas) + historial_movimientos (stock).
 * Estados: borrador -> confirmada -> anulada
 *   - borrador:   se puede editar; NO toca el stock.
 *   - confirmada: generó UN movimiento de stock por línea, todo en una sola transacción.
 *   - anulada:    se revirtió el stock con movimientos inversos.
 *
 * (No confundir con la carpeta /compras de la raíz: esa es el checkout del cliente.)
 */
class CompraProveedor
{
    const CONCEPTO_COMPRA = 'Compra de proveedor';
    const CONCEPTO_ANULACION = 'Anulación de compra';

    private mysqli $conexion;

    public function __construct()
    {
        $db = new Database();
        $this->conexion = $db->getConexion();
    }


    /* ------------------------------------------------------------------
     * GUARDAR (crear o editar un borrador) y, opcionalmente, CONFIRMAR
     * ------------------------------------------------------------------
     * $cab:    ['id_proveedor','comprobante','fecha','bonificacion']
     * $lineas: [ ['id_producto','cantidad','costo'], ... ]
     * Devuelve: ['ok'=>bool, 'id_compra'=>int, 'movimientos'=>int, 'error'=>string]
     */
    public function guardar(
        array $cab,
        array $lineas,
        bool $confirmar,
        ?int $id_usuario,
        ?int $id_compra = null
    ): array {

        $error = $this->validar($cab, $lineas);
        if ($error !== null) {
            return ['ok' => false, 'error' => $error];
        }

        $subtotal = 0.0;
        foreach ($lineas as $l) {
            $subtotal += round($l['cantidad'] * $l['costo'], 2);
        }
        $bonificacion = round((float) $cab['bonificacion'], 2);

        if ($bonificacion > $subtotal) {
            return ['ok' => false, 'error' => 'La bonificación no puede ser mayor al subtotal de la factura.'];
        }

        $total = round($subtotal - $bonificacion, 2);
        $movimientos = 0;
        $stocks_bajos = [];

        $this->conexion->begin_transaction();

        try {
            // Si es edición: bloquear la compra y comprobar que siga en borrador
            if ($id_compra) {
                $actual = $this->bloquear($id_compra);
                if ($actual['estado'] !== 'borrador') {
                    throw new Exception('Solo se pueden editar compras en estado borrador.');
                }
            }

            // El mismo comprobante del mismo proveedor no se puede cargar dos veces
            $excluir = (int) $id_compra;
            $stmt = $this->conexion->prepare(
                "SELECT id_compras FROM compras
                 WHERE rela_id_proveedores = ? AND comprobante_compra = ? AND id_compras <> ?"
            );
            $stmt->bind_param("isi", $cab['id_proveedor'], $cab['comprobante'], $excluir);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception(
                    "Ya existe una compra con el comprobante \"{$cab['comprobante']}\" para este proveedor."
                );
            }

            if ($id_compra) {
                // Editar cabecera y reemplazar las líneas
                $stmt = $this->conexion->prepare(
                    "UPDATE compras
                     SET fecha_compra = ?, comprobante_compra = ?, bonificacion = ?, total_compra = ?,
                         rela_id_proveedores = ?
                     WHERE id_compras = ?"
                );
                $stmt->bind_param(
                    "ssddii",
                    $cab['fecha'], $cab['comprobante'], $bonificacion, $total,
                    $cab['id_proveedor'], $id_compra
                );
                $stmt->execute();

                $stmt = $this->conexion->prepare("DELETE FROM detalle_compras WHERE rela_id_compras = ?");
                $stmt->bind_param("i", $id_compra);
                $stmt->execute();

            } else {
                $stmt = $this->conexion->prepare(
                    "INSERT INTO compras
                        (fecha_compra, comprobante_compra, bonificacion, total_compra, estado,
                         rela_id_proveedores, rela_id_usuario)
                     VALUES (?, ?, ?, ?, 'borrador', ?, ?)"
                );
                $stmt->bind_param(
                    "ssddii",
                    $cab['fecha'], $cab['comprobante'], $bonificacion, $total,
                    $cab['id_proveedor'], $id_usuario
                );
                $stmt->execute();
                $id_compra = $stmt->insert_id;
            }

            // Líneas
            $stmt = $this->conexion->prepare(
                "INSERT INTO detalle_compras
                    (cantidad, costo_unitario, subtotal_linea, rela_id_compras, rela_id_productos)
                 VALUES (?, ?, ?, ?, ?)"
            );
            foreach ($lineas as $l) {
                $subtotal_linea = round($l['cantidad'] * $l['costo'], 2);
                $stmt->bind_param(
                    "iddii",
                    $l['cantidad'], $l['costo'], $subtotal_linea, $id_compra, $l['id_producto']
                );
                $stmt->execute();
            }

            if ($confirmar) {
                $r = $this->confirmarInterno($id_compra, $id_usuario);
                $movimientos = $r['movimientos'];
            }

            $this->conexion->commit();

        } catch (Throwable $e) {
            $this->conexion->rollback();
            return ['ok' => false, 'error' => $this->mensajeDe($e)];
        }

        return ['ok' => true, 'id_compra' => (int) $id_compra, 'movimientos' => $movimientos];
    }


    /* ------------------------------------------------------------------
     * CONFIRMAR un borrador ya guardado (desde la pantalla de detalle)
     * ------------------------------------------------------------------ */
    public function confirmar(int $id_compra, ?int $id_usuario): array
    {
        $this->conexion->begin_transaction();

        try {
            $r = $this->confirmarInterno($id_compra, $id_usuario);
            $this->conexion->commit();

        } catch (Throwable $e) {
            $this->conexion->rollback();
            return ['ok' => false, 'error' => $this->mensajeDe($e)];
        }

        return ['ok' => true, 'movimientos' => $r['movimientos']];
    }


    // Aplica las líneas al stock. Debe llamarse DENTRO de una transacción abierta.
    private function confirmarInterno(int $id_compra, ?int $id_usuario): array
    {
        $compra = $this->bloquear($id_compra);

        if ($compra['estado'] !== 'borrador') {
            throw new Exception('La compra ya no está en borrador.');
        }

        $lineas = $this->lineasDeCompra($id_compra);

        if (count($lineas) === 0) {
            throw new Exception('La compra no tiene productos cargados.');
        }

        // Siempre en el mismo orden (por producto): evita bloqueos cruzados entre dos facturas a la vez
        usort($lineas, fn($a, $b) => $a['rela_id_productos'] <=> $b['rela_id_productos']);

        $concepto = $this->idConcepto(self::CONCEPTO_COMPRA);
        $referencia = mb_substr($compra['comprobante_compra'], 0, 50);
        $comentario = 'Compra a proveedor - comprobante ' . $compra['comprobante_compra'];

        $n = 0;
        foreach ($lineas as $l) {
            MovimientoStock::aplicarLinea(
                $this->conexion,
                (int) $l['rela_id_productos'],
                $concepto,
                (int) $l['cantidad'],
                $compra['fecha_compra'],
                $referencia,
                $comentario,
                $id_usuario,
                $id_compra
            );
            $n++;
        }

        $stmt = $this->conexion->prepare(
            "UPDATE compras SET estado = 'confirmada', fecha_confirmacion = NOW() WHERE id_compras = ?"
        );
        $stmt->bind_param("i", $id_compra);
        $stmt->execute();

        return ['movimientos' => $n];
    }


    /* ------------------------------------------------------------------
     * ANULAR una compra confirmada: movimientos inversos (salidas).
     * Si parte de esa mercadería ya se vendió, el stock no alcanza y se rechaza TODO.
     * ------------------------------------------------------------------ */
    public function anular(int $id_compra, ?int $id_usuario): array
    {
        $stocks = [];

        $this->conexion->begin_transaction();

        try {
            $compra = $this->bloquear($id_compra);

            if ($compra['estado'] !== 'confirmada') {
                throw new Exception('Solo se pueden anular compras confirmadas.');
            }

            $lineas = $this->lineasDeCompra($id_compra);
            usort($lineas, fn($a, $b) => $a['rela_id_productos'] <=> $b['rela_id_productos']);

            $concepto = $this->idConcepto(self::CONCEPTO_ANULACION);
            $referencia = mb_substr('ANUL-' . $compra['comprobante_compra'], 0, 50);
            $comentario = 'Anulación de compra - comprobante ' . $compra['comprobante_compra'];

            $n = 0;
            foreach ($lineas as $l) {
                $r = MovimientoStock::aplicarLinea(
                    $this->conexion,
                    (int) $l['rela_id_productos'],
                    $concepto,
                    (int) $l['cantidad'],
                    date('Y-m-d'),
                    $referencia,
                    $comentario,
                    $id_usuario,
                    $id_compra
                );
                $stocks[(int) $l['rela_id_productos']] = $r['stock_nuevo'];
                $n++;
            }

            $stmt = $this->conexion->prepare("UPDATE compras SET estado = 'anulada' WHERE id_compras = ?");
            $stmt->bind_param("i", $id_compra);
            $stmt->execute();

            $this->conexion->commit();

        } catch (Throwable $e) {
            $this->conexion->rollback();
            return ['ok' => false, 'error' => 'No se pudo anular: ' . $this->mensajeDe($e)];
        }

        // Ya con todo guardado: avisar de stock bajo (si falla, no afecta la anulación)
        foreach ($stocks as $id_producto => $stock_nuevo) {
            MovimientoStock::avisarStockBajo($this->conexion, $id_producto, $stock_nuevo);
        }

        return ['ok' => true, 'movimientos' => $n];
    }


    /* ------------------------------------------------------------------
     * ELIMINAR un borrador (una compra confirmada o anulada NO se borra: es historial)
     * ------------------------------------------------------------------ */
    public function eliminarBorrador(int $id_compra): array
    {
        $this->conexion->begin_transaction();

        try {
            $compra = $this->bloquear($id_compra);

            if ($compra['estado'] !== 'borrador') {
                throw new Exception('Solo se pueden eliminar borradores.');
            }

            $stmt = $this->conexion->prepare("DELETE FROM detalle_compras WHERE rela_id_compras = ?");
            $stmt->bind_param("i", $id_compra);
            $stmt->execute();

            $stmt = $this->conexion->prepare("DELETE FROM compras WHERE id_compras = ?");
            $stmt->bind_param("i", $id_compra);
            $stmt->execute();

            $this->conexion->commit();

        } catch (Throwable $e) {
            $this->conexion->rollback();
            return ['ok' => false, 'error' => $this->mensajeDe($e)];
        }

        return ['ok' => true];
    }


    /* ------------------------------------------------------------------
     * CONSULTAS
     * ------------------------------------------------------------------ */

    public function obtener(int $id_compra): ?array
    {
        $stmt = $this->conexion->prepare(
            "SELECT c.*, p.razon_social, u.nombre AS usuario_nombre
             FROM compras c
             INNER JOIN proveedores p ON p.id_proveedor = c.rela_id_proveedores
             LEFT JOIN usuarios u ON u.id_usuario = c.rela_id_usuario
             WHERE c.id_compras = ?"
        );
        $stmt->bind_param("i", $id_compra);
        $stmt->execute();
        $compra = $stmt->get_result()->fetch_assoc();

        if (!$compra) {
            return null;
        }

        $compra['lineas'] = $this->lineasDeCompra($id_compra);
        return $compra;
    }

    public function lineasDeCompra(int $id_compra): array
    {
        $stmt = $this->conexion->prepare(
            "SELECT dc.id_detalle_compra, dc.rela_id_productos, dc.cantidad, dc.costo_unitario,
                    dc.subtotal_linea, p.codigo, p.nombre, p.stock
             FROM detalle_compras dc
             INNER JOIN productos p ON p.id_producto = dc.rela_id_productos
             WHERE dc.rela_id_compras = ?
             ORDER BY dc.id_detalle_compra"
        );
        $stmt->bind_param("i", $id_compra);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Los movimientos de stock que generó esta compra (y su anulación, si la hubo)
    public function movimientosDeCompra(int $id_compra): array
    {
        $stmt = $this->conexion->prepare(
            "SELECT h.fecha_movimiento, h.hora_movimiento, h.cantidad, h.referencia_ext,
                    p.codigo, p.nombre, cm.descripcion AS concepto
             FROM historial_movimientos h
             INNER JOIN productos p ON p.id_producto = h.rela_id_productos
             INNER JOIN conceptos_movimiento cm ON cm.id_concepto = h.rela_id_conceptos
             WHERE h.rela_id_compras = ?
             ORDER BY h.id_movimientos"
        );
        $stmt->bind_param("i", $id_compra);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function listar(string $busqueda = '', string $estado = '', int $limite = 10, int $offset = 0): array
    {
        $like = '%' . $busqueda . '%';

        $stmt = $this->conexion->prepare(
            "SELECT c.id_compras, c.fecha_compra, c.comprobante_compra, c.total_compra, c.estado,
                    p.razon_social,
                    (SELECT COUNT(*) FROM detalle_compras d WHERE d.rela_id_compras = c.id_compras) AS items
             FROM compras c
             INNER JOIN proveedores p ON p.id_proveedor = c.rela_id_proveedores
             WHERE (c.comprobante_compra LIKE ? OR p.razon_social LIKE ?)
               AND (? = '' OR c.estado = ?)
             ORDER BY c.fecha_compra DESC, c.id_compras DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->bind_param("ssssii", $like, $like, $estado, $estado, $limite, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function contar(string $busqueda = '', string $estado = ''): int
    {
        $like = '%' . $busqueda . '%';

        $stmt = $this->conexion->prepare(
            "SELECT COUNT(*) AS total
             FROM compras c
             INNER JOIN proveedores p ON p.id_proveedor = c.rela_id_proveedores
             WHERE (c.comprobante_compra LIKE ? OR p.razon_social LIKE ?)
               AND (? = '' OR c.estado = ?)"
        );
        $stmt->bind_param("ssss", $like, $like, $estado, $estado);
        $stmt->execute();
        return (int) $stmt->get_result()->fetch_assoc()['total'];
    }

    public function proveedoresActivos(): array
    {
        $res = $this->conexion->query(
            "SELECT id_proveedor, razon_social FROM proveedores WHERE es_activo = 1 ORDER BY razon_social"
        );
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    /*
     * Para el buscador / lector de códigos. Si el texto coincide EXACTO con un código
     * (lo que hace un lector de barras), devuelve solo ese producto con exacto = true.
     */
    public function buscarProductos(string $q, int $limite = 10): array
    {
        $base = "SELECT p.id_producto, p.codigo, p.nombre, p.stock,
                        (SELECT dc.costo_unitario
                         FROM detalle_compras dc
                         INNER JOIN compras c ON c.id_compras = dc.rela_id_compras
                         WHERE dc.rela_id_productos = p.id_producto AND c.estado = 'confirmada'
                         ORDER BY c.fecha_compra DESC, dc.id_detalle_compra DESC
                         LIMIT 1) AS ultimo_costo
                 FROM productos p
                 WHERE p.es_activo = 1 ";

        // 1) código exacto
        $stmt = $this->conexion->prepare($base . "AND p.codigo = ? LIMIT 1");
        $stmt->bind_param("s", $q);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();

        if ($fila) {
            return [$this->normalizarProducto($fila, true)];
        }

        // 2) por nombre o parte del código
        $like = '%' . $q . '%';
        $stmt = $this->conexion->prepare($base . "AND (p.nombre LIKE ? OR p.codigo LIKE ?) ORDER BY p.nombre LIMIT ?");
        $stmt->bind_param("ssi", $like, $like, $limite);
        $stmt->execute();

        $out = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $f) {
            $out[] = $this->normalizarProducto($f, false);
        }
        return $out;
    }

    // Completa código/nombre/stock de las líneas (para volver a mostrar la grilla)
    public function enriquecerLineas(array $lineas): array
    {
        if (count($lineas) === 0) {
            return [];
        }

        $ids = array_values(array_unique(array_map(fn($l) => (int) $l['id_producto'], $lineas)));
        $marcas = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $this->conexion->prepare(
            "SELECT id_producto, codigo, nombre, stock FROM productos WHERE id_producto IN ($marcas)"
        );
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();

        $info = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $p) {
            $info[(int) $p['id_producto']] = $p;
        }

        $out = [];
        foreach ($lineas as $l) {
            $p = $info[(int) $l['id_producto']] ?? null;
            if (!$p) {
                continue;
            }
            $out[] = [
                'id_producto' => (int) $p['id_producto'],
                'codigo' => $p['codigo'],
                'nombre' => $p['nombre'],
                'stock' => (int) $p['stock'],
                'cantidad' => $l['cantidad'],
                'costo' => $l['costo'],
            ];
        }
        return $out;
    }


    /* ------------------------------------------------------------------
     * AUXILIARES
     * ------------------------------------------------------------------ */

    private function normalizarProducto(array $f, bool $exacto): array
    {
        return [
            'id_producto' => (int) $f['id_producto'],
            'codigo' => $f['codigo'],
            'nombre' => $f['nombre'],
            'stock' => (int) $f['stock'],
            'ultimo_costo' => $f['ultimo_costo'] !== null ? (float) $f['ultimo_costo'] : null,
            'exacto' => $exacto,
        ];
    }

    // Bloquea la fila de la compra hasta el commit (nadie más la confirma/anula a la vez)
    private function bloquear(int $id_compra): array
    {
        $stmt = $this->conexion->prepare("SELECT * FROM compras WHERE id_compras = ? FOR UPDATE");
        $stmt->bind_param("i", $id_compra);
        $stmt->execute();
        $compra = $stmt->get_result()->fetch_assoc();

        if (!$compra) {
            throw new Exception('La compra no existe.');
        }
        return $compra;
    }

    private function idConcepto(string $descripcion): int
    {
        $stmt = $this->conexion->prepare(
            "SELECT id_concepto FROM conceptos_movimiento WHERE descripcion = ? AND es_activo = 1 LIMIT 1"
        );
        $stmt->bind_param("s", $descripcion);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();

        if (!$fila) {
            throw new Exception(
                "Falta el concepto de movimiento \"{$descripcion}\". Ejecutá el paso 4 de compras_migracion.sql."
            );
        }
        return (int) $fila['id_concepto'];
    }

    private function validar(array $cab, array $lineas): ?string
    {
        if ((int) $cab['id_proveedor'] <= 0) {
            return 'Elegí un proveedor.';
        }

        $stmt = $this->conexion->prepare("SELECT 1 FROM proveedores WHERE id_proveedor = ? AND es_activo = 1");
        $stmt->bind_param("i", $cab['id_proveedor']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            return 'El proveedor elegido no existe o está inactivo.';
        }

        if ($cab['comprobante'] === '' || mb_strlen($cab['comprobante']) > 100) {
            return 'Ingresá el número de comprobante (hasta 100 caracteres).';
        }

        $f = DateTime::createFromFormat('Y-m-d', (string) $cab['fecha']);
        if (!$f || $f->format('Y-m-d') !== $cab['fecha']) {
            return 'La fecha de la compra no es válida.';
        }

        if ((float) $cab['bonificacion'] < 0) {
            return 'La bonificación no puede ser negativa.';
        }

        if (count($lineas) === 0) {
            return 'Agregá al menos un producto a la compra.';
        }

        foreach ($lineas as $i => $l) {
            $n = $i + 1;
            if ($l['id_producto'] <= 0) {
                return "Línea {$n}: producto inválido.";
            }
            if ($l['cantidad'] <= 0 || $l['cantidad'] > 100000) {
                return "Línea {$n}: la cantidad debe ser un número entero mayor a cero.";
            }
            if ($l['costo'] < 0) {
                return "Línea {$n}: el costo no puede ser negativo.";
            }
        }

        return null;
    }

    // Convierte una excepción en un mensaje para mostrar al usuario
    private function mensajeDe(Throwable $e): string
    {
        if ($e instanceof mysqli_sql_exception) {
            if ((int) $e->getCode() === 1062) {
                return 'Ya existe una compra con ese comprobante para este proveedor.';
            }
            error_log('CompraProveedor SQL: ' . $e->getMessage());
            return 'Error de base de datos. Intentá de nuevo; si persiste, avisá al administrador.';
        }
        return $e->getMessage();
    }
}