<?php

require_once __DIR__ . '/Database.php';

class FiltroProducto
{
    private mysqli $conexion;

    const ORDENES = [
        'nuevos'  => 'p.id_producto DESC',
        'menor'   => 'p.precio ASC',
        'mayor'   => 'p.precio DESC',
        'nombre'  => 'p.nombre ASC',
    ];

    public function __construct()
    {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    public function categorias(): array
    {
        $r = $this->conexion->query(
            "SELECT c.id_categoria, c.nombre FROM categorias c
             WHERE c.es_activo = 1
             AND EXISTS (SELECT 1 FROM productos p WHERE p.rela_id_categoria = c.id_categoria AND p.es_descontinuado = 0)
             ORDER BY c.nombre"
        );
        return $r->fetch_all(MYSQLI_ASSOC);
    }

    public function marcas(): array
    {
        $r = $this->conexion->query(
            "SELECT m.id_marca, m.nombre_marca FROM marcas m
             WHERE m.es_activo = 1
             AND EXISTS (SELECT 1 FROM productos p WHERE p.rela_id_marca = m.id_marca AND p.es_descontinuado = 0)
             ORDER BY m.nombre_marca"
        );
        return $r->fetch_all(MYSQLI_ASSOC);
    }

    // BUSCA Y FILTRA PRODUCTOS ACTIVOS
    public function listar(string $buscar, array $f): array
    {
        $sql = "SELECT p.*, m.nombre_marca, c.nombre AS categoria
                FROM productos p
                INNER JOIN marcas m ON p.rela_id_marca = m.id_marca
                INNER JOIN categorias c ON p.rela_id_categoria = c.id_categoria
                WHERE p.es_descontinuado = 0
                AND (p.nombre LIKE ? OR p.codigo LIKE ? OR p.descripcion LIKE ?
                     OR c.nombre LIKE ? OR m.nombre_marca LIKE ?)";

        $like  = "%" . $buscar . "%";
        $tipos = "sssss";
        $vals  = [$like, $like, $like, $like, $like];

        if (!empty($f['categoria'])) {
            $sql .= " AND p.rela_id_categoria = ?";
            $tipos .= "i";
            $vals[] = (int) $f['categoria'];
        }
        if (!empty($f['marca'])) {
            $sql .= " AND p.rela_id_marca = ?";
            $tipos .= "i";
            $vals[] = (int) $f['marca'];
        }
        if ($f['precio_min'] !== null) {
            $sql .= " AND p.precio >= ?";
            $tipos .= "d";
            $vals[] = (float) $f['precio_min'];
        }
        if ($f['precio_max'] !== null) {
            $sql .= " AND p.precio <= ?";
            $tipos .= "d";
            $vals[] = (float) $f['precio_max'];
        }
        if (!empty($f['solo_stock'])) {
            $sql .= " AND p.stock > 0";
        }

        $orden = self::ORDENES[$f['orden']] ?? self::ORDENES['nuevos'];
        $sql .= " ORDER BY " . $orden;

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param($tipos, ...$vals);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}