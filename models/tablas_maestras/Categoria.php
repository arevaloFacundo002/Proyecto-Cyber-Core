<?php
require_once 'C:\Users\areva\.vscode\cyber_core\models/Database.php';

class Categoria{
    private mysqli $conexion;

    public function __construct() {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    public function listar(){
        $sql = "SELECT
            id_categoria,
            nombre,
            rela_id_categoria_padre
            FROM categorias
            where es_activo = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $categorias = [];

        while ($fila = $resultado->fetch_assoc()) {
            $categorias[] = $fila;
        }
        return $categorias;
    }

    public function obtenerPorId(int $id_categoria){
        $sql = "SELECT * FROM categorias WHERE id_categoria = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id_categoria);
        $stmt->execute();
        $resultado = $stmt->get_result();

        return $resultado->fetch_assoc();
    }


    public function crear(string $nombre, string $descripcion, ?int $rela_id_categoria_padre){
        $sql = "INSERT INTO categorias (nombre, descripcion,rela_id_categoria_padre) VALUES (?,?,?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('ssi',$nombre,$descripcion,$rela_id_categoria_padre);
        return $stmt->execute();
    }

    public function editar(string $nombre, string $descripcion, int $rela_id_categoria_padre, int $id_categoria){
        $sql = "UPDATE categorias SET nombre = ?, descripcion = ?, rela_id_categoria_padre = ? WHERE id_categoria = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('ssii',$nombre,$descripcion,$rela_id_categoria_padre,$id_categoria);
        return $stmt->execute();
    }

    public function eliminar(int $id_categoria){
        $sql = "UPDATE categorias SET es_activo = 0 WHERE id_categoria = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id_categoria);
        return $stmt->execute();
    }

}
