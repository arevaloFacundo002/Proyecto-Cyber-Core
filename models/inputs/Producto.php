<?php

require_once 'C:\Users\areva\.vscode\cyber_core\models/Database.php';

class Producto
{
    private mysqli $conexion;

    public function __construct()
    {
        $db = new Database();
        $this->conexion = $db->getConexion();
    }

    // LISTAR PRODUCTOS
    public function listar(
        string $busqueda = "",
        string $estado = "activos",
        int $limite = 6,
        int $offset = 0
    ) {
        $condicionEstado = "";

        if ($estado === "activos") {
            $condicionEstado = "p.es_activo = 1";
        } elseif ($estado === "inactivos") {
            $condicionEstado = "p.es_activo = 0";
        } else {
            $condicionEstado = "1=1";
        }

        $sql = "SELECT
                    p.*,
                    c.nombre AS nombre_categoria,
                    m.nombre_marca,
                    mp.nombre_modelo
                FROM productos p
                INNER JOIN categorias c
                    ON p.rela_id_categoria = c.id_categoria
                INNER JOIN marcas m
                    ON p.rela_id_marca = m.id_marca
                LEFT JOIN modelos_producto mp
                    ON p.rela_id_modelo_producto = mp.id_modelo_producto
                WHERE $condicionEstado
                AND (
                    p.codigo LIKE ?
                    OR p.nombre LIKE ?
                    OR p.descripcion LIKE ?
                    OR c.nombre LIKE ?
                    OR m.nombre_marca LIKE ?
                )
                ORDER BY p.id_producto DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->conexion->prepare($sql);

        $busqueda = "%" . $busqueda . "%";

        $stmt->bind_param(
            "sssssii",
            $busqueda,
            $busqueda,
            $busqueda,
            $busqueda,
            $busqueda,
            $limite,
            $offset
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        $productos = [];

        while ($fila = $resultado->fetch_assoc()) {
            $productos[] = $fila;
        }

        return $productos;
    }


    // OBTENER PRODUCTO POR ID
    public function obtenerPorId(int $id_producto)
    {
        $sql = "SELECT *
                FROM productos
                WHERE id_producto = ?";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param("i", $id_producto);

        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            return $resultado->fetch_assoc();
        }

        return null;
    }


    // CREAR PRODUCTO
    public function crear(
        string $codigo,
        string $nombre,
        string $descripcion,
        ?string $imagen_url,
        float $precio,
        float $peso_envio,
        int $rela_id_categoria,
        int $rela_id_marca,
        ?int $rela_id_modelo_producto
    ) {
        $sql = "INSERT INTO productos
                (
                    codigo,
                    nombre,
                    descripcion,
                    imagen_url,
                    precio,
                    peso_envio,
                    rela_id_categoria,
                    rela_id_marca,
                    rela_id_modelo_producto
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param(
            "ssssddiii",
            $codigo,
            $nombre,
            $descripcion,
            $imagen_url,
            $precio,
            $peso_envio,
            $rela_id_categoria,
            $rela_id_marca,
            $rela_id_modelo_producto
        );

        return $stmt->execute();
    }


    // EDITAR PRODUCTO
    public function editar(
        string $codigo,
        string $nombre,
        string $descripcion,
        ?string $imagen_url,
        float $precio,
        int $es_descontinuado,
        float $peso_envio,
        int $rela_id_categoria,
        int $rela_id_marca,
        ?int $rela_id_modelo_producto,
        int $id_producto
    ) {
        $sql = "UPDATE productos
                SET
                    codigo = ?,
                    nombre = ?,
                    descripcion = ?,
                    imagen_url = ?,
                    precio = ?,
                    es_descontinuado = ?,
                    peso_envio = ?,
                    rela_id_categoria = ?,
                    rela_id_marca = ?,
                    rela_id_modelo_producto = ?
                WHERE id_producto = ?";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param(
            "ssssdidiiii",
            $codigo,
            $nombre,
            $descripcion,
            $imagen_url,
            $precio,
            $es_descontinuado,
            $peso_envio,
            $rela_id_categoria,
            $rela_id_marca,
            $rela_id_modelo_producto,
            $id_producto
        );

        return $stmt->execute();
    }


    // BAJA LÓGICA
    public function eliminar(int $id_producto)
    {
        $sql = "UPDATE productos
                SET es_activo = 0
                WHERE id_producto = ?";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param("i", $id_producto);

        return $stmt->execute();
    }


    // REACTIVAR PRODUCTO
    public function activar(int $id_producto)
    {
        $sql = "UPDATE productos
                SET es_activo = 1
                WHERE id_producto = ?";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param("i", $id_producto);

        return $stmt->execute();
    }


    // CONTAR PRODUCTOS
    public function contar(
        string $busqueda = "",
        string $estado = "activos"
    ) {
        if ($estado === "activos") {
            $condicionEstado = "p.es_activo = 1";
        } elseif ($estado === "inactivos") {
            $condicionEstado = "p.es_activo = 0";
        } else {
            $condicionEstado = "1=1";
        }

        $sql = "SELECT COUNT(*) AS total
                FROM productos p
                INNER JOIN categorias c
                    ON p.rela_id_categoria = c.id_categoria
                INNER JOIN marcas m
                    ON p.rela_id_marca = m.id_marca
                WHERE $condicionEstado
                AND (
                    p.codigo LIKE ?
                    OR p.nombre LIKE ?
                    OR p.descripcion LIKE ?
                    OR c.nombre LIKE ?
                    OR m.nombre_marca LIKE ?
                )";

        $stmt = $this->conexion->prepare($sql);

        $busqueda = "%" . $busqueda . "%";

        $stmt->bind_param(
            "sssss",
            $busqueda,
            $busqueda,
            $busqueda,
            $busqueda,
            $busqueda
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        $fila = $resultado->fetch_assoc();

        return $fila['total'];
    }
}