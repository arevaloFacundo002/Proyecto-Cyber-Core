<?php
require_once 'C:\Users\areva\.vscode\cyber_core\models/Database.php';

class ModeloProducto{
    private mysqli $conexion;

    public function __construct() {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }


    public function listar(){
        $sql = "SELECT * FROM modelos_producto WHERE es_activo = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $modelos = [];

        while ($fila = $resultado->fetch_assoc()) {
            $modelos[] = $fila;
        }
        return $modelos;
    }


    // LISTAR MODELOS DE UNA MARCA ESPECÍFICA
    public function listarPorMarca(int $id_marca)
    {
        $sql = "SELECT *
                FROM modelos_producto
                WHERE rela_id_marca = ?
                AND es_activo = 1
                ORDER BY nombre_modelo ASC";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param("i", $id_marca);

        $stmt->execute();

        $resultado = $stmt->get_result();

        $modelos = [];

        while ($fila = $resultado->fetch_assoc()) {
            $modelos[] = $fila;
        }

        return $modelos;
    }


    public function obtenerPorId(int $id){
        $sql = "SELECT * FROM modelos_producto WHERE id_modelo_producto = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }

    public function crear(string $nombre_modelo, int $rela_id_marca){
        $sql = "INSERT INTO modelos_producto (nombre_modelo, rela_id_marca) VALUES (?,?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('si',$nombre_modelo,$rela_id_marca);
        return $stmt->execute();
    }

    public function editar(string $nombre_modelo, int $rela_id_marca, int $id_modelo){
        $sql = "UPDATE modelos_producto SET nombre_modelo = ?, rela_id_marca = ? WHERE id_modelo_producto = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('sii',$nombre_modelo,$rela_id_marca,$id_modelo);
        return $stmt->execute();      
    }

    public function eliminar(int $id_modelo){
        $sql = "UPDATE modelos_producto SET es_activo = 0 WHERE id_modelo_producto = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id_modelo);
        return $stmt->execute();
    }
    
}