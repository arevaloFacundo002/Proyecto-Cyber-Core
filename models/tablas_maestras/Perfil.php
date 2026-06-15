<?php
require_once 'C:\Users\areva\.vscode\cyber_core\models/Database.php';

class Perfil{
    private mysqli $conexion;

    public function __construct() {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    public function listar(){
        $sql = "SELECT * FROM perfiles WHERE es_activo = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $perfiles = [];

        while ($fila = $resultado->fetch_assoc()) {
            $perfiles[] = $fila;
        }
        return $perfiles;
    }

    public function obtenerPorId(int $id){
        $sql = "SELECT * FROM perfiles WHERE id_perfil = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        return $resultado->fetch_assoc();
    }

    public function crear(string $nombre){
        $sql = "INSERT INTO perfiles (nombre_perfil) VALUES (?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('s',$nombre);
        return $stmt->execute();
    }

    public function editar(string $nombre, int $id_perfil){
        $sql = "UPDATE perfiles SET nombre_perfil = ? WHERE id_perfil = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('si',$nombre,$id_perfil);
        return $stmt->execute();
    }

    public function eliminar(int $id_perfil){
        $sql = "UPDATE perfiles SET es_activo = 0 WHERE id_perfil = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id_perfil);
        return $stmt->execute();
    }
}