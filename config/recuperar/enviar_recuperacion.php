<?php
require_once '../../models/Usuario.php';
require_once '../mail.php';
$user = new Usuario();

$error = '';
$exito = '';

if (!isset($_POST['correo'])) {
    $error = 'Error';
}else{

    $correo = $_POST['correo'];
    $usuario = $user->login($correo);

    if (!$usuario) {
        $error = "No existe una cuenta con ese correo";
    }else{

        //  generar token
        $token = bin2hex(random_bytes(32));

        // guardar token
        $user->actualizar_token($token,$correo);

        // link
        $base_url = "http://" . $_SERVER['HTTP_HOST'];
        $link = $base_url . "/config/recuperar/nueva_password.php?token=$token";

        // mail
        $mensaje = "
            <h2>Cyber Core</h2>
            <h2>Recuperar contraseña</h2>
            <p>Hacé click en el siguiente enlace:</p>
            <a href='$link'>Restablecer contraseña</a>
        ";

        enviar_mail($correo, $usuario['nombre'], 'Recuperar contraseña', $mensaje);
        $exito = 'Revisá tu email para cambiar tu contraseña';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>enviar</title>
</head>
<body>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (!empty($error)): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Error',
    text: '<?= $error ?>'
}).then(() => {
    window.location = '../../login.php';
});
</script>
<?php endif; ?>

<?php if (!empty($exito)): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Correo enviado',
    text: '<?= $exito ?>'
}).then(() => {
    window.location = '../../login.php';
});
</script>
<?php endif; ?>
    
</body>
</html>
