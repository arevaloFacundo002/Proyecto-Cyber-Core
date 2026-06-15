<?php
require_once 'C:\Users\areva\.vscode\cyber_core\models/tablas_maestras/ModeloProducto.php';

class ModeloController{
    private ModeloProducto $modelo;

    public function __construct() {
        $this->modelo = new ModeloProducto();
    }

    public function crear(){
        $nombre_modelo = $_POST['nombre_modelo'];
        $rela_id_marca = $_POST['rela_id_marca'];

        $resultado = $this->modelo->crear($nombre_modelo,$rela_id_marca);

        if($resultado){
            header('Location: ../../views/tablas_maestras/modelosProducto/listarModelo.php?mensaje=creado');
        }else{
            header('Location: ../../views/tablas_maestras/modelosProducto/listarModelo.php?error=1');
        }
        exit();
    }

    public function editar(){
        $id_modelo = $_POST['id_modelo_producto'];
        $nombre_modelo = $_POST['nombre_modelo'];
        $rela_id_marca = $_POST['rela_id_marca'];

        $resultado = $this->modelo->editar($nombre_modelo,$rela_id_marca,$id_modelo);

        if($resultado){
            header('Location: ../../views/tablas_maestras/modelosProducto/listarModelo.php?mensaje=editado');
        }else{
            header('Location: ../../views/tablas_maestras/modelosProducto/listarModelo.php?error=1');
        }
        exit();
    }

    public function eliminar(){
        $id_modelo = $_GET['id'];

        $resultado = $this->modelo->eliminar($id_modelo);

        if($resultado){
            header('Location: ../../views/tablas_maestras/modelosProducto/listarModelo.php?mensaje=eliminado');
        }else{
            header('Location: ../../views/tablas_maestras/modelosProducto/listarModelo.php?error=1');
        }
        exit();
    }
}

$modeloController = new ModeloController();

$accion = $_REQUEST['accion']?? '';

switch($accion){
    case 'crear':
        $modeloController->crear();
        break;
    case 'editar':
        $modeloController->editar();
        break;
    case 'eliminar':
        $modeloController->eliminar();
        break;
    default:
        header('Location: ../../views/tablas_maestras/modelosProducto/listarModelo.php');
        exit();
}
?>