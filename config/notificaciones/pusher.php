<?php

use Pusher\Pusher;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../env.php';

function obtener_pusher(): Pusher
{
    $options = [
        'cluster' => $_ENV['PUSHER_CLUSTER'],
        'useTLS' => true
    ];

    return new Pusher(
        $_ENV['PUSHER_KEY'],
        $_ENV['PUSHER_SECRET'],
        $_ENV['PUSHER_APP_ID'],
        $options
    );
}

/**
 * Crea la notificación en la base y la dispara en tiempo real por Pusher.
 * Devuelve true si al menos quedó guardada en la base (aunque falle el pusher).
 */
function notificar(int $id_usuario, string $tipo, string $titulo, string $mensaje, ?string $url_accion = null): bool
{
    require_once __DIR__ . '/../models/Notificacion.php';

    $notificacion = new Notificacion();
    $id_notificacion = $notificacion->crear($id_usuario, $tipo, $titulo, $mensaje, $url_accion);

    if (!$id_notificacion) {
        return false;
    }

    try {
        $pusher = obtener_pusher();
        $pusher->trigger(
            'canal-usuario-' . $id_usuario,
            'nueva-notificacion',
            [
                'id_notificacion' => $id_notificacion,
                'tipo' => $tipo,
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'url_accion' => $url_accion,
                'fecha' => date('Y-m-d H:i:s')
            ]
        );
    } catch (Exception $ex) {
        // La notificación ya quedó guardada; si falla el realtime, no rompemos el flujo.
        error_log('Error al disparar Pusher: ' . $ex->getMessage());
    }

    return true;
}

/**
 * Igual que notificar(), pero le avisa a todos los administradores y empleados
 * activos en vez de a un solo usuario. Para avisos internos: stock bajo,
 * pedido nuevo, etc.
 */
function notificar_staff(string $tipo, string $titulo, string $mensaje, ?string $url_accion = null): void
{
    require_once __DIR__ . '/../models/Notificacion.php';

    $notificacion = new Notificacion();

    foreach ($notificacion->obtener_ids_staff() as $id_usuario) {
        notificar($id_usuario, $tipo, $titulo, $mensaje, $url_accion);
    }
}