<?php

require_once 'C:\Users\areva\.vscode\cyber_core\models/Database.php';

class MovimientoStock
{
    private mysqli $conexion;

    public function __construct()
    {
        $db = new Database();
        $this->conexion = $db->getConexion();
    }

    // REGISTRAR MOVIMIENTO

    public function registrar(
        int $id_producto,
        int $id_concepto,
        int $cantidad,
        string $fecha_movimiento,
        ?string $referencia_ext,
        ?string $comentario,
        ?int $id_usuario = null
    ) {

        /*
        * Primero obtenemos el tipo de movimiento
        * correspondiente al concepto seleccionado.
        */

        $sqlConcepto = "SELECT tipo_movimiento
                        FROM conceptos_movimiento
                        WHERE id_concepto = ?
                        AND es_activo = 1";

        $stmtConcepto = $this->conexion->prepare($sqlConcepto);

        $stmtConcepto->bind_param("i", $id_concepto);

        $stmtConcepto->execute();

        $resultadoConcepto = $stmtConcepto->get_result();

        if ($resultadoConcepto->num_rows === 0) {
            return false;
        }

        $concepto = $resultadoConcepto->fetch_assoc();

        $tipo = $concepto['tipo_movimiento'];

        /*
        * La cantidad que ingresa el usuario siempre
        * se interpreta como cantidad positiva.
        *
        * E = Entrada  →
        * S = Salida   →
        */

        if ($tipo === 'E') {

            $cantidadMovimiento = abs($cantidad);

        } elseif ($tipo === 'S') {

            $cantidadMovimiento = -abs($cantidad);

        } else {

            return false;
        }

        // La cantidad no puede ser cero.

        if ($cantidadMovimiento === 0) {
            return false;
        }

        /*
        * Obtener la hora exacta en la que se registra
        * el movimiento.
        */

        $hora_movimiento = date('H:i:s');

        /*
        * Iniciamos una transacción.
        *
        * Esto hace que el registro del movimiento y la
        * actualización del stock se hagan juntos.
        */

        $this->conexion->begin_transaction();

        try {

            // Obtener stock actual del producto

            $sqlProducto = "SELECT stock
                            FROM productos
                            WHERE id_producto = ?
                            AND es_activo = 1
                            FOR UPDATE";

            $stmtProducto = $this->conexion->prepare($sqlProducto);

            $stmtProducto->bind_param("i", $id_producto);

            $stmtProducto->execute();

            $resultadoProducto = $stmtProducto->get_result();

            if ($resultadoProducto->num_rows === 0) {
                throw new Exception("Producto no encontrado.");
            }

            $producto = $resultadoProducto->fetch_assoc();

            $stockActual = (int) $producto['stock'];

            // Calcular nuevo stock

            $nuevoStock = $stockActual + $cantidadMovimiento;

            // No permitir stock negativo

            if ($nuevoStock < 0) {
                throw new Exception("Stock insuficiente.");
            }

            // Actualizar stock del producto

            $sqlActualizar = "UPDATE productos
                            SET stock = ?
                            WHERE id_producto = ?";

            $stmtActualizar = $this->conexion->prepare($sqlActualizar);

            $stmtActualizar->bind_param(
                "ii",
                $nuevoStock,
                $id_producto
            );

            if (!$stmtActualizar->execute()) {
                throw new Exception("No se pudo actualizar el stock.");
            }

            // Registrar movimiento en el historial

            $sqlMovimiento = "INSERT INTO historial_movimientos
                            (
                                fecha_movimiento,
                                hora_movimiento,
                                cantidad,
                                referencia_ext,
                                comentario,
                                rela_id_productos,
                                rela_id_conceptos,
                                rela_id_usuario
                            )
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmtMovimiento = $this->conexion->prepare($sqlMovimiento);

            $stmtMovimiento->bind_param(
                "ssissiii",
                $fecha_movimiento,
                $hora_movimiento,
                $cantidadMovimiento,
                $referencia_ext,
                $comentario,
                $id_producto,
                $id_concepto,
                $id_usuario
            );

            if (!$stmtMovimiento->execute()) {
                throw new Exception("No se pudo registrar el movimiento.");
            }

            // Confirmar todo

            $this->conexion->commit();

            return true;

        } catch (Exception $e) {

            // Si algo falla, deshacer todo

            $this->conexion->rollback();

            return false;
        }
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