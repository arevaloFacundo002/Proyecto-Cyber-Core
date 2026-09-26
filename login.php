<?php
session_start();
require_once 'models/Usuario.php';
$user = new Usuario();

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $correo = $_POST['correo'] ?? null;
    $password = $_POST['password'] ?? null;

    $usuario = $user->login($correo);

    if ($usuario && password_verify($password, $usuario['password'])) {

        if($usuario['validado'] == 0){
            $error = "Debes verificar tu correo antes de iniciar sesión";

            $_SESSION['reenviar_correo'] = $usuario['correo'];
        }
        elseif ($usuario['estado'] == "bloqueado" || $usuario['estado'] == "inactivo") {
            $error = "Tu cuenta está bloqueada o inactiva";
        } else{

            $_SESSION['usuario'] = $usuario['nombre'];
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['rol'] = $usuario['nombre_perfil'];
            $_SESSION['correo'] = $usuario['correo'];

            if ($usuario['nombre_perfil'] == 'administrador' || $usuario['nombre_perfil'] == 'empleado') {
                header('Location: inicio.php');
                exit();

            } elseif ($usuario['nombre_perfil'] == 'cliente' || $usuario['nombre_perfil'] == 'usuario') {
                header('Location: home.php');
                exit();

            } else {
                $error = "Rol no reconocido";
            }
        }

    } else {
        $error = "Correo o contraseña incorrectos";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>CyberCore - Iniciar Sesión</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="css/theme.css" rel="stylesheet">

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.login-box {
    background: var(--cc-superficie);
    padding: 40px;
    width: 380px;
    border-radius: 15px;
    box-shadow: 0 0 25px rgba(0, 234, 255, 0.25);
    text-align: center;
    border: 1px solid var(--cc-borde);
    color: var(--cc-texto);
}

.logo {
    font-size: 32px;
    color: var(--cc-primario);
    font-weight: bold;
    margin-bottom: 25px;
}

input {
    width: 90%;
    box-sizing: border-box;
    padding: 14px;
    margin: 10px 0;
    border-radius: 8px;
    border: 1px solid var(--cc-borde);
    background: var(--cc-fondo);
    color: var(--cc-texto);
}

button {
    width: 95%;
    padding: 14px;
    margin-top: 15px;
    background: var(--cc-primario);
    color: var(--cc-texto-sobre-primario);
    border: none;
    border-radius: 25px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.2s;
}

button:hover {
    background: var(--cc-primario-hover);
}

.registro {
    margin-top: 8px;
    color: var(--cc-texto);
}

.registro a {
    color: var(--cc-primario);
}

.olvide {
    margin-top: 15px;
}

.olvide a {
    color: var(--cc-primario);
    text-decoration: none;
    font-size: 14px;
}

.olvide a:hover {
    text-decoration: underline;
}
</style>
</head>

<body>

<div class="login-box">

    <div class="logo">CYBERCORE</div>
    <h2>Iniciar sesión</h2>

  <form action="login.php" method="POST">
        <input type="email" name="correo" placeholder="Correo electrónico" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit">Ingresar</button>
    </form>

    <div class="registro">
        ¿No tenés una cuenta? <a href="registro.php">Registrate aquí</a>
    </div>

    <p class="olvide">
        <a href="config/recuperar/olvide_password.php">
            ¿Olvidaste tu contraseña?
        </a>
    </p>

    <!--reenvia el correo si el usuario no verifico-->
    <?php if (isset($_SESSION['reenviar_correo'])): ?>
        <form action="config/reenviar_mail.php" method="POST">
            <input type="hidden" name="correo" value="<?= $_SESSION['reenviar_correo'] ?>">
            <button type="submit">Reenviar correo de verificación</button>
        </form>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/theme-toggle.js"></script>

<?php if ($error != ""): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Error',
    text: '<?= $error ?>'
});
</script>
<?php endif; ?>

</body>
</html> 