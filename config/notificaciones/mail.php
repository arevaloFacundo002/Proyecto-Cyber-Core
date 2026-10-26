<?php 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__ . '/../env.php';

function enviar_mail(string $destino,string $nombre,string $asunto,string $mensajeHTML){
    $email = new PHPMailer(true);

    try {
        $email->isSMTP();
        $email->Host = 'smtp.gmail.com';
        $email->SMTPAuth = true;
        $email->Username = $_ENV['MAIL_USERNAME'];
        $email->Password = $_ENV['MAIL_PASSWORD'];
        $email->Port = 587;
        $email->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $email->CharSet = 'UTF-8';

        $email->setFrom($_ENV['MAIL_USERNAME'], $_ENV['MAIL_FROM_NAME'] ?? 'Cyber Core');
        $email->addAddress($destino,$nombre);

        $email->isHTML(true);
        $email->Subject = $asunto;
        $email->Body = $mensajeHTML;

        $email->send();
        return true;

    } catch (Exception $ex) {
        // Muestra el error exacto en la consola de comandos
        echo "[" . date('Y-m-d H:i:s') . "] ERROR SMTP al enviar a {$destino}: " . $email->ErrorInfo . "\n";
        return false;
    }
}