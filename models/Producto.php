<?php
//este modelo se encarga de las responsabilides relacionadas con productos y resenias
require_once 'database.php';

class Producto {
    private mysqli $conexion;  

    public function __construct() {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    function listar_productos(string $buscar){
        $sql = "SELECT p.id_producto, p.nombre, p.precio, p.imagen_url,
                p.stock,
                c.nombre AS categoria,
                m.nombre_marca
            FROM productos p
            INNER JOIN categorias c ON p.rela_id_categoria = c.id_categoria
            INNER JOIN marcas m ON p.rela_id_marca = m.id_marca
            WHERE p.nombre LIKE ? OR c.nombre LIKE ? OR m.nombre_marca LIKE ?
            ORDER BY p.id_producto DESC";

        $stmt = $this->conexion->prepare($sql);
        $param = "%$buscar%";
        $stmt->bind_param("sss", $param, $param, $param);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $productos = [];

        while ($fila = $resultado->fetch_assoc()) {
            $productos[] = $fila;
        }
        return $productos;
    }

    function busqueda_de_producto(int $id){
        $sql = "SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.imagen_url,
           p.stock,
           c.nombre AS categoria,
           m.nombre_marca
        FROM productos p
        INNER JOIN categorias c ON p.rela_id_categoria = c.id_categoria
        INNER JOIN marcas m ON p.rela_id_marca = m.id_marca
        WHERE p.id_producto = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($producto = $resultado->fetch_assoc()) {
            return $producto;
        }
        return null;
    }

    function consulta_producto_stock(int $id){
        $sql = "SELECT id_producto, nombre, precio, stock, imagen_url 
        FROM productos 
        WHERE id_producto = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if($producto = $resultado->fetch_assoc()){ 
            return $producto;
        }
        return null;
    }

    //Parte de resenias
    function consultar_reseñas(int $id){
        $sqlreseñas = "SELECT comentario, calificacion 
                    FROM reseñas 
                    WHERE rela_id_producto = ?
                    ORDER BY id_reseña DESC";

        $stmt = $this->conexion->prepare($sqlreseñas);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $reseñas = [];

        while ($fila = $resultado->fetch_assoc()) {
            $reseñas[] = $fila;
        }
        return $reseñas;
    }

    function insertar_resenias(string $comentario, int $calificacion, int $id_producto){
        $sql = "INSERT INTO reseñas(comentario,calificacion,rela_id_producto)
            VALUES(?,?,?) ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('sii',$comentario, $calificacion,$id_producto);
        $resultado = $stmt->execute();
        return $resultado;
    }
}