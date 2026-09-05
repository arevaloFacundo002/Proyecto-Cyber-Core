<?php

require_once 'C:\Users\areva\.vscode\cyber_core\models/Database.php';

class Proveedor
{
    private mysqli $conexion;

    public function __construct()
    {
        $db = new Database();
        $this->conexion = $db->getConexion();
    }


    // LISTAR y paginado
    public function listar(
        string $busqueda = "",
        string $estado = "activo",
        int $limite = 6,
        int $offset = 0
    ) {

        $sql = "SELECT *
                FROM proveedores
                WHERE (
                    razon_social LIKE ?
                    OR persona_contacto LIKE ?
                    OR email LIKE ?
                    OR telefono LIKE ?
                )";

        // FILTRO DE ESTADO
        if ($estado === "activo") {
            $sql .= " AND es_activo = 1";
        } elseif ($estado === "inactivo") {
            $sql .= " AND es_activo = 0";
        }
        // Si es "todos", no agregamos ninguna condición

        $sql .= " ORDER BY id_proveedor DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->conexion->prepare($sql);

        $busqueda = "%" . $busqueda . "%";

        $stmt->bind_param(
            "ssssii",
            $busqueda,
            $busqueda,
            $busqueda,
            $busqueda,
            $limite,
            $offset
        );

        $stmt->execute();

        $resultado = $stmt->get_result();

        $proveedores = [];

        while ($fila = $resultado->fetch_assoc()) {
            $proveedores[] = $fila;
        }

        return $proveedores;
    }


    // OBTENER POR ID
    public function obtenerPorId(int $id_proveedor)
    {
        $sql = "SELECT *
                FROM proveedores
                WHERE id_proveedor = ?";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param("i", $id_proveedor);

        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            return $resultado->fetch_assoc();
        }

        return null;
    }


    // CREAR
    public function crear(
        string $razon_social,
        string $persona_contacto,
        string $email,
        string $direccion,
        string $telefono
    ) {

        $sql = "INSERT INTO proveedores
                (
                    razon_social,
                    persona_contacto,
                    email,
                    direccion,
                    telefono,
                )
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param(
            "sssss",
            $razon_social,
            $persona_contacto,
            $email,
            $direccion,
            $telefono,
        );

        return $stmt->execute();
    }


    // EDITAR
    public function editar(
        string $razon_social,
        string $persona_contacto,
        string $email,
        string $direccion,
        string $telefono,
        int $id_proveedor
    ) {

        $sql = "UPDATE proveedores
                SET
                    razon_social = ?,
                    persona_contacto = ?,
                    email = ?,
                    direccion = ?,
                    telefono = ?
                WHERE id_proveedor = ?";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param(
            "sssssi",
            $razon_social,
            $persona_contacto,
            $email,
            $direccion,
            $telefono,
            $id_proveedor
        );

        return $stmt->execute();
    }


    // BAJA LÓGICA
    public function eliminar(int $id_proveedor)
    {
        $sql = "UPDATE proveedores
                SET es_activo = 0
                WHERE id_proveedor = ?";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param("i", $id_proveedor);

        return $stmt->execute();
    }


        // ACTIVAR PROVEEDOR
    public function activar(int $id_proveedor)
    {
        $sql = "UPDATE proveedores
                SET es_activo = 1
                WHERE id_proveedor = ?";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param(
            "i",
            $id_proveedor
        );

        return $stmt->execute();
    }


    //El metodo para contar los registros y saber cuantas paginas hay
    public function contar(
        string $busqueda = "",
        string $estado = "activo"
    ) {

        $sql = "SELECT COUNT(*) AS total
                FROM proveedores
                WHERE (
                    razon_social LIKE ?
                    OR persona_contacto LIKE ?  
                    OR email LIKE ?
                    OR telefono LIKE ?
                )";

        if ($estado === "activo") {
            $sql .= " AND es_activo = 1";
        } elseif ($estado === "inactivo") {
            $sql .= " AND es_activo = 0";
        }

        $stmt = $this->conexion->prepare($sql);

        $busqueda = "%" . $busqueda . "%";

        $stmt->bind_param(
            "ssss",
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