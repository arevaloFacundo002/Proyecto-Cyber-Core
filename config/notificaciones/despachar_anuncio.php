<?php
require_once __DIR__ . '/notificar_multicanal.php';
require_once __DIR__ . '/../../models/Anuncio.php';
require_once __DIR__ . '/../../models/Notificacion.php';

function despachar_anuncio(int $id_anuncio): int
{
    $anuncioModel = new Anuncio();
    $anuncio = $anuncioModel->obtener($id_anuncio);

    if (!$anuncio || $anuncio['enviado']) {
        return 0;
    }

    $notifModel = new Notificacion();

    switch ($anuncio['audiencia']) {
        case 'todos_clientes':
            $destinatarios = $notifModel->obtener_ids_clientes();
            break;
        case 'todos_staff':
            $destinatarios = $notifModel->obtener_ids_staff();
            break;
        case 'todos':
            $destinatarios = array_merge($notifModel->obtener_ids_clientes(), $notifModel->obtener_ids_staff());
            break;
        default:
            $destinatarios = [];
    }

    $canales = explode(',', $anuncio['canales']);

    foreach ($destinatarios as $id_usuario) {
        notificar_multicanal($id_usuario, $anuncio['tipo'], $anuncio['titulo'], $anuncio['mensaje'], $canales);
    }

    $anuncioModel->marcar_enviado($id_anuncio);

    return count($destinatarios);
}

/**
 * Manda el anuncio SOLO al usuario que esta probando (no a toda la audiencia),
 * y NO marca el anuncio como enviado -- sigue siendo borrador despues de probar.
 */
function probar_anuncio(int $id_anuncio, int $id_usuario_probador): bool
{
    $anuncioModel = new Anuncio();
    $anuncio = $anuncioModel->obtener($id_anuncio);

    if (!$anuncio) {
        return false;
    }

    $canales = explode(',', $anuncio['canales']);

    notificar_multicanal(
        $id_usuario_probador,
        $anuncio['tipo'],
        '[PRUEBA] ' . $anuncio['titulo'],
        $anuncio['mensaje'],
        $canales,
        null,
        $id_anuncio
    );

    return true;
}