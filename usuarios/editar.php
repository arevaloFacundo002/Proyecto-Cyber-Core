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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editar Usuario - CyberCore</title>

<link href="../css/theme.css" rel="stylesheet">

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
}

/* CARD */
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

/* Campos */
input, select {
    width: 100%;
    box-sizing: border-box;
    padding: 13px;
    margin: 10px 0;
    border: 1px solid var(--cc-borde);
    border-radius: 10px;
    background: var(--cc-fondo);
    color: var(--cc-texto);
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
    margin-top: 10px;
    cursor: pointer;
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
</style>
</head>

<body>

<?php
$ruta_raiz = '../';
require __DIR__ . '/../includes/header_admin.php';
?>




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

<script src="../js/theme-toggle.js"></script>

</body>
</html>