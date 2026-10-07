 <?php
require_once 'models/Usuario.php';
$user = new Usuario();

$error = '';
$exito = '';

if (!isset($_GET['token']) || empty($_GET['token'])) {
    $error = 'Token invalido o vacio';
}else {
    
    $token = $_GET['token'];
    $usuario = $user->buscar_por_token($token);

    if (!$usuario) {
        $error = 'Token inválido o expirado';
    } 
    elseif ($usuario['validado'] == 1) {
        $error = 'La cuenta ya fue validada.';
    } 
    else {
        if ($user->validar_usuario($token)) {
            $exito = "Cuenta validada con éxito. Ya podés iniciar sesión.";
        } else {
            $error = "Error al validar la cuenta.";
        }
    }
}


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validación de cuenta - CyberCore</title>
    <link href="css/theme.css" rel="stylesheet">
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .estado { color: var(--cc-texto-secundario); font-size: 18px; }
    </style>
</head>
<body>
    <div class="estado">Validando tu cuenta...</div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if ($error != ""): ?>
    <script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?= $error ?> Serás redirigido al login...'
    }).then(() => {
        window.location = 'login.php';
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
        window.location = 'login.php';
    });
    </script>
    <?php endif; ?>

    <script src="js/theme-toggle.js"></script>
</body>
</html>