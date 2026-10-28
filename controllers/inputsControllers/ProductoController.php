<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Solo administrador y empleados pueden gestionar productos
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['rol'] ?? '', ['administrador', 'empleado'], true)) {
    header("Location: ../../home.php");
    exit();
}

require_once __DIR__ . '/../../models/inputs/Producto.php';
require_once __DIR__ . '/../../models/ProductoImagen.php';

class ProductoController
{
    private Producto $producto;

    public function __construct()
    {
        $this->producto = new Producto();
    }


    // Marca la foto principal elegida en el formulario.
    // El valor puede ser el id de una foto ya guardada ("12") o una foto recién subida ("nueva_0", "nueva_1"...).
    private function aplicarPrincipal(ProductoImagen $galeria, int $id_producto, array $imgs): void
    {
        $elegida = (string) ($_POST['imagen_principal'] ?? '');

        if (preg_match('/^nueva_(\d+)$/', $elegida, $m)) {
            $indice = (int) $m[1];
            if (!empty($imgs['ids'][$indice])) {
                $galeria->marcarPrincipal($id_producto, (int) $imgs['ids'][$indice]);
            }
        } elseif (ctype_digit($elegida) && (int) $elegida > 0) {
            $galeria->marcarPrincipal($id_producto, (int) $elegida);
        }

        $galeria->normalizarPrincipal($id_producto);
    }


    // CREAR PRODUCTO
    public function crear()
    {
        $codigo = $_POST['codigo'];
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $imagen_url = null; // la(s) imagen(es) se guardan en la galería, más abajo
        $precio = (float) $_POST['precio'];
        $peso_envio = (float) $_POST['peso_envio'];
        $rela_id_categoria = (int) $_POST['rela_id_categoria'];
        $rela_id_marca = (int) $_POST['rela_id_marca'];

        // Si no seleccionó modelo
        $rela_id_modelo_producto = !empty($_POST['rela_id_modelo_producto'])
            ? (int) $_POST['rela_id_modelo_producto']
            : null;


        $resultado = $this->producto->crear(
            $codigo,
            $nombre,
            $descripcion,
            $imagen_url,
            $precio,
            $peso_envio,
            $rela_id_categoria,
            $rela_id_marca,
            $rela_id_modelo_producto
        );


        if ($resultado) {
            $id_nuevo = $this->producto->ultimoId();

            // Fotos del producto (si se subieron)
            $galeria = new ProductoImagen();
            $imgs = $galeria->guardarSubidas($id_nuevo, $_FILES['imagenes'] ?? []);
            $this->aplicarPrincipal($galeria, $id_nuevo, $imgs);

            if (!empty($imgs['errores'])) {
                // El producto se creó; se vuelve a su edición para mostrar qué foto falló
                header("Location: ../../views/productos/editarProducto.php?id=" . $id_nuevo
                    . "&aviso=" . urlencode(implode(' ', $imgs['errores'])));
            } else {
                header("Location: ../../views/productos/listarProducto.php?mensaje=creado");
            }

        } else {
            header(
                "Location: ../../views/productos/listarProducto.php?error=1");
        }
        exit();
    }


    // EDITAR PRODUCTO
    public function editar()
    {
        $id_producto = (int) $_POST['id_producto'];

        $codigo = $_POST['codigo'];
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $actual = $this->producto->obtenerPorId($id_producto);
        $imagen_url = $actual['imagen_url'] ?? null; // se actualiza después con la galería
        $precio = (float) $_POST['precio'];
        $es_descontinuado = (int) $_POST['es_descontinuado'];
        $peso_envio = (float) $_POST['peso_envio'];
        $rela_id_categoria = (int) $_POST['rela_id_categoria'];
        $rela_id_marca = (int) $_POST['rela_id_marca'];

        $rela_id_modelo_producto = !empty($_POST['rela_id_modelo_producto'])
            ? (int) $_POST['rela_id_modelo_producto']
            : null;


        $resultado = $this->producto->editar(
            $codigo,
            $nombre,
            $descripcion,
            $imagen_url,
            $precio,
            $es_descontinuado,
            $peso_envio,
            $rela_id_categoria,
            $rela_id_marca,
            $rela_id_modelo_producto,
            $id_producto
        );


        if ($resultado) {
            $galeria = new ProductoImagen();

            if (!empty($_POST['eliminar_imagenes']) && is_array($_POST['eliminar_imagenes'])) {
                $galeria->eliminar($id_producto, $_POST['eliminar_imagenes']);
            }

            $imgs = $galeria->guardarSubidas($id_producto, $_FILES['imagenes'] ?? []);

            $this->aplicarPrincipal($galeria, $id_producto, $imgs);

            if (!empty($imgs['errores'])) {
                header("Location: ../../views/productos/editarProducto.php?id=" . $id_producto
                    . "&aviso=" . urlencode(implode(' ', $imgs['errores'])));
            } else {
                header("Location: ../../views/productos/listarProducto.php?mensaje=editado");
            }

        } else {
            header(
                "Location: ../../views/productos/listarProducto.php?error=1");
        }
        exit();
    }


    // BAJA LÓGICA
    public function eliminar()
    {
        $id_producto = (int) $_GET['id'];

        $resultado = $this->producto->eliminar($id_producto);

        if ($resultado) {
            header(
                "Location: ../../views/productos/listarProducto.php?mensaje=eliminado");

        } else {
            header(
                "Location: ../../views/productos/listarProducto.php?error=1");
        }
        exit();
    }


    // REACTIVAR PRODUCTO
    public function activar()
    {
        $id_producto = (int) $_GET['id'];

        $resultado = $this->producto->activar($id_producto);

        if ($resultado) {
            header(
                "Location: ../../views/productos/listarProducto.php?mensaje=activado");

        } else {
            header(
                "Location: ../../views/productos/listarProducto.php?error=1");
        }
        exit();
    }
}


$productoController = new ProductoController();

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    case 'crear':
        $productoController->crear();
        break;

    case 'editar':
        $productoController->editar();
        break;

    case 'eliminar':
        $productoController->eliminar();
        break;

    case 'activar':
        $productoController->activar();
        break;

    default:
        header(
            "Location: ../../views/productos/listarProducto.php"
        );
        exit();
}