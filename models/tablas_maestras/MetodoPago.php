<?php 
require_once 'C:\Users\areva\.vscode\cyber_core\models/Database.php';

class MetodoPago{
    private mysqli $conexion;

    public function __construct() {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    public function listar(){
        $sql = "SELECT * FROM metodos_pago WHERE es_activo = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $metodos_pago = [];

        while ($fila = $resultado->fetch_assoc()) {
            $metodos_pago[] = $fila;
        }
        return $metodos_pago;
    }

    public function obtenerPorId(int $id){
        $sql = "SELECT * FROM metodos_pago WHERE id_metodo_pago = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }

    public function crear(string $nombre, string $descripcion, int $requiere_autorizacion){
        $sql = "INSERT INTO metodos_pago (nombre, descripcion,requiere_autorizacion) VALUES (?,?,?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('ssi',$nombre,$descripcion,$requiere_autorizacion);
        return $stmt->execute();
    }

    public function editar(string $nombre, string $descripcion, int $requiere_autorizacion, int $id_metodo_pago){
        $sql = "UPDATE metodos_pago SET nombre = ?, descripcion = ?, requiere_autorizacion = ? WHERE id_metodo_pago = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('ssii',$nombre,$descripcion,$requiere_autorizacion,$id_metodo_pago);
        return $stmt->execute();
    }

    public function eliminar(int $id_metodo_pago){
        $sql = "UPDATE metodos_pago SET es_activo = 0 WHERE id_metodo_pago = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id_metodo_pago);
        return $stmt->execute();
    }
}