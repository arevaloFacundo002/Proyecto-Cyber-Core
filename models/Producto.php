 <?php

require_once __DIR__ . '/Database.php';

class Producto
{
    private mysqli $conexion;

    public function __construct()
    {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    // LISTA PRODUCTOS ACTIVOS PARA LA HOME, CON BÚSQUEDA OPCIONAL
    public function listar(string $buscar = "")
    {
        $sql = "SELECT
                    p.*,
                    m.nombre_marca,
                    c.nombre AS categoria
                FROM productos p
                INNER JOIN marcas m
                    ON p.rela_id_marca = m.id_marca
                INNER JOIN categorias c
                    ON p.rela_id_categoria = c.id_categoria
                WHERE p.es_descontinuado = 0
                AND (
                    p.nombre LIKE ?
                    OR p.descripcion LIKE ?
                    OR c.nombre LIKE ?
                    OR m.nombre_marca LIKE ?
                )
                ORDER BY p.id_producto DESC";

        $stmt = $this->conexion->prepare($sql);

        $like = "%" . $buscar . "%";
        $stmt->bind_param("ssss", $like, $like, $like, $like);
        $stmt->execute();

        $resultado = $stmt->get_result();

        $productos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $productos[] = $fila;
        }

        return $productos;
    }

    // BUSCA UN PRODUCTO POR ID PARA LA PÁGINA DE DETALLE (home.php / producto.php)
    public function busqueda_de_producto(int $id_producto)
    {
        $sql = "SELECT
                    p.*,
                    m.nombre_marca,
                    c.nombre AS categoria
                FROM productos p
                INNER JOIN marcas m
                    ON p.rela_id_marca = m.id_marca
                INNER JOIN categorias c
                    ON p.rela_id_categoria = c.id_categoria
                WHERE p.id_producto = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            return $resultado->fetch_assoc();
        }

        return null;
    }

    // TRAE LAS RESEÑAS DE UN PRODUCTO
    public function consultar_reseñas(int $id_producto)
    {
        $sql = "SELECT id_reseña, comentario, calificacion, rela_id_producto
                FROM reseñas
                WHERE rela_id_producto = ?
                ORDER BY id_reseña DESC";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();

        $resultado = $stmt->get_result();

        $reseñas = [];
        while ($fila = $resultado->fetch_assoc()) {
            $reseñas[] = $fila;
        }

        return $reseñas;
    }

    // INSERTA UNA NUEVA RESEÑA (guardar_resena.php)
    public function insertar_resenias(string $comentario, int $calificacion, int $id_producto)
    {
        $sql = "INSERT INTO reseñas (comentario, calificacion, rela_id_producto)
                VALUES (?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("sii", $comentario, $calificacion, $id_producto);

        return $stmt->execute();
    }
}