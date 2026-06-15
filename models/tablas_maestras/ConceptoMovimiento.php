<?php
require_once 'C:\Users\areva\.vscode\cyber_core\models/Database.php';

class ConceptoMovimiento{
    private mysqli $conexion;

    public function __construct() {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    public function listar(){
        $sql = "SELECT * FROM conceptos_movimiento WHERE es_activo = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $conceptos_movimiento = [];

        while ($fila = $resultado->fetch_assoc()) {
            $conceptos_movimiento[] = $fila;
        }
        return $conceptos_movimiento;
    }

    public function obtenerPorId(int $id){
        $sql = "SELECT * FROM conceptos_movimiento WHERE id_concepto = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }

    public function crear(string $descripcion, string $tipo_movimiento){
        $sql = "INSERT INTO conceptos_movimiento (descripcion, tipo_movimiento) VALUES (?,?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('ss',$descripcion,$tipo_movimiento);
        return $stmt->execute();
    }

    public function editar(string $descripcion, string $tipo_movimiento, int $id_concepto){
        $sql = "UPDATE conceptos_movimiento SET descripcion = ?, tipo_movimiento = ? WHERE id_concepto = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('ssi',$descripcion,$tipo_movimiento,$id_concepto);
        return $stmt->execute();
    }

    public function eliminar(int $id_concepto){
        $sql = "UPDATE conceptos_movimiento SET es_activo = 0 WHERE id_concepto = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id_concepto);
        return $stmt->execute();
    }

}