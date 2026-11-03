<?php

require_once __DIR__ . '/../Database.php';

class MovimientoStock
{
    private mysqli $conexion;

    public function __construct()
    {
        $db = new Database();
        $this->conexion = $db->getConexion();
    }

    /*
    * MOTOR DE STOCK: aplica UN movimiento sobre UNA conexión que le pasa quien lo llama.
    *
    * - NO abre ni cierra transacción: eso lo decide el que lo llama. Así una factura de
    *   40 líneas puede llamar a este método 40 veces dentro de UNA sola transacción.
    * - Si algo falla, lanza Exception con un mensaje claro (el que llama hace rollback).
    * - Devuelve el tipo (E/S) y el stock nuevo, para avisos posteriores.
    */
    public static function aplicarLinea(
        mysqli $conexion,
        int $id_producto,
        int $id_concepto,
        int $cantidad,
        string $fecha_movimiento,
        ?string $referencia_ext,
        ?string $comentario,
        ?int $id_usuario = null,
        ?int $id_compra = null
    ): array {

        // 1) El concepto define si suma (E) o resta (S)
        $stmt = $conexion->prepare(
            "SELECT tipo_movimiento FROM conceptos_movimiento
             WHERE id_concepto = ? AND es_activo = 1"
        );
        $stmt->bind_param("i", $id_concepto);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            throw new Exception("Concepto de movimiento inválido.");
        }

        $tipo = $res->fetch_assoc()['tipo_movimiento'];

        if ($tipo === 'E') {
            $delta = abs($cantidad);
        } elseif ($tipo === 'S') {
            $delta = -abs($cantidad);
        } else {
            throw new Exception("Tipo de movimiento no soportado.");
        }

        if ($delta === 0) {
            throw new Exception("La cantidad no puede ser cero.");
        }

        // 2) Bloqueamos la fila del producto: nadie más toca su stock hasta el commit
        $stmt = $conexion->prepare(
            "SELECT nombre, stock FROM productos
             WHERE id_producto = ? AND es_activo = 1 FOR UPDATE"
        );
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            throw new Exception("Producto no encontrado o inactivo (id {$id_producto}).");
        }

        $producto = $res->fetch_assoc();
        $nuevoStock = (int) $producto['stock'] + $delta;

        // 3) Nunca stock negativo
        if ($nuevoStock < 0) {
            throw new Exception(
                "Stock insuficiente para \"{$producto['nombre']}\" "
                . "(hay {$producto['stock']}, se piden " . abs($delta) . ")."
            );
        }

        // 4) Actualizar stock
        $stmt = $conexion->prepare("UPDATE productos SET stock = ? WHERE id_producto = ?");
        $stmt->bind_param("ii", $nuevoStock, $id_producto);

        if (!$stmt->execute()) {
            throw new Exception("No se pudo actualizar el stock.");
        }

        // 5) Registrar el movimiento en el historial (queda ligado a la compra si hay)
        $hora = date('H:i:s');

        $stmt = $conexion->prepare(
            "INSERT INTO historial_movimientos
                (fecha_movimiento, hora_movimiento, cantidad, referencia_ext, comentario,
                 rela_id_productos, rela_id_conceptos, rela_id_usuario, rela_id_compras)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "ssissiiii",
            $fecha_movimiento, $hora, $delta, $referencia_ext, $comentario,
            $id_producto, $id_concepto, $id_usuario, $id_compra
        );

        if (!$stmt->execute()) {
            throw new Exception("No se pudo registrar el movimiento.");
        }

        return ['tipo' => $tipo, 'stock_nuevo' => $nuevoStock];
    }


    /*
    * Aviso de stock bajo. Se llama DESPUÉS del commit; si falla (Pusher caído, etc.)
    * no debe afectar al movimiento, que ya quedó guardado.
    */
    public static function avisarStockBajo(mysqli $conexion, int $id_producto, int $stock_nuevo): void
    {
        $UMBRAL_STOCK_BAJO = 5;

        if ($stock_nuevo > $UMBRAL_STOCK_BAJO) {
            return;
        }

        try {
            require_once __DIR__ . '/../../config/notificaciones/pusher.php';

            $stmt = $conexion->prepare("SELECT nombre, codigo FROM productos WHERE id_producto = ?");
            $stmt->bind_param("i", $id_producto);
            $stmt->execute();
            $prod = $stmt->get_result()->fetch_assoc();

            notificar_staff(
                'stock_bajo',
                'Stock bajo: ' . $prod['nombre'],
                "El producto {$prod['codigo']} - {$prod['nombre']} quedó con {$stock_nuevo} unidades.",
                '../../views/productos/listarProducto.php?id=' . $id_producto
            );
        } catch (Throwable $e) {
            error_log('Aviso de stock bajo falló: ' . $e->getMessage());
        }
    }


    // REGISTRAR MOVIMIENTO MANUAL (ajustes, mermas, conteos). Misma firma que antes.

    public function registrar(
        int $id_producto,
        int $id_concepto,
        int $cantidad,
        string $fecha_movimiento,
        ?string $referencia_ext,
        ?string $comentario,
        ?int $id_usuario = null
    ) {
        $this->conexion->begin_transaction();

        try {
            $r = self::aplicarLinea(
                $this->conexion, $id_producto, $id_concepto, $cantidad,
                $fecha_movimiento, $referencia_ext, $comentario, $id_usuario
            );

            $this->conexion->commit();

        } catch (Exception $e) {
            $this->conexion->rollback();
            return false;
        }

        if ($r['tipo'] === 'S') {
            self::avisarStockBajo($this->conexion, $id_producto, $r['stock_nuevo']);
        }

        return true;
    }


    // OBTENER MOVIMIENTO POR ID

    public function obtenerPorId(int $id_movimiento)
    {
        $sql = "SELECT
                    hm.*,
                    p.nombre AS nombre_producto,
                    p.codigo,
                    cm.descripcion AS concepto,
                    cm.tipo_movimiento,
                    u.nombre AS nombre_usuario,
                    u.correo AS correo_usuario,
                    pf.nombre_perfil AS rol_usuario
                FROM historial_movimientos hm
                INNER JOIN productos p
                    ON hm.rela_id_productos = p.id_producto
                INNER JOIN conceptos_movimiento cm
                    ON hm.rela_id_conceptos = cm.id_concepto
                LEFT JOIN usuarios u
                    ON hm.rela_id_usuario = u.id_usuario
                LEFT JOIN perfiles pf
                    ON u.rela_id_perfil = pf.id_perfil
                WHERE hm.id_movimientos = ?";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param("i", $id_movimiento);

        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            return $resultado->fetch_assoc();
        }

        return null;
    }


    // LISTAR MOVIMIENTOS CON BÚSQUEDA Y FILTROS
    public function listar(
        string $busqueda = "",
        string $tipo = "",
        int $id_producto = 0,
        string $fecha_desde = "",
        string $fecha_hasta = "",
        int $limite = 9,
        int $offset = 0
    ) {

        $condiciones = [];
        $parametros = [];
        $tipos = "";

        /*
        * BÚSQUEDA
        */
        if ($busqueda !== "") {

            $condiciones[] = "(
                p.codigo LIKE ?
                OR p.nombre LIKE ?
                OR hm.referencia_ext LIKE ?
                OR hm.comentario LIKE ?
                OR cm.descripcion LIKE ?
            )";

            $busqueda = "%" . $busqueda . "%";

            for ($i = 0; $i < 5; $i++) {
                $parametros[] = $busqueda;
                $tipos .= "s";
            }
        }


        /*
        * FILTRO POR TIPO
        */
        if ($tipo === "E" || $tipo === "S") {

            $condiciones[] = "cm.tipo_movimiento = ?";

            $parametros[] = $tipo;
            $tipos .= "s";
        }


        /*
        * FILTRO POR PRODUCTO
        */
        if ($id_producto > 0) {

            $condiciones[] = "hm.rela_id_productos = ?";

            $parametros[] = $id_producto;
            $tipos .= "i";
        }


        /*
        * FILTRO FECHA DESDE
        */
        if ($fecha_desde !== "") {

            $condiciones[] = "hm.fecha_movimiento >= ?";

            $parametros[] = $fecha_desde;
            $tipos .= "s";
        }


        /*
        * FILTRO FECHA HASTA
        */
        if ($fecha_hasta !== "") {

            $condiciones[] = "hm.fecha_movimiento <= ?";

            $parametros[] = $fecha_hasta;
            $tipos .= "s";
        }


        /*
        * ARMAR WHERE
        */
        $where = "";

        if (!empty($condiciones)) {

            $where = "WHERE " . implode(" AND ", $condiciones);
        }


        /*
        * CONSULTA
        */
        $sql = "SELECT
                    hm.id_movimientos,
                    hm.fecha_movimiento,
                    hm.cantidad,
                    hm.referencia_ext,
                    hm.comentario,

                    p.codigo,
                    p.nombre AS nombre_producto,

                    cm.descripcion AS concepto,
                    cm.tipo_movimiento

                FROM historial_movimientos hm

                INNER JOIN productos p
                    ON hm.rela_id_productos = p.id_producto

                INNER JOIN conceptos_movimiento cm
                    ON hm.rela_id_conceptos = cm.id_concepto

                $where

                ORDER BY hm.id_movimientos DESC

                LIMIT ? OFFSET ?";


        $stmt = $this->conexion->prepare($sql);


        /*
        * Agregar LIMIT y OFFSET
        */
        $parametros[] = $limite;
        $tipos .= "i";

        $parametros[] = $offset;
        $tipos .= "i";


        /*
        * bind_param necesita referencias
        */
        $bind = [$tipos];

        foreach ($parametros as $key => $valor) {
            $bind[] = &$parametros[$key];
        }

        call_user_func_array(
            [$stmt, 'bind_param'],
            $bind
        );


        $stmt->execute();

        $resultado = $stmt->get_result();

        $movimientos = [];

        while ($fila = $resultado->fetch_assoc()) {

            $movimientos[] = $fila;
        }

        return $movimientos;
    }


    // CONTAR MOVIMIENTOS
    public function contar(
        string $busqueda = "",
        string $tipo = "",
        int $id_producto = 0,
        string $fecha_desde = "",
        string $fecha_hasta = ""
    ) {

        $condiciones = [];
        $parametros = [];
        $tipos = "";


        /*
        * BÚSQUEDA
        */
        if ($busqueda !== "") {

            $condiciones[] = "(
                p.codigo LIKE ?
                OR p.nombre LIKE ?
                OR hm.referencia_ext LIKE ?
                OR hm.comentario LIKE ?
                OR cm.descripcion LIKE ?
            )";

            $busqueda = "%" . $busqueda . "%";

            for ($i = 0; $i < 5; $i++) {

                $parametros[] = $busqueda;
                $tipos .= "s";
            }
        }


        /*
        * TIPO
        */
        if ($tipo === "E" || $tipo === "S") {

            $condiciones[] = "cm.tipo_movimiento = ?";

            $parametros[] = $tipo;
            $tipos .= "s";
        }


        /*
        * PRODUCTO
        */
        if ($id_producto > 0) {

            $condiciones[] = "hm.rela_id_productos = ?";

            $parametros[] = $id_producto;
            $tipos .= "i";
        }


        /*
        * FECHA DESDE
        */
        if ($fecha_desde !== "") {

            $condiciones[] = "hm.fecha_movimiento >= ?";

            $parametros[] = $fecha_desde;
            $tipos .= "s";
        }


        /*
        * FECHA HASTA
        */
        if ($fecha_hasta !== "") {

            $condiciones[] = "hm.fecha_movimiento <= ?";

            $parametros[] = $fecha_hasta;
            $tipos .= "s";
        }


        $where = "";

        if (!empty($condiciones)) {

            $where = "WHERE " . implode(" AND ", $condiciones);
        }


        $sql = "SELECT COUNT(*) AS total

                FROM historial_movimientos hm

                INNER JOIN productos p
                    ON hm.rela_id_productos = p.id_producto

                INNER JOIN conceptos_movimiento cm
                    ON hm.rela_id_conceptos = cm.id_concepto

                $where";


        $stmt = $this->conexion->prepare($sql);


        if (!empty($parametros)) {

            $bind = [$tipos];

            foreach ($parametros as $key => $valor) {
                $bind[] = &$parametros[$key];
            }

            call_user_func_array(
                [$stmt, 'bind_param'],
                $bind
            );
        }


        $stmt->execute();

        $resultado = $stmt->get_result();

        $fila = $resultado->fetch_assoc();

        return (int) $fila['total'];
    }
}