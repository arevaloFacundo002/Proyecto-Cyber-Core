<?php
include "conexion.php";
session_start();

// Variables para mensajes
$error = "";
$ok = "";

if (isset($_POST['registrar'])) {

    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $pass = trim($_POST['contrasena']);
    $pass2 = trim($_POST['contrasena2']);
    $fecha = date("Y-m-d");
    $tipo = "cliente"; // por defecto cliente

    // === VALIDACIONES ===

    if ($pass !== $pass2) {
        $error = "Las contraseñas no coinciden.";
    }
    elseif (strlen($pass) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    }
    else {
        // verificar correo duplicado
        $sqlCheck = "SELECT id_usuario FROM usuarios WHERE correo = ?";
        $stmtCheck = mysqli_prepare($conexion, $sqlCheck);
        mysqli_stmt_bind_param($stmtCheck, "s", $correo);
        mysqli_stmt_execute($stmtCheck);
        $resCheck = mysqli_stmt_get_result($stmtCheck);

        if (mysqli_num_rows($resCheck) > 0) {
            $error = "Ya existe una cuenta con este correo.";
        } else {

            // === REGISTRAR ===
            $sql = "INSERT INTO usuarios (nombre, correo, contrasena, tipo_usuario, fecha_registro)
                    VALUES (?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($conexion, $sql);
            mysqli_stmt_bind_param($stmt, "sssss", 
                $nombre, $correo, $pass, $tipo, $fecha
            );

            mysqli_stmt_execute($stmt);

            // Obtener ID recién insertado
            $nuevo_id = mysqli_insert_id($conexion);

            // === AUTOLOGIN ===
            $_SESSION['usuario'] = $nombre;
            $_SESSION['id_usuario'] = $nuevo_id;
            $_SESSION['correo'] = $correo;
            $_SESSION['rol'] = $tipo;

            // Redirección inmediata al panel
            header("Location: inicio.php");
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

.msg-ok {
    background: #00d18a;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 18px;
    font-weight: bold;
    color: black;
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

        <input type="password" name="contrasena" placeholder="Contraseña" required>

        <input type="password" name="contrasena2" placeholder="Confirmar contraseña" required>

        <button type="submit" name="registrar">Registrarme</button>
    </form>

    <p style="margin-top: 16px;">¿Ya tenés una cuenta? 
        <a href="index.php">Iniciar sesión</a>
    </p>

</div>

</body>
</html>
