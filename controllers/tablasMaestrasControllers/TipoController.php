<?php
require_once 'C:\Users\areva\.vscode\cyber_core\models/tablas_maestras/TipoContacto.php';

class TipoContactoController{
    private TipoContacto $tipo;

    public function __construct() {
        $this->tipo = new TipoContacto();
    }   

    public function crear(){
        $descripcion = $_POST['descripcion'];

        $resultado = $this->tipo->crear($descripcion);

        if($resultado){
            header('Location: ../../views/tablas_maestras/tiposContacto/listarTipoContacto.php?mensaje=creado');
        }else{
            header('Location: ../../views/tablas_maestras/tiposContacto/listarTipoContacto.php?error=1');
        }
        exit();
    }

    public function editar(){
        $id_tipo_contacto = $_POST['id_tipo_contacto'];
        $descripcion = $_POST['descripcion'];

        $resultado = $this->tipo->editar($descripcion,$id_tipo_contacto);

        if($resultado){
            header('Location: ../../views/tablas_maestras/tiposContacto/listarTipoContacto.php?mensaje=editado');
        }else{
            header('Location: ../../views/tablas_maestras/tiposContacto/listarTipoContacto.php?error=1');
        }
        exit();
    }

    public function eliminar(){
        $id_tipo_contacto = $_GET['id'];

        $resultado = $this->tipo->eliminar($id_tipo_contacto);

        if($resultado){
            header('Location: ../../views/tablas_maestras/tiposContacto/listarTipoContacto.php?mensaje=eliminado');
        }else{
            header('Location: ../../views/tablas_maestras/tiposContacto/listarTipoContacto.php?error=1');
        }
        exit();
    }

}

$tipoController = new TipoContactoController();

$accion = $_REQUEST['accion'] ?? '';

switch($accion){
    case 'crear':
        $tipoController->crear();
        break;
    case 'editar':
        $tipoController->editar();
        break;
    case 'eliminar':
        $tipoController->eliminar();
        break;
    default:
        header('Location: ../../views/tablas_maestras/tiposContacto/listarTipoContacto.php');
        exit();
}
?>