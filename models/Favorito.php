<?php

require_once __DIR__ . '/Database.php';

class Favorito
{
    private mysqli $conexion;

    public function __construct()
    {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    // IDS DE PRODUCTOS FAVORITOS DEL USUARIO
    public function ids(int $id_usuario): array
    {
        $stmt = $this->conexion->prepare(
            "SELECT rela_id_productos FROM favoritos WHERE rela_id_usuario = ?"
        );
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $res = $stmt->get_result();
        $ids = [];
        while ($f = $res->fetch_assoc()) {
            $ids[] = (int) $f['rela_id_productos'];
        }
        return $ids;
    }

    // AGREGA O QUITA. DEVUELVE true SI QUEDO COMO FAVORITO
    public function alternar(int $id_usuario, int $id_producto): ?bool
    {
        $stmt = $this->conexion->prepare(
            "SELECT id_producto FROM productos WHERE id_producto = ? AND es_descontinuado = 0"
        );
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            return null;
        }

        $stmt = $this->conexion->prepare(
            "DELETE FROM favoritos WHERE rela_id_usuario = ? AND rela_id_productos = ?"
        );
        $stmt->bind_param("ii", $id_usuario, $id_producto);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            return false;
        }

        $stmt = $this->conexion->prepare(
            "INSERT IGNORE INTO favoritos (rela_id_usuario, rela_id_productos) VALUES (?, ?)"
        );
        $stmt->bind_param("ii", $id_usuario, $id_producto);
        $stmt->execute();
        return true;
    }

    public function contar(int $id_usuario): int
    {
        $stmt = $this->conexion->prepare(
            "SELECT COUNT(*) AS n FROM favoritos WHERE rela_id_usuario = ?"
        );
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        return (int) $stmt->get_result()->fetch_assoc()['n'];
    }

    // PRODUCTOS FAVORITOS CON SUS DATOS
    public function listar(int $id_usuario): array
    {
        $stmt = $this->conexion->prepare(
            "SELECT p.*, m.nombre_marca, c.nombre AS categoria
             FROM favoritos f
             INNER JOIN productos p ON p.id_producto = f.rela_id_productos
             INNER JOIN marcas m ON p.rela_id_marca = m.id_marca
             INNER JOIN categorias c ON p.rela_id_categoria = c.id_categoria
             WHERE f.rela_id_usuario = ? AND p.es_descontinuado = 0
             ORDER BY f.fecha_agregado DESC"
        );
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $res = $stmt->get_result();
        $lista = [];
        while ($f = $res->fetch_assoc()) {
            $lista[] = $f;
        }
        return $lista;
    }
}