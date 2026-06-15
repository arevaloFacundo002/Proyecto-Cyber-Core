<?php
require_once 'C:\Users\areva\.vscode\cyber_core\models/Database.php';

class TipoContacto{
    private mysqli $conexion;

    public function __construct() {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    public function listar(){
        $sql = "SELECT * FROM tipos_contacto WHERE es_activo = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $tipos_contacto = [];

        while ($fila = $resultado->fetch_assoc()) {
            $tipos_contacto[] = $fila;
        }
        return $tipos_contacto;
    }

    public function obtenerPorId(int $id){
        $sql = "SELECT * FROM tipos_contacto WHERE id_tipo_contacto = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }


    public function crear(string $descripcion){
        $sql = "INSERT INTO tipos_contacto (descripcion) VALUES (?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('s',$descripcion);
        return $stmt->execute();
    }

    public function editar(string $descripcion, int $id_tipo_contacto){
        $sql = "UPDATE tipos_contacto SET descripcion = ? WHERE id_tipo_contacto = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('si',$descripcion,$id_tipo_contacto);
        return $stmt->execute();
    }

    public function eliminar(int $id_tipo_contacto){
        $sql = "UPDATE tipos_contacto SET es_activo = 0 WHERE id_tipo_contacto = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id_tipo_contacto);
        return $stmt->execute();
    }
}