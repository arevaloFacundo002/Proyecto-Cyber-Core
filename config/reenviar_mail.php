<?php
require_once '../conexion.php';
require_once 'mail.php';
$dao = new UserDao();
$error = '';
$exito = '';

if (!isset($_POST['correo'])) {
    $error = 'Error';
} else {

    $correo = $_POST['correo'];
    $usuario = $dao->login($correo);

    if (!$usuario) {
        $error = 'No se encontró al usuario.';
    } 
    elseif ($usuario['validado'] == 1) {
        $error = 'El usuario ya está validado.';
    } 
    else {
        $token = bin2hex(random_bytes(32));
        $dao->actualizar_token($token,$correo);

        $base_url = "http://" . $_SERVER['HTTP_HOST'];
        $link = $base_url . "/validar_cuenta.php?token=$token";

        $mensaje = "
            <h2>Hola {$usuario['nombre']}</h2>
            <p>Reenvío de verificación:</p>
            <a href='$link'>Validar cuenta</a>
        ";

        enviar_mail($correo,$usuario['nombre'],'Reenvío de verificación',$mensaje);

        $exito = "Correo reenviado correctamente.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reenviar mail</title>
</head>
<body>
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if ($error != ""): ?>
        <script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '<?= $error ?> Serás redirigido al login...'
        }).then(() => {
            window.location = '../login.php';
        });
        </script>
    <?php endif; ?>

    <?php if ($exito != ""): ?>
        <script>
        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: '<?= $exito ?> Serás redirigido al login...'
        }).then(() => {
            window.location = '../login.php';
        });
        </script>
    <?php endif; ?>
    
</body>
</html>