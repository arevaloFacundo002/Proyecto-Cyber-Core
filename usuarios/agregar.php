<?php
require_once "../models/Usuario.php";
$user = new Usuario();

$error='';

// Si no hay sesión fuera
require_once "../auth/auth.php";

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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Agregar Usuario - CyberCore</title>

<link href="../css/theme.css" rel="stylesheet">

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
}

/* FORM CARD */
.form-container {
    width: 420px;
    max-width: 90%;
    margin: 40px auto;
    background: var(--cc-superficie);
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 0 15px rgba(0, 234, 255, 0.15);
    border: 1px solid var(--cc-borde);
    box-sizing: border-box;
}

.form-container h2 {
    text-align: center;
    color: var(--cc-primario);
    margin-bottom: 20px;
}

/* Inputs */
input, select {
    width: 100%;
    box-sizing: border-box;
    padding: 13px;
    margin: 10px 0;
    border-radius: 10px;
    border: 1px solid var(--cc-borde);
    background: var(--cc-fondo);
    color: var(--cc-texto);
    font-size: 15px;
    outline: none;
}

input:focus, select:focus {
    border-color: var(--cc-primario);
}

/* Botón */
.form-container button {
    width: 100%;
    padding: 14px;
    background: var(--cc-primario);
    color: var(--cc-texto-sobre-primario);
    border: none;
    border-radius: 20px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 12px;
    transition: 0.2s;
}

.form-container button:hover {
    background: var(--cc-primario-hover);
}

/* Link volver */
.volver {
    display: block;
    text-align: center;
    margin-top: 15px;
    text-decoration: none;
    color: var(--cc-primario);
    font-weight: bold;
}

.volver:hover {
    color: var(--cc-primario-hover);
}

.msg-error {
    background: var(--cc-peligro);
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 18px;
    font-weight: bold;
    color: #fff;
}

</style>
</head>

<body>

<?php
$ruta_raiz = '../';
require __DIR__ . '/../includes/header_admin.php';
?>




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

<script src="../js/theme-toggle.js"></script>

</body>
</html>