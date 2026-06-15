<?php
require_once 'C:\Users\areva\.vscode\cyber_core\models/tablas_maestras/Perfil.php';

class PerfilController{
    private Perfil $perfil;

    public function __construct() {
        $this->perfil = new Perfil();
    }

    public function crear(){
        $nombre = $_POST['nombre'];

        $resultado = $this->perfil->crear($nombre);

        if($resultado){
            header('Location: ../../views/tablas_maestras/perfiles/listarPerfil.php?mensaje=creado');
        }else{
            header('Location: ../../views/tablas_maestras/perfiles/listarPerfil.php?error=1');
        }
        exit();
    }

    public function editar(){   
        $nombre = $_POST['nombre'];
        $id_perfil = $_POST['id_perfil'];

        $resultado = $this->perfil->editar($nombre,$id_perfil); 

        if($resultado){
            header('Location: ../../views/tablas_maestras/perfiles/listarPerfil.php?mensaje=editado');
        }else{
            header('Location: ../../views/tablas_maestras/perfiles/listarPerfil.php?error=1');
        }
        exit();
    }

    public function eliminar(){
        $id_perfil = $_GET['id'];

        $resultado = $this->perfil->eliminar($id_perfil);   

        if($resultado){
            header('Location: ../../views/tablas_maestras/perfiles/listarPerfil.php?mensaje=eliminado');
        }else{
            header('Location: ../../views/tablas_maestras/perfiles/listarPerfil.php?error=1');
        }
        exit();
    }

}

$perfilController = new PerfilController();

$accion = $_REQUEST['accion']?? '';

switch($accion){
    case 'crear':
        $perfilController->crear();
        break;
    case 'editar':
        $perfilController->editar();
        break;
    case 'eliminar':
        $perfilController->eliminar();
        break;
    default:
        header('Location: ../../views/tablas_maestras/perfiles/listarPerfil.php');
        exit();
}
?>


