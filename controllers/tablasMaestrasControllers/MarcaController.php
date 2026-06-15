<?php
require_once 'C:\Users\areva\.vscode\cyber_core\models/tablas_maestras/Marca.php';

class MarcaController{
    private Marca $marca;

    public function __construct() {
        $this->marca = new Marca();
    }

    public function crear(){
        $nombre_marca = $_POST['nombre_marca'];
        $nombre_corto = $_POST['nombre_corto'];
        $logo = $_FILES['logo_url'];
        $sitio_web = $_POST['sitio_web'];

        $resultado = $this->marca->crear($nombre_marca,$nombre_corto,$logo,$sitio_web);

        if($resultado){
            header('Location: ../../views/tablas_maestras/marcas/listarMarca.php?mensaje=creado');
        }else{
            header('Location: ../../views/tablas_maestras/marcas/listarMarca.php?error=1');
        }
        exit();
    }

    public function editar(){
        $id_marca = $_POST['id_marca'];
        $nombre_marca = $_POST['nombre_marca'];
        $nombre_corto = $_POST['nombre_corto'];
        $logo = $_FILES['logo_url'];
        $sitio_web = $_POST['sitio_web'];

        $resultado = $this->marca->editar($nombre_marca,$nombre_corto,$logo,$sitio_web,$id_marca);

        if($resultado){
            header('Location: ../../views/tablas_maestras/marcas/listarMarca.php?mensaje=editado');
        }else{
            header('Location: ../../views/tablas_maestras/marcas/listarMarca.php?error=1');
        }
        exit();
    }

    public function eliminar(){
        $id_marca = $_GET['id'];

        $resultado = $this->marca->eliminar($id_marca);

        if($resultado){
            header('Location: ../../views/tablas_maestras/marcas/listarMarca.php?mensaje=eliminado');
        }else{
            header('Location: ../../views/tablas_maestras/marcas/listarMarca.php?error=1');
        }
        exit();

    }
}

$marcaController = new MarcaController();

$accion = $_REQUEST['accion'] ?? '';

switch($accion){
    case 'crear':
        $marcaController->crear();
        break;
    case 'editar':
        $marcaController->editar();
        break;
    case 'eliminar':
        $marcaController->eliminar();
        break;
    default:
        header('Location: ../../views/tablas_maestras/marcas/listarMarca.php');
        exit();
}
?>