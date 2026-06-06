<?php
require_once "../models/Usuario.php";
$user = new Usuario();

$error='';

// Si no hay sesión fuera
require_once "../auth.php";

//  PROCESAR FORMULARIO 
if (isset($_POST['guardar'])) {

    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $password = $_POST['password'];
    $rela_id_perfil = $_POST['rol'];
    $fecha = date("Y-m-d");

    // VALIDACIONES
    if (strlen($nombre) < 3) {
        $error = "El nombre debe tener al menos 3 caracteres.";     #El largo del nombre
    }
    elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {          #Validar correo
        $error = "El correo no es válido.";
    }
    elseif (strlen($password) < 6) {                                #El largo de la contrasenia
        $error = "La contraseña debe tener al menos 6 caracteres.";
    }
    elseif($user->verificar_correo($correo)) {                       // verificar correo duplicado
        $error = "Ya existe una cuenta con este correo.";
    } else {

        if ($user->agregar_usuario_panel($nombre,$password,$correo,$fecha,$rela_id_perfil)) {  //agregamos el usuario
            header("Location: listar.php");                    
            exit;
        }else {
           echo 'Error al insertar';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Agregar Usuario</title>

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: #f4f4f4;
}

/* HEADER */
header {
    background: #0a0a0a;
    padding: 18px 40px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

header a {
    color: #00eaff;
    text-decoration: none;
    font-weight: bold;
}

/* FORM CARD */
.form-container {
    width: 420px;
    margin: 40px auto;
    background: white;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 0 12px rgba(0,0,0,0.15);
}

.form-container h2 {
    text-align: center;
    color: #222;
    margin-bottom: 20px;
}

/* Inputs */
input, select {
    width: 100%;
    padding: 13px;
    margin: 10px 0;
    border-radius: 10px;
    border: 1px solid #aaa;
    font-size: 15px;
    outline: none;
}

input:focus, select:focus {
    border-color: #00b1cc;
}

/* Botón */
button {
    width: 100%;
    padding: 14px;
    background: #00eaff;
    border: none;
    border-radius: 20px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 12px;
}

button:hover {
    background: #009ebd;
}

/* Link volver */
.volver {
    display: block;
    text-align: center;
    margin-top: 15px;
    text-decoration: none;
    color: #00b1cc;
    font-weight: bold;
}

.volver:hover {
    color: #00809b;
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

<header>
    <div><strong>CyberCore - Panel Admin</strong></div>
    <a href="listar.php">← Volver</a>
</header>

<div class="form-container">
    <h2>Agregar Usuario</h2>

    <?php if ($error!='') { ?>
        <div class="msg-error"><?php echo $error ?></div>
    <?php } ?>

    <form method="POST">

        <input type="text" name="nombre" placeholder="Nombre completo" required>

        <input type="email" name="correo" placeholder="Correo electrónico" required>

        <input type="password" name="password" placeholder="Contraseña" required>

        <select name="rol" required>
            <option value="4">Usuario</option>
            <option value="3">Cliente</option>
            <option value="1">Administrador</option>
            <option value="2">Empleado</option>
        </select>

        <button type="submit" name="guardar">Guardar usuario</button>
    </form>

    <a class="volver" href="listar.php">Volver al listado</a>
</div>

</body>
</html>

