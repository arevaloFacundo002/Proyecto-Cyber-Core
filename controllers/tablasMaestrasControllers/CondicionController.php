<?php
require_once 'C:\Users\areva\.vscode\cyber_core\models/tablas_maestras/CondicionIva.php';

class CondicionIvaController{
    private CondicionIva $condicion;

    public function __construct() {
        $this->condicion = new CondicionIva();
    }

    public function crear(){
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $porcentaje_iva = $_POST['porcentaje_iva'];

        $resultado = $this->condicion->crear($nombre,$descripcion,$porcentaje_iva);

        if($resultado){
            header('Location: ../../views/tablas_maestras/condicionIva/listarCondicion.php?mensaje=creado');
        }else{
            header('Location: ../../views/tablas_maestras/condicionIva/listarCondicion.php?error=1');
        }
        exit();
    }

    public function editar(){
        $id_condicion_iva = $_POST['id_condicion_iva'];
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $porcentaje_iva = $_POST['porcentaje_iva'];

        $resultado = $this->condicion->editar($nombre,$descripcion,$porcentaje_iva,$id_condicion_iva);

        if($resultado){
            header('Location: ../../views/tablas_maestras/condicionIva/listarCondicion.php?mensaje=editado');
        }else{
            header('Location: ../../views/tablas_maestras/condicionIva/listarCondicion.php?error=1');
        }
        exit();
    }

    public function eliminar(){
        $id_condicion_iva = $_GET['id'];

        $resultado = $this->condicion->eliminar($id_condicion_iva);

        if($resultado){
            header('Location: ../../views/tablas_maestras/condicionIva/listarCondicion.php?mensaje=eliminado');
        }else{
            header('Location: ../../views/tablas_maestras/condicionIva/listarCondicion.php?error=1');
        }
        exit();
    }
}

$condicionController = new CondicionIvaController();

$accion = $_REQUEST['accion'] ?? '';

switch($accion){
    case 'crear':
        $condicionController->crear();
        break;
    case 'editar':
        $condicionController->editar();
        break;
    case 'eliminar':
        $condicionController->eliminar();
        break;
    default:
        header('Location: ../../views/tablas_maestras/condicionIva/listarCondicion.php');
        exit();
}
?>