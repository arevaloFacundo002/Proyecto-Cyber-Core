<?php
require_once 'C:\Users\areva\.vscode\cyber_core\models/tablas_maestras/ConceptoMovimiento.php';

class ConceptoMovimientoController{
    private ConceptoMovimiento $concepto;

    public function __construct() {
        $this->concepto = new ConceptoMovimiento();
    }

    public function crear(){
        $descripcion = $_POST['descripcion'];
        $tipo_movimiento = $_POST['tipo_movimiento'];


        $resultado = $this->concepto->crear($descripcion,$tipo_movimiento);
    
        if($resultado){
            header('Location: ../../views/tablas_maestras/conceptoMovimientos/listarConcepto.php?mensaje=creado');
        }else{
            header('Location: ../../views/tablas_maestras/conceptoMovimientos/listarConcepto.php?error=1');
        }
        exit();

    }

    public function editar(){   
        $descripcion = $_POST['descripcion'];
        $tipo_movimiento = $_POST['tipo_movimiento'];
        $id_concepto = $_POST['id_concepto'];

        $resultado = $this->concepto->editar($descripcion,$tipo_movimiento,$id_concepto); 

        if($resultado){
            header('Location: ../../views/tablas_maestras/conceptoMovimientos/listarConcepto.php?mensaje=editado');
        }else{
            header('Location: ../../views/tablas_maestras/conceptoMovimientos/listarConcepto.php?error=1');
        }
        exit();
    }

    public function eliminar(){
        $id_concepto = $_GET['id'];

        $resultado = $this->concepto->eliminar($id_concepto);   

        if($resultado){
            header('Location: ../../views/tablas_maestras/conceptoMovimientos/listarConcepto.php?mensaje=eliminado');
        }else{
            header('Location: ../../views/tablas_maestras/conceptoMovimientos/listarConcepto.php?error=1');
        }
        exit();
    }

}

$conceptoController = new ConceptoMovimientoController();

$accion = $_REQUEST['accion']?? '';

switch($accion){
    case 'crear':
        $conceptoController->crear();
        break;
    case 'editar':
        $conceptoController->editar();
        break;
    case 'eliminar':
        $conceptoController->eliminar();
        break;
    default:
        header('Location: ../../views/tablas_maestras/conceptoMovimientos/listarConcepto.php');
        exit();
}
?>