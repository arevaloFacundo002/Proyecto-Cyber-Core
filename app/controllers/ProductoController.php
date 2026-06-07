<?php
/**
 * Maneja el catalogo (home), el detalle de un producto y sus resenias.
 */
class ProductoController extends Controller
{
    private Producto $productoModel;

    public function __construct(string $base = '')
    {
        parent::__construct($base);
        $this->productoModel = new Producto();
    }

    // home.php (catalogo, requiere sesion)
    public function home(): void
    {
        $this->requiereLogin();

        $buscar = $_GET['buscar'] ?? "";
        $rol = $_SESSION['rol'];

        $productos = $this->productoModel->listar_productos($buscar);

        $this->render('productos/home', [
            'buscar' => $buscar,
            'rol' => $rol,
            'productos' => $productos,
        ]);
    }

    // producto.php (detalle + resenias)
    public function detalle(): void
    {
        // Validar ID
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            die("Producto no encontrado.");
        }

        $rol = $_SESSION['rol'] ?? null;
        $id = intval($_GET['id']);

        $producto = $this->productoModel->busqueda_de_producto($id);
        if (!$producto) {
            die("Producto no encontrado.");
        }

        $resenas = $this->productoModel->consultar_reseñas($id);

        $this->render('productos/detalle', [
            'rol' => $rol,
            'id' => $id,
            'producto' => $producto,
            'resenas' => $resenas,
        ]);
    }

    // guardar_resena.php
    public function guardarResena(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // verificar usuario
            if (!isset($_SESSION['id_usuario'])) {
                die('Tiene que iniciar sesion para comentar.');
            }

            $id_usuario = $_SESSION['id_usuario'];

            $id_producto = $_POST['id_producto'] ?? null;
            $comentario = trim($_POST['comentario'] ?? null);
            $calificacion = $_POST['calificacion'] ?? null;

            // validaciones
            if (!$id_producto || !is_numeric($id_producto)) {
                die('Producto no valido');
            }

            $comentario = trim($comentario);

            if (!$comentario) {
                die('El comentario no puede estar vacio');
            }

            if ($this->productoModel->insertar_resenias($comentario, $calificacion, $id_producto)) {
                $this->redirect('producto.php?id=' . $id_producto);
            }
        }
    }
}
