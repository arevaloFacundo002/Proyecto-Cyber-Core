<?php
require_once 'C:\Users\areva\.vscode\cyber_core\models/Database.php';

class CondicionIva{
    private mysqli $conexion;

    public function __construct() {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    public function listar(){
        $sql = "SELECT * FROM condiciones_iva WHERE es_activo = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $condiciones_iva = [];

        while ($fila = $resultado->fetch_assoc()) {
            $condiciones_iva[] = $fila;
        }
        return $condiciones_iva;
    }

    public function obtenerPorId(int $id){
        $sql = "SELECT * FROM condiciones_iva WHERE id_condicion_iva = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }

    public function crear(string $nombre, string $descripcion, float $porcentaje_iva){
        $sql = "INSERT INTO condiciones_iva (nombre, descripcion, porcentaje_iva) VALUES (?,?,?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('ssi',$nombre,$descripcion,$porcentaje_iva);
        return $stmt->execute();
    }

    public function editar(string $nombre, string $descripcion, float $porcentaje_iva, int $id_condicion_iva){
        $sql = "UPDATE condiciones_iva SET nombre = ?, descripcion = ?, porcentaje_iva = ? WHERE id_condicion_iva = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('ssii',$nombre,$descripcion,$porcentaje_iva,$id_condicion_iva);
        return $stmt->execute();
    }

    public function eliminar(int $id_condicion_iva){
        $sql = "UPDATE condiciones_iva SET es_activo = 0 WHERE id_condicion_iva = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id_condicion_iva);
        return $stmt->execute();
    }

}