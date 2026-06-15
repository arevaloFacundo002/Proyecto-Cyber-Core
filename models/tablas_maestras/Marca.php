<?php
require_once 'C:\Users\areva\.vscode\cyber_core\models/Database.php';

class Marca{
    private mysqli $conexion;

    public function __construct() {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    public function listar(){
        $sql = "SELECT * FROM marcas WHERE es_activo =1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $marcas = [];

        while ($fila = $resultado->fetch_assoc()) {
            $marcas[] = $fila;
        }
        return $marcas;
    }

    public function obtenerPorId(int $id){
        $sql = "SELECT * FROM marcas WHERE id_marca = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }

    public function crear(string $nombre,string $nombre_corto,?string $logo_url,string $sitio_web){
        $sql = "INSERT INTO marcas (nombre_marca, nombre_corto,logo_url,sitio_web) VALUES (?,?,?,?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('ssss',$nombre,$nombre_corto,$logo_url,$sitio_web);
        return $stmt->execute();
    }

    public function editar(string $nombre,string $nombre_corto,?string $logo_url,string $sitio_web,int $id_marca){
        $sql = "UPDATE marcas SET nombre_marca = ?, nombre_corto = ?, logo_url = ?, sitio_web = ? WHERE id_marca = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('ssssi',$nombre,$nombre_corto,$logo_url,$sitio_web,$id_marca);
        return $stmt->execute();
    }

    public function eliminar(int $id_marca){
        $sql = "UPDATE marcas SET es_activo = 0 WHERE id_marca = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id_marca);
        return $stmt->execute();
    }
    
}