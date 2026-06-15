<?php 
require_once 'C:\Users\areva\.vscode\cyber_core\models/tablas_maestras/MetodoPago.php';

class MetodoController{
    private MetodoPago $metodo; 

    public function __construct() {
        $this->metodo = new MetodoPago();
    }

    public function crear(){    
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $requiere_autorizacion = $_POST['requiere_autorizacion'];

        $resultado = $this->metodo->crear($nombre,$descripcion,$requiere_autorizacion);
        
        if($resultado){
            header('Location: ../../views/tablas_maestras/metodosPago/listarMetodo.php?mensaje=creado');
        }else{
            header('Location: ../../views/tablas_maestras/metodosPago/listarMetodo.php?error=1');
        }
        exit();
    }

    public function editar(){
        $id_metodo = $_POST['id_metodo_pago'];
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'];
        $requiere_autorizacion = $_POST['requiere_autorizacion'];

        $resultado = $this->metodo->editar($nombre,$descripcion,$requiere_autorizacion,$id_metodo);

        if($resultado){
            header('Location: ../../views/tablas_maestras/metodosPago/listarMetodo.php?mensaje=editado');
        }else{
            header('Location: ../../views/tablas_maestras/metodosPago/listarMetodo.php?error=1');
        }
        exit();
    }

    public function eliminar(){
        $id_metodo = $_GET['id'];

        $resultado = $this->metodo->eliminar($id_metodo);

        if($resultado){
            header('Location: ../../views/tablas_maestras/metodosPago/listarMetodo.php?mensaje=eliminado');
        }else{
            header('Location: ../../views/tablas_maestras/metodosPago/listarMetodo.php?error=1');
        }
        exit();
    }

}

$metodoController = new MetodoController();

$accion = $_REQUEST['accion'] ?? ''; 

switch($accion){
    case 'crear':
        $metodoController->crear();
        break;
    case 'editar':
        $metodoController->editar();
        break;
    case 'eliminar':
        $metodoController->eliminar();
        break;
    default:
        header('Location: ../../views/tablas_maestras/metodosPago/listarMetodo.php');
        exit();
}
?>