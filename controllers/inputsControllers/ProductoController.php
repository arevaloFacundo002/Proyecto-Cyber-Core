<?php

require_once 'C:\Users\areva\.vscode\cyber_core\models/inputs/Producto.php';

class ProductoController
{
    private Producto $producto;

    public function __construct()
    {
        $this->producto = new Producto();
    }


    // CREAR PRODUCTO
    public function crear()
    {
        $codigo = $_POST['codigo'];
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $imagen_url = $_POST['imagen_url'] ?? null;
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
            header(
                "Location: ../../views/productos/listarProducto.php?mensaje=creado");

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
        $imagen_url = $_POST['imagen_url'] ?? null;
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
            header(
                "Location: ../../views/productos/listarProducto.php?mensaje=editado");

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