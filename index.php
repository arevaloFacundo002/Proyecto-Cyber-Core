<?php
session_start();
require_once "conexion.php";
$dao = new UserDao();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo']?? null;
    $password = $_POST['password'] ?? null;

    $usuario = $dao->login($correo);

    if($usuario && password_verify($password, $usuario['password'])){

        if ($usuario['estado'] == "bloqueado" || $usuario['estado'] == "inactivo") {
            echo "<script>alert('Tu cuenta está bloqueada o inactiva. Acceso denegado.'); 
                window.location='index.php';</script>";
            exit;
        }

        $_SESSION['usuario'] = $usuario['nombre'];
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['rol'] = $usuario['tipo_usuario'];
        $_SESSION['correo'] = $usuario['correo'];


        if ($usuario['tipo_usuario'] == 'administrador') {            
                header('Location:inicio.php');
                exit();

        }elseif ($usuario['tipo_usuario'] == 'cliente' || $usuario['tipo_usuario'] == 'usuario') {
                header('Location:home.php');
                exit();
                
        }elseif ($usuario['tipo_usuario'] == 'empleado'){
                header('Location:inicio.php');
                exit();
       }else {
            echo "Rol No reconocido";
       }

    }else {
        echo "<script>alert('Correo o contraseña incorrectos'); window.location='index.php';</script>";
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

/* Caja de login */
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
    letter-spacing: 2px;
    text-shadow: 0 0 10px #00eaff;
}

h2 {
    margin-bottom: 20px;
    color: #00eaff;
}

/* Inputs */
input {
    width: 90%;
    padding: 14px;
    margin: 10px 0;
    border-radius: 8px;
    border: none;
    outline: none;
    background: #1c1c1c;
    color: white;
    font-size: 16px;
    transition: 0.3s;
}

input:focus {
    box-shadow: 0 0 10px #00eaff;
    background: #222;
}

/* Botón */
button {
    width: 95%;
    padding: 14px;
    margin-top: 15px;
    background: #00eaff;
    color: black;
    font-weight: bold;
    border-radius: 25px;
    font-size: 18px;
    cursor: pointer;
    transition: 0.3s;
    border: none;
}

button:hover {
    background: #009ac0;
}

/* Link */
.registro {
    margin-top: 15px;
    color: #bbb;
}

.registro a {
    color: #00eaff;
    text-decoration: none;
}

.registro a:hover {
    text-decoration: underline;
}
</style>
</head>

<body>

<div class="login-box">

    <div class="logo">CYBERCORE</div>
    <h2>Iniciar sesión</h2>

   <form action="" method="POST">

        <input type="email" name="correo" placeholder="Correo electrónico" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit">Ingresar</button>

    </form>

    <div class="registro">
        ¿No tenés una cuenta? <a href="registro.php">Registrate aquí</a>
    </div>
</div>

</body>
</html>
