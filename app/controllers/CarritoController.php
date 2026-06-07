<?php
/**
 * Maneja el carrito de compras (guardado en $_SESSION['carrito']).
 */
class CarritoController extends Controller
{
    private Producto $productoModel;

    public function __construct(string $base = '')
    {
        parent::__construct($base);
        $this->productoModel = new Producto();
    }

    // carrito/carrito.php
    public function index(): void
    {
        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        $carrito = $_SESSION['carrito'];
        $rol = $_SESSION['rol'] ?? null;

        $this->render('carrito/index', [
            'carrito' => $carrito,
            'rol' => $rol,
        ]);
    }

    // carrito/agregar_carrito.php
    public function agregar(): void
    {
        // Validar ID
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            die("Producto inválido.");
        }

        $id = intval($_GET['id']);

        // si no esta el login, redirigir
        if (!isset($_SESSION['usuario'])) {
            $this->redirect('../login.php?msg=Debes iniciar sesión');
        }

        // consultar producto y stock
        $producto = $this->productoModel->consulta_producto_stock($id);
        if (!$producto) {
            die("Producto no encontrado.");
        }

        // validar stock
        if ($producto['stock'] <= 0) {
            $this->redirect("../producto.php?id=$id&error=sin_stock");
        }

        // crear carrito si no existe
        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        // si el producto ya esta -> aumentar cantidad
        if (isset($_SESSION['carrito'][$id])) {
            // validar que no supere el stock
            if ($_SESSION['carrito'][$id]['cantidad'] + 1 > $producto['stock']) {
                $this->redirect("../producto.php?id=$id&error=stock_limit");
            }

            $_SESSION['carrito'][$id]['cantidad']++;
        } else {
            // agregar nuevo producto al carrito
            $_SESSION['carrito'][$id] = [
                'id' => $producto['id_producto'],
                'nombre' => $producto['nombre'],
                'precio' => $producto['precio'],
                'imagen' => $producto['imagen_url'],
                'cantidad' => 1,
                'stock' => $producto['stock']
            ];
        }

        $this->redirect('../carrito/carrito.php?ok=1');
    }

    // carrito/eliminar_item.php
    public function eliminar(): void
    {
        if (!isset($_GET['id'])) {
            $this->redirect('carrito.php');
        }

        $id = intval($_GET['id']);

        if (isset($_SESSION['carrito'][$id])) {
            unset($_SESSION['carrito'][$id]);
        }

        if (empty($_SESSION['carrito'])) {
            unset($_SESSION['carrito']);
        }

        $this->redirect('carrito.php?deleted=1');
    }

    // carrito/modificar_carrito.php
    public function modificar(): void
    {
        if (!isset($_POST['id']) || !isset($_POST['cantidad'])) {
            $this->redirect('carrito.php');
        }

        $id = intval($_POST['id']);
        $cantidad = intval($_POST['cantidad']);

        if (!isset($_SESSION['carrito'][$id])) {
            $this->redirect('carrito.php');
        }

        if ($cantidad < 1) {
            $cantidad = 1;
        }

        $stockDisponible = $_SESSION['carrito'][$id]['stock'];

        if ($cantidad > $stockDisponible) {
            $cantidad = $stockDisponible;
        }

        $_SESSION['carrito'][$id]['cantidad'] = $cantidad;

        $this->redirect('carrito.php?updated=1');
    }
}
