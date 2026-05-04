<?php 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__.'/../vendor/autoload.php';

function enviar_mail($destino,$nombre,$asunto,$mensajeHTML){
    $email = new PHPMailer(true);
    
    try {
        $email->isSMTP();
        $email->Host = 'smtp.gmail.com';
        $email->SMTPAuth = true;
        $email->Username = 'arevalofacundo304@gmail.com';
        $email->Password = 'ompz zzof zkzc xddj';
        $email->Port = 587;
        $email->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $email->setFrom('arevalofacundo304@gmail.com','Cyber Core');
        $email->addAddress($destino,$nombre);

        $email->isHTML(true);
        $email->Subject = $asunto;
        $email->Body = $mensajeHTML;

        $email->send();
        return true;

        } catch (Exception $ex) {
            return false;
    }
}