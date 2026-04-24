<?php
require_once "conexion.php";
session_start();
$dao = new UserDao();

// Variables para mensajes
$error = "";

if (isset($_POST['registrar'])) {

    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $password = trim($_POST['password']);
    $password2 = trim($_POST['password2']);
    $fecha = date("Y-m-d");
    $rela_id_perfil = 2; // por defecto usuario normal, todavia no es cliente
    $tipo_usuario = 'usuario';

    // === VALIDACIONES ===

    if (strlen($nombre) < 3) {
        $error = "El nombre debe tener al menos 3 caracteres.";     #El largo del nombre
    }
    elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {          #Validar correo
        $error = "El correo no es válido.";
    }
    elseif ($password !== $password2) {                                 #la constrasenia
        $error = "Las contraseñas no coinciden.";
    }
    elseif (strlen($password) < 6) {                                #El largo de la contrasenia
        $error = "La contraseña debe tener al menos 6 caracteres.";
    }
    else {
        // verificar correo duplicado
        if ($dao->verificar_correo($correo)) {
            $error = "Ya existe una cuenta con este correo.";
        } else {

            // === REGISTRAR ===
            $nuevo_id = $dao->registrar_usuario($nombre,$correo,$password,$rela_id_perfil,$fecha);


            // === AUTOLOGIN ===
            $_SESSION['usuario'] = $nombre;
            $_SESSION['id_usuario'] = $nuevo_id;
            $_SESSION['correo'] = $correo;
            $_SESSION['rol'] = $tipo_usuario;

            // Redirección inmediata al panel
            header("Location: home.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrarse - CyberCore</title>

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: #0f0f0f;
    color: white;
}

.container {
    width: 100%;
    max-width: 420px;
    margin: 80px auto;
    background: #1a1a1a;
    padding: 35px;
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(0, 255, 255, 0.20);
    text-align: center;
}

h2 {
    margin-bottom: 25px;
    color: #00eaff;
}

input {
    width: 100%;
    padding: 14px;
    margin-bottom: 18px;
    border-radius: 8px;
    border: none;
    outline: none;
    background: #2a2a2a;
    color: white;
    font-size: 15px;
}

button {
    width: 100%;
    padding: 14px;
    background: #00eaff;
    border: none;
    color: black;
    font-weight: bold;
    font-size: 16px;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
}

button:hover {
    background: #00b1cc;
}

a {
    color: #00eaff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

.msg-error {
    background: #ff3b3b;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 18px;
    font-weight: bold;
    color: white;
}
</style>
</head>
<body>

<div class="container">

    <h2>Crear cuenta</h2>

    <?php if ($error != "") { ?>
        <div class="msg-error"><?= $error ?></div>
    <?php } ?>

    <form method="POST">
        <input type="text" name="nombre" placeholder="Tu nombre" required>

        <input type="email" name="correo" placeholder="Correo electrónico" required>

        <input type="password" name="password" placeholder="Contraseña" required>

        <input type="password" name="password2" placeholder="Confirmar contraseña" required>

        <button type="submit" name="registrar">Registrarme</button>
    </form>

    <p style="margin-top: 16px;">¿Ya tenés una cuenta? 
        <a href="index.php">Iniciar sesión</a>
    </p>

</div>

</body>
</html>
