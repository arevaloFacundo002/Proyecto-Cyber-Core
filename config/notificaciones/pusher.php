<?php

require_once __DIR__ . '/../env.php';

/** true si en el .env están las claves de Pusher y la librería está instalada (composer install). */
function pusher_configurado(): bool
{
    if (env_valor('PUSHER_KEY') === '' || env_valor('PUSHER_SECRET') === '' || env_valor('PUSHER_APP_ID') === '') {
        return false;
    }

    $autoload = __DIR__ . '/../../vendor/autoload.php';

    if (!is_file($autoload)) {
        return false;
    }

    require_once $autoload;

    return class_exists('Pusher\\Pusher');
}

function obtener_pusher()
{
    require_once __DIR__ . '/../../vendor/autoload.php';

    $options = [
        'cluster' => env_valor('PUSHER_CLUSTER', 'us2'),
        'useTLS'  => true,
        'timeout' => 5,
    ];

    $clase = 'Pusher\Pusher';

    return new $clase(
        env_valor('PUSHER_KEY'),
        env_valor('PUSHER_SECRET'),
        env_valor('PUSHER_APP_ID'),
        $options
    );
}

/**
 * Dispara el aviso en tiempo real a la campanita del usuario.
 * Si Pusher no está configurado o falla, no pasa nada: la notificación ya está
 * guardada en la base y aparece en la campanita al cargar la próxima página.
 */
function disparar_pusher(int $id_usuario, array $datos): void
{
    try {
        if (!pusher_configurado()) {
            return;
        }
        obtener_pusher()->trigger('canal-usuario-' . $id_usuario, 'nueva-notificacion', $datos);
    } catch (Throwable $ex) {
        error_log('Pusher: ' . $ex->getMessage());
    }
}

/**
 * Crea la notificación en la base y la dispara en tiempo real.
 * Devuelve true si quedó guardada en la base.
 */
function notificar(int $id_usuario, string $tipo, string $titulo, string $mensaje, ?string $url_accion = null): bool
{
    require_once __DIR__ . '/../../models/Notificacion.php';

    $notificacion    = new Notificacion();
    $id_notificacion = $notificacion->crear($id_usuario, $tipo, $titulo, $mensaje, $url_accion);

    if (!$id_notificacion) {
        return false;
    }

    disparar_pusher($id_usuario, [
        'id_notificacion' => $id_notificacion,
        'tipo'            => $tipo,
        'titulo'          => $titulo,
        'mensaje'         => $mensaje,
        'url_accion'      => $url_accion,
        'fecha'           => date('Y-m-d H:i:s'),
    ]);

    return true;
}

/**
 * Igual que notificar(), pero para todos los administradores y empleados activos.
 */
function notificar_staff(string $tipo, string $titulo, string $mensaje, ?string $url_accion = null): void
{
    require_once __DIR__ . '/../../models/Notificacion.php';

    $notificacion = new Notificacion();

    foreach ($notificacion->obtener_ids_staff() as $id_usuario) {
        notificar($id_usuario, $tipo, $titulo, $mensaje, $url_accion);
    }
}