<?php
require_once 'C:\Users\areva\.vscode\cyber_core\models/tablas_maestras/Categoria.php';

class CategoriaController{
    private Categoria $categoria;

    public function __construct() {
        $this->categoria = new Categoria();
    }

    public function crear(){
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $rela_id_categoria_padre = $_POST['categoria_padre'];

        if($rela_id_categoria_padre == ""){
            $rela_id_categoria_padre = null;
        }

        $resultado = $this->categoria->crear($nombre,$descripcion,$rela_id_categoria_padre);

        if($resultado){
            header('Location: ../../views/tablas_maestras/categorias/listarCategoria.php?mensaje=creado');
        }else{
            header('Location: ../../views/tablas_maestras/categorias/listarCategoria.php?error=1');
        }
        exit();
    }

    public function editar(){
        $id_categoria = $_POST['id_categoria'];
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $rela_id_categoria_padre = $_POST['categoria_padre'];

        if($rela_id_categoria_padre == ""){
            $rela_id_categoria_padre = null;
        }

        $resultado = $this->categoria->editar($nombre,$descripcion,$rela_id_categoria_padre,$id_categoria);

        if($resultado){
            header('Location: ../../views/tablas_maestras/categorias/listarCategoria.php?mensaje=editado');
        }else{
            header('Location: ../../views/tablas_maestras/categorias/listarCategoria.php?error=1');
        }
        exit();
    }

    public function eliminar(){
        $id_categoria = $_GET['id'];

        $resultado = $this->categoria->eliminar($id_categoria);

        if($resultado){
            header('Location: ../../views/tablas_maestras/categorias/listarCategoria.php?mensaje=eliminado');
        }else{
            header('Location: ../../views/tablas_maestras/categorias/listarCategoria.php?error=1');
        }
        exit();
    }

}

$controler = new CategoriaController();

$accion = $_REQUEST['accion'] ?? '';

switch($accion){
    case 'crear':
        $controler->crear();
        break;
    case 'editar':
        $controler->editar();
        break;
    case 'eliminar':
        $controler->eliminar();
        break;
    default:
        header('Location: ../../views/tablas_maestras/categorias/listarCategoria.php');
        exit();
}
?>

