<?php
/**
 * config/notificaciones/notificar_multicanal.php
 *
 * Punto unico para disparar una notificacion. Crea UN SOLO registro en
 * `notificaciones` y la reparte por los canales pedidos en $canales:
 *   'app'      -> Pusher + campanita en tiempo real
 *   'email'    -> PHPMailer, al correo del usuario
 *   'whatsapp' -> Meta Cloud API, al numero cargado en contactos (solo clientes)
 *
 * Ejemplo:
 *   notificar_multicanal($id_usuario, 'stock_bajo', 'Stock bajo', 'Quedan 3 unidades', ['app', 'email']);
 */

require_once __DIR__ . '/pusher.php';
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/whatsapp.php';
require_once __DIR__ . '/../../models/Notificacion.php';
require_once __DIR__ . '/../../models/NotificacionEnvio.php';
require_once __DIR__ . '/../../models/Usuario.php';
require_once __DIR__ . '/../../models/Cliente.php';

function notificar_multicanal(
    int $id_usuario,
    string $tipo,
    string $titulo,
    string $mensaje,
    array $canales = ['app'],
    ?string $url_accion = null,
    ?int $id_anuncio = null
): void {
    $notifModel = new Notificacion();
    $id_notificacion = $notifModel->crear($id_usuario, $tipo, $titulo, $mensaje, $url_accion, $id_anuncio);

    if (!$id_notificacion) {
        error_log("notificar_multicanal: no se pudo guardar la notificacion para el usuario {$id_usuario}");
        return;
    }

    // --- Canal: app (campanita; Pusher la actualiza en tiempo real si está configurado) ---
    if (in_array('app', $canales, true)) {
        disparar_pusher($id_usuario, [
            'id_notificacion' => $id_notificacion,
            'tipo'            => $tipo,
            'titulo'          => $titulo,
            'mensaje'         => $mensaje,
            'url_accion'      => $url_accion,
            'fecha'           => date('Y-m-d H:i:s'),
        ]);
    }

    // --- Canal: email ---
    if (in_array('email', $canales, true)) {
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->obtener_usuario($id_usuario);

        if ($usuario && !empty($usuario['correo'])) {
            $envioModel = new NotificacionEnvio();
            $id_envio = $envioModel->registrar($id_notificacion, 'email', $usuario['correo']);

            try {
                $enviado = enviar_mail(
                    $usuario['correo'],
                    $usuario['nombre'] ?? '',
                    $titulo,
                    '<p>' . nl2br(htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8')) . '</p>'
                );
            } catch (Throwable $ex) {
                error_log('notificar_multicanal (email): ' . $ex->getMessage());
                $enviado = false;
            }

            if ($id_envio) {
                $enviado
                    ? $envioModel->marcar_enviado($id_envio)
                    : $envioModel->marcar_fallido($id_envio, 'enviar_mail() devolvió false');
            }
        } else {
            error_log("notificar_multicanal (email): usuario {$id_usuario} sin correo cargado");
        }
    }

    // --- Canal: whatsapp (solo tiene sentido para usuarios que son clientes) ---
    if (in_array('whatsapp', $canales, true)) {
        $clienteModel = new Cliente();
        $numero = $clienteModel->obtener_contacto_por_usuario($id_usuario);

        if ($numero) {
            try {
                enviar_whatsapp($id_notificacion, $numero, $mensaje);
            } catch (Throwable $ex) {
                error_log('notificar_multicanal (whatsapp): ' . $ex->getMessage());
            }
        } else {
            error_log("notificar_multicanal (whatsapp): usuario {$id_usuario} sin numero cargado en contactos");
        }
    }
}