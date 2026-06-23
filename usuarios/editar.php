<?php
require_once "../models/Usuario.php";
$user = new Usuario();

// Si no hay sesión lo echamos
require_once "../auth/auth.php";

if (!isset($_GET['id'])) {
    die('Usuario invalido');
}

$id = intval($_GET['id']);

$usuario = $user->obtener_usuario($id);

// Si no existe → error
if (!$usuario) {
    echo "Usuario no encontrado.";
    exit;
}

// Cuando envía →
if (isset($_POST['guardar'])) {

    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $rela_id_perfil = $_POST['nombre_perfil'];

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        die("Correo inválido");
    }


    if ($user->editar_usuario($nombre, $correo, $rela_id_perfil, $id)) {
        header("Location: listar.php");
        exit;
    }else{
        echo 'Error al editar Usuario';
    }

}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Usuario</title>

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

/* CARD */
.form-container {
    width: 420px;
    margin: 40px auto;
    background: white;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 0 12px rgba(0,0,0,0.15);
}

h2 {
    text-align: center;
    color: #111;
    margin-bottom: 20px;
}

/* Campos */
input, select {
    width: 100%;
    padding: 13px;
    margin: 10px 0;
    border: 1px solid #aaa;
    border-radius: 10px;
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
    margin-top: 10px;
    cursor: pointer;
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
</style>
</head>

<body>

<header>
    <div><strong>CyberCore - Panel Admin</strong></div>
    <a href="listar.php">← Volver</a>
</header>

<div class="form-container">
    <h2>Editar Usuario</h2>

    <form method="POST">
        <input type="text" name="nombre" value="<?php echo $usuario['nombre']; ?>" required>

        <input type="email" name="correo" value="<?php echo $usuario['correo']; ?>" required>

        <select name="nombre_perfil" required>
            <option value="3" <?= $usuario["nombre_perfil"]=="cliente" ? "selected":'' ?>>Cliente</option>
            <option value="1" <?= $usuario["nombre_perfil"]=="administrador" ? "selected":'' ?>>Administrador</option>
            <option value="2" <?= $usuario["nombre_perfil"]=="empleado" ? "selected":'' ?>>Empleado</option>
        </select>

        <button type="submit" name="guardar">Guardar cambios</button>
    </form>

    <a class="volver" href="listar.php">Volver al listado</a>
</div>

</body>
</html>