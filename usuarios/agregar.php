<?php
session_start();
include "../conexion.php";

// Si no hay sesión → fuera
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit;
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

</style>
</head>

<body>

<header>
    <div><strong>CyberCore - Panel Admin</strong></div>
    <a href="listar.php">← Volver</a>
</header>

<div class="form-container">
    <h2>Agregar Usuario</h2>

    <form method="POST">

        <input type="text" name="nombre" placeholder="Nombre completo" required>

        <input type="email" name="correo" placeholder="Correo electrónico" required>

        <input type="password" name="contrasena" placeholder="Contraseña" required>

        <select name="tipo_usuario" required>
            <option value="cliente">Cliente</option>
            <option value="admin">Administrador</option>
            <option value="empleado">Empleado</option>
        </select>

        <button type="submit" name="guardar">Guardar usuario</button>

    </form>

    <a class="volver" href="listar.php">Volver al listado</a>
</div>

</body>
</html>

<?php

// ------------------ PROCESAR FORMULARIO ------------------ //

if (isset($_POST['guardar'])) {

    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $pass = $_POST['contrasena'];
    $rol = $_POST['tipo_usuario'];

    $fecha = date("Y-m-d");

    $sql = "INSERT INTO usuarios (nombre, correo, contrasena, tipo_usuario, fecha_registro)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, "sssss",
        $nombre, $correo, $pass, $rol, $fecha);

    mysqli_stmt_execute($stmt);

    header("Location: listar.php");
    exit;
}
?>
