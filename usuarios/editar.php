<?php
session_start();
include "../conexion.php";

// Si no hay sesión → lo echamos
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit;
}

// Obtener usuario
$id = $_GET['id'];
$sql = "SELECT * FROM usuarios WHERE id_usuario = ?";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$usuario = mysqli_fetch_assoc($res);

// Si no existe → error
if (!$usuario) {
    echo "Usuario no encontrado.";
    exit;
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
        <input type="text" name="nombre"
               value="<?php echo $usuario['nombre']; ?>" required>

        <input type="email" name="correo"
               value="<?php echo $usuario['correo']; ?>" required>

        

        <select name="tipo_usuario" required>
            <option value="cliente" <?php if($usuario["tipo_usuario"]=="cliente") echo "selected"; ?>>Cliente</option>
            <option value="admin" <?php if($usuario["tipo_usuario"]=="admin") echo "selected"; ?>>Administrador</option>
            <option value="empleado" <?php if($usuario["tipo_usuario"]=="empleado") echo "selected"; ?>>Empleado</option>
        </select>

        <button type="submit" name="guardar">Guardar cambios</button>
    </form>

    <a class="volver" href="listar.php">Volver al listado</a>
</div>

</body>
</html>

<?php

// Cuando envía →
if (isset($_POST['guardar'])) {

    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $rol = $_POST['tipo_usuario'];

    // Mantener contraseña anterior si no se cambió
    $pass = !empty($_POST['contrasena']) ?
            $_POST['contrasena'] : $usuario['contrasena'];

    $sql_update = "UPDATE usuarios 
                   SET nombre = ?, correo = ?, contrasena = ?, tipo_usuario = ?
                   WHERE id_usuario = ?";

    $stmt2 = mysqli_prepare($conexion, $sql_update);
    mysqli_stmt_bind_param($stmt2, "ssssi",
        $nombre, $correo, $pass, $rol, $id);

    mysqli_stmt_execute($stmt2);

    header("Location: listar.php");
    exit;
}
?>
