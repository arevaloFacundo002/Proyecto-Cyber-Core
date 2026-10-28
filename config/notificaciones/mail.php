<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../env.php';

/** Devuelve true si en el .env hay usuario y contraseña de correo cargados. */
function mail_configurado(): bool
{
    return env_valor('MAIL_USERNAME') !== '' && env_valor('MAIL_PASSWORD') !== '';
}

function enviar_mail(string $destino, string $nombre, string $asunto, string $mensajeHTML)
{
    if (!mail_configurado()) {
        error_log('enviar_mail: faltan MAIL_USERNAME / MAIL_PASSWORD en el archivo .env');
        return false;
    }

    $email = new PHPMailer(true);

    try {
        $email->isSMTP();
        $email->Host       = 'smtp.gmail.com';
        $email->SMTPAuth   = true;
        $email->Username   = env_valor('MAIL_USERNAME');
        $email->Password   = env_valor('MAIL_PASSWORD');
        $email->Port       = 587;
        $email->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $email->CharSet    = 'UTF-8';
        $email->Timeout    = 10; // si Gmail no responde, no deja la página colgada

        $email->setFrom(env_valor('MAIL_USERNAME'), env_valor('MAIL_FROM_NAME', 'Cyber Core'));
        $email->addAddress($destino, $nombre);

        $email->isHTML(true);
        $email->Subject = $asunto;
        $email->Body    = $mensajeHTML;

        $email->send();
        return true;

    } catch (Exception $ex) {
        // El detalle va al registro de errores de PHP, no a la pantalla del usuario
        error_log("ERROR SMTP al enviar a {$destino}: " . $email->ErrorInfo);
        return false;
    }
}