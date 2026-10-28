<?php

require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/admin.php';
require_once __DIR__ . '/../../models/Anuncio.php';
require_once __DIR__ . '/../../config/notificaciones/despachar_anuncio.php';

class AnuncioController {
    private Anuncio $anuncio;

    public function __construct() {
        $this->anuncio = new Anuncio();
    }

    private function leerFormulario(): array
    {
        $titulo    = trim($_POST['titulo'] ?? '');
        $mensaje   = trim($_POST['mensaje'] ?? '');
        $tipo      = in_array($_POST['tipo'] ?? '', ['oferta', 'evento', 'aviso_general'], true) ? $_POST['tipo'] : 'aviso_general';
        $audiencia = in_array($_POST['audiencia'] ?? '', ['todos_clientes', 'todos_staff', 'todos'], true) ? $_POST['audiencia'] : 'todos_clientes';
        $canales   = array_values(array_intersect(['app', 'email', 'whatsapp'], (array) ($_POST['canales'] ?? [])));

        if ($titulo === '' || $mensaje === '') {
            header('Location: ../../views/anuncios/listarAnuncio.php?error=1');
            exit();
        }

        return [mb_substr($titulo, 0, 150), $mensaje, $tipo, $audiencia, implode(',', $canales ?: ['app'])];
    }

    public function crear(){
        [$titulo, $mensaje, $tipo, $audiencia, $canales] = $this->leerFormulario();

        $resultado = $this->anuncio->crear($titulo, $mensaje, $tipo, $canales, $audiencia, (int) $_SESSION['id_usuario']);

        if($resultado){
            header('Location: ../../views/anuncios/listarAnuncio.php?mensaje=creado');
        }else{
            header('Location: ../../views/anuncios/listarAnuncio.php?error=1');
        }
        exit();
    }

    public function editar(){
        $id_anuncio = (int) ($_POST['id_anuncio'] ?? 0);
        [$titulo, $mensaje, $tipo, $audiencia, $canales] = $this->leerFormulario();

        $resultado = $this->anuncio->editar($id_anuncio, $titulo, $mensaje, $tipo, $canales, $audiencia);

        if($resultado){
            header('Location: ../../views/anuncios/listarAnuncio.php?mensaje=editado');
        }else{
            header('Location: ../../views/anuncios/listarAnuncio.php?error=1');
        }
        exit();
    }

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

    public function probar(){
        $id_anuncio = (int) $_GET['id'];

        probar_anuncio($id_anuncio, (int) $_SESSION['id_usuario']);

        header('Location: ../../views/anuncios/listarAnuncio.php?mensaje=probado');
        exit();
    }

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