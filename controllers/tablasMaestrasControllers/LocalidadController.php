<?php
require_once 'C:\Users\areva\.vscode\cyber_core\models/tablas_maestras/Ubicacion.php';

class LocalidadController{
    private Ubicacion $ubicacion;

    public function __construct() {
        $this->ubicacion = new Ubicacion();
    }

    public function crear(){
        $nombre_localidad = $_POST['nombre_localidad'];
        $codigo_postal = $_POST['codigo_postal'];
        $tipo_zona = $_POST['tipo_zona'];
        $rela_id_provincia = $_POST['rela_id_provincia'];

        $resultado = $this->ubicacion->crearLocalidades($nombre_localidad,$codigo_postal,$tipo_zona,$rela_id_provincia);

        if($resultado){
            header('Location: ../../views/tablas_maestras/localidades/listarLocalidad.php?mensaje=creado');
        }else{  
            header('Location: ../../views/tablas_maestras/localidades/listarLocalidad.php?error=1');
        }
        exit();
    }
    
    public function editar(){
        $id_localidad = $_POST['id_localidad'];
        $nombre_localidad = $_POST['nombre_localidad'];
        $codigo_postal = $_POST['codigo_postal'];
        $tipo_zona = $_POST['tipo_zona'];
        $rela_id_provincia = $_POST['rela_id_provincia'];

        $resultado = $this->ubicacion->editarLocalidades($nombre_localidad,$codigo_postal,$tipo_zona,$rela_id_provincia,$id_localidad);

        if($resultado){
            header('Location: ../../views/tablas_maestras/localidades/listarLocalidad.php?mensaje=editado');
        }else{
            header('Location: ../../views/tablas_maestras/localidades/listarLocalidad.php?error=1');
        }
        exit();
    }

    public function eliminar(){
        $id_localidad = $_GET['id'];

        $resultado = $this->ubicacion->eliminarLocalidades($id_localidad);  
        if($resultado){
            header('Location: ../../views/tablas_maestras/localidades/listarLocalidad.php?mensaje=eliminado');
        }else{
            header('Location: ../../views/tablas_maestras/localidades/listarLocalidad.php?error=1');
        }
        exit(); 
    }
}

$localidadController = new LocalidadController();

$accion = $_REQUEST['accion'] ?? '';

switch($accion){
    case 'crear':
        $localidadController->crear();
    case 'editar':
        $localidadController->editar();
    case 'eliminar':
        $localidadController->eliminar();
    default: 
        header('Location: ../../views/tablas_maestras/localidades/listarLocalidad.php');
        exit();
}
?>