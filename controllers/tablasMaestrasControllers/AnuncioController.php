<?php
require_once __DIR__ . '/../../models/Anuncio.php';
require_once __DIR__ . '/../../config/notificaciones/despachar_anuncio.php';

session_start();

class AnuncioController {
    private Anuncio $anuncio;

    public function __construct() {
        $this->anuncio = new Anuncio();
    }

    // Guarda como borrador. Todavia NO manda nada.
    public function crear(){
        $titulo = $_POST['titulo'];
        $mensaje = $_POST['mensaje'];
        $tipo = $_POST['tipo'];
        $audiencia = $_POST['audiencia'];
        $canales = implode(',', $_POST['canales'] ?? ['app']);

        $resultado = $this->anuncio->crear($titulo, $mensaje, $tipo, $canales, $audiencia, (int) $_SESSION['id_usuario']);

        if($resultado){
            header('Location: ../../views/anuncios/listarAnuncio.php?mensaje=creado');
        }else{
            header('Location: ../../views/anuncios/listarAnuncio.php?error=1');
        }
        exit();
    }

    // Solo aplica mientras es borrador
    public function editar(){
        $id_anuncio = (int) $_POST['id_anuncio'];
        $titulo = $_POST['titulo'];
        $mensaje = $_POST['mensaje'];
        $tipo = $_POST['tipo'];
        $audiencia = $_POST['audiencia'];
        $canales = implode(',', $_POST['canales'] ?? ['app']);

        $resultado = $this->anuncio->editar($id_anuncio, $titulo, $mensaje, $tipo, $canales, $audiencia);

        if($resultado){
            header('Location: ../../views/anuncios/listarAnuncio.php?mensaje=editado');
        }else{
            header('Location: ../../views/anuncios/listarAnuncio.php?error=1');
        }
        exit();
    }

    // Solo borra borradores; un anuncio ya enviado no se puede eliminar (queda como historico)
    public function eliminar(){
        $id_anuncio = (int) $_GET['id'];

        $resultado = $this->anuncio->eliminar($id_anuncio);

        if($resultado){
            header('Location: ../../views/anuncios/listarAnuncio.php?mensaje=eliminado');
        }else{
            header('Location: ../../views/anuncios/listarAnuncio.php?error=1');
        }
        exit();
    }

    // Manda el anuncio solo al admin logueado, para previsualizar antes de enviar de verdad
    public function probar(){
        $id_anuncio = (int) $_GET['id'];

        probar_anuncio($id_anuncio, (int) $_SESSION['id_usuario']);

        header('Location: ../../views/anuncios/listarAnuncio.php?mensaje=probado');
        exit();
    }

    // La accion especifica de anuncios: recien aca se dispara el envio real
    public function enviar(){
        $id_anuncio = (int) $_GET['id'];

        $cantidad = despachar_anuncio($id_anuncio);

        header('Location: ../../views/anuncios/listarAnuncio.php?mensaje=enviado&cantidad=' . $cantidad);
        exit();
    }
}

$anuncioController = new AnuncioController();

$accion = $_REQUEST['accion'] ?? '';

switch($accion){
    case 'crear':
        $anuncioController->crear();
        break;
    case 'editar':
        $anuncioController->editar();
        break;
    case 'eliminar':
        $anuncioController->eliminar();
        break;
    case 'probar':
        $anuncioController->probar();
        break;
    case 'enviar':
        $anuncioController->enviar();
        break;
    default:
        header('Location: ../../views/anuncios/listarAnuncio.php');
        exit();
}