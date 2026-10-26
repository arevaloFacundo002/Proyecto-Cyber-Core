<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    die('No autenticado. Iniciá sesión primero.');
}

require_once __DIR__ . '/../models/Notificacion.php';
require_once __DIR__ . '/../config/notificaciones/whatsapp.php';

// Reemplazá por el numero que uniste al sandbox con "join palabra-clave"
$numero_prueba = '+543705033550';

// Primero se crea la notificacion real (igual que hace notificar() con Pusher)
$notificacion = new Notificacion();
$id_notificacion = $notificacion->crear(
    (int) $_SESSION['id_usuario'],
    'prueba',
    'Prueba de WhatsApp',
    'Notificación de prueba para probar el envío por WhatsApp.'
);

$ok = enviar_whatsapp(
    $id_notificacion,
    $numero_prueba,
    'Prueba de WhatsApp desde Cyber Core 🚀'
);

echo $ok ? 'WhatsApp enviado correctamente.' : 'Falló el envío — revisá error_log o la tabla notificaciones_envios.';