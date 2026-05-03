<?php
session_start();
require_once "conexion.php";
$dao = new UserDao();

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $correo = $_POST['correo'] ?? null;
    $password = $_POST['password'] ?? null;

    $usuario = $dao->login($correo);

    if ($usuario && password_verify($password, $usuario['password'])) {

        if ($usuario['estado'] == "bloqueado" || $usuario['estado'] == "inactivo") {
            $error = "Tu cuenta está bloqueada o inactiva";
        } else{

            $_SESSION['usuario'] = $usuario['nombre'];
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['rol'] = $usuario['nombre_perfil'];
            $_SESSION['correo'] = $usuario['correo'];

            if ($usuario['nombre_perfil'] == 'administrador') {
                header('Location: inicio.php');
                exit();

            } elseif ($usuario['nombre_perfil'] == 'cliente' || $usuario['nombre_perfil'] == 'usuario') {
                header('Location: home.php');
                exit();

            } elseif ($usuario['nombre_perfil'] == 'empleado') {
                header('Location: inicio.php');
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

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #0a0a0a, #090e13ff);
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.login-box {
    background: #111;
    padding: 40px;
    width: 380px;
    border-radius: 15px;
    box-shadow: 0 0 25px #00eaff44;
    text-align: center;
}

.logo {
    font-size: 32px;
    color: #00eaff;
    font-weight: bold;
    margin-bottom: 25px;
}

input {
    width: 90%;
    padding: 14px;
    margin: 10px 0;
    border-radius: 8px;
    border: none;
    background: #1c1c1c;
    color: white;
}

button {
    width: 95%;
    padding: 14px;
    margin-top: 15px;
    background: #00eaff;
    border-radius: 25px;
    font-weight: bold;
    cursor: pointer;
}
</style>
</head>

<body>

<div class="login-box">

    <div class="logo">CYBERCORE</div>
    <h2>Iniciar sesión</h2>

    <form method="POST">
        <input type="email" name="correo" placeholder="Correo electrónico" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit">Ingresar</button>
    </form>

    <div class="registro">
        ¿No tenés una cuenta? <a href="registro.php">Registrate aquí</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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