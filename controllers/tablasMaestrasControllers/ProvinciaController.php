<?php
require_once 'C:\Users\areva\.vscode\cyber_core\models/tablas_maestras/Ubicacion.php';

class ProvinciaController{
    private Ubicacion $ubicacion;

    public function __construct() {
        $this->ubicacion = new Ubicacion();
    }

    public function crear(){
        $nombre_provincia = $_POST['nombre_provincia'];
        $codigo_iso = strtoupper(trim($_POST['codigo_iso']));
        $zona_tarifa = $_POST['zona_tarifa'];
        $dias_transitos_base = $_POST['dias_transitos_base'];

        $resultado = $this->ubicacion->crearProvincias($nombre_provincia,$codigo_iso,$zona_tarifa,$dias_transitos_base);
        if($resultado){
            header('Location: ../../views/tablas_maestras/provincias/listarProvincia.php?mensaje=creado');
        }else{
            header('Location: ../../views/tablas_maestras/provincias/listarProvincia.php?error=1');
        }
        exit();
    }

    public function editar(){
        $id_provincia = $_POST['id_provincia'];
        $nombre_provincia = $_POST['nombre_provincia'];
        $codigo_iso = strtoupper(trim($_POST['codigo_iso']));
        $zona_tarifa = $_POST['zona_tarifa'];
        $dias_transitos_base = $_POST['dias_transitos_base'];

        $resultado = $this->ubicacion->editarProvincias($nombre_provincia,$codigo_iso,$zona_tarifa,$dias_transitos_base,$id_provincia);
        
        if($resultado){
            header('Location: ../../views/tablas_maestras/provincias/listarProvincia.php?mensaje=editado');
        }else{
            header('Location: ../../views/tablas_maestras/provincias/listarProvincia.php?error=1');
        }
        exit();
    }

    public function eliminar(){
        $id_provincia = $_GET['id'];

        $resultado = $this->ubicacion->eliminarProvincias($id_provincia);
        if($resultado){
            header('Location: ../../views/tablas_maestras/provincias/listarProvincia.php?mensaje=eliminado');
        }else{
            header('Location: ../../views/tablas_maestras/provincias/listarProvincia.php?error=1');
        }
        exit();
    }

}

$provinciaController = new ProvinciaController();

$accion = $_REQUEST['accion'] ?? '';

switch($accion){
    case 'crear':
        $provinciaController->crear();
        break;
    case 'editar':
        $provinciaController->editar();
        break;
    case 'eliminar':
        $provinciaController->eliminar();
        break;
    default:
        header('Location: ../../views/tablas_maestras/provincias/listarProvincia.php');
        exit();
}
?>