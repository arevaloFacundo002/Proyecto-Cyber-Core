<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    die('No autenticado. Iniciá sesión primero.');
}

require_once __DIR__ . '/../config/notificaciones/notificar_multicanal.php';

notificar_multicanal(
    (int) $_SESSION['id_usuario'],
    'prueba',
    'Prueba multicanal',
    'Si te llegó el toast, el correo y el WhatsApp, el sistema completo anda bien.',
    ['app', 'email', 'whatsapp']
);

echo 'Disparado. Revisá: el toast en Home, tu correo, tu WhatsApp, y la fila nueva en notificaciones_envios.';