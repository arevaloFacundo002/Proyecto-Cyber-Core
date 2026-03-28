<?php  
session_start();
include "../conexion.php";

// Validar login
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit();
}

// Validar ID cliente
if (!isset($_GET['id'])) {
    echo "Error: No se recibió el ID del cliente.";
    exit();
}

$id_cliente = $_GET['id'];

// Obtener datos del cliente + provincia actual
$sql = "SELECT c.*, u.correo, l.rela_id_provincias
        FROM clientes c
        INNER JOIN usuarios u ON u.id_usuario = c.rela_id_usuario
        INNER JOIN localidades l ON l.id_localidades = c.rela_id_localidades
        WHERE c.id_cliente = ?";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_cliente);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$cliente = mysqli_fetch_assoc($res);

if (!$cliente) {
    echo "Cliente no encontrado.";
    exit();
}

// Obtener provincias
$prov_q = "SELECT * FROM provincias ORDER BY nombre_provincia ASC";
$prov_r = mysqli_query($conexion, $prov_q);

// Obtener localidades según la provincia actual del cliente
$loc_q = "SELECT * FROM localidades WHERE rela_id_provincias = ? ORDER BY nombre_localidad ASC";
$stmt2 = mysqli_prepare($conexion, $loc_q);
mysqli_stmt_bind_param($stmt2, "i", $cliente['rela_id_provincias']);
mysqli_stmt_execute($stmt2);
$loc_r = mysqli_stmt_get_result($stmt2);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Cliente</title>

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: #f4f4f4;
}

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

.form-container {
    width: 500px;
    margin: 40px auto;
    background: white;
    padding: 35px;
    border-radius: 16px;
    box-shadow: 0 0 12px rgba(0,0,0,0.15);
}

h2 { 
    text-align: center; 
    margin-bottom: 25px; 
    font-size: 24px;
}

label {
    font-weight: bold;
    margin-top: 10px;
    display: block;
    color: #333;
}

input, select {
    width: 100%;
    padding: 13px;
    margin-top: 5px;
    border: 1px solid #aaa;
    border-radius: 10px;
    font-size: 15px;
}

button {
    width: 100%;
    padding: 14px;
    background: #00eaff;
    border-radius: 20px;
    border: none;
    margin-top: 20px;
    font-weight: bold;
    cursor: pointer;
    font-size: 16px;
}
button:hover { background:#009ebd; }
</style>

<script>
// Cambio dinámico de localidades
function cargarLocalidades(idProvincia) {
    let xhr = new XMLHttpRequest();
    xhr.open("GET", "obtener_localidades.php?provincia=" + idProvincia, true);

    xhr.onload = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            document.getElementById("localidad").innerHTML = xhr.responseText;
        }
    };

    xhr.send();
}
</script>

</head>
<body>

<header>
    <div><strong>CyberCore - Panel Admin</strong></div>
    <a href="../usuarios/listar.php">← Volver</a>
</header>

<div class="form-container">

<h2>Editar Cliente</h2>

<form method="POST">

    <label>Nombre</label>
    <input type="text" name="nombre" value="<?= $cliente['nombre'] ?>" required>

    <label>Apellido</label>
    <input type="text" name="apellido" value="<?= $cliente['apellido'] ?>" required>

    <label>Teléfono</label>
    <input type="text" name="telefono" value="<?= $cliente['telefono'] ?>" required>

    <label>CUIL / CUIT</label>
    <input type="text" name="cuil" value="<?= $cliente['cuil_cuit'] ?>" required>

    <label>Dirección</label>
    <input type="text" name="direccion" value="<?= $cliente['direccion'] ?>" required>

    <label>Provincia</label>
    <select name="provincia" required onchange="cargarLocalidades(this.value)">
        <option value="">Seleccione provincia...</option>
        <?php while ($p = mysqli_fetch_assoc($prov_r)) { ?>
            <option value="<?= $p['id_provincias'] ?>"
                <?= ($p['id_provincias'] == $cliente['rela_id_provincias']) ? "selected" : "" ?>>
                <?= $p['nombre_provincia'] ?>
            </option>
        <?php } ?>
    </select>

    <label>Localidad</label>
    <select name="localidad" id="localidad" required>
        <?php while ($l = mysqli_fetch_assoc($loc_r)) { ?>
            <option value="<?= $l['id_localidades'] ?>"
                <?= ($l['id_localidades'] == $cliente['rela_id_localidades']) ? "selected" : "" ?>>
                <?= $l['nombre_localidad'] ?>
            </option>
        <?php } ?>
    </select>

    <label>Estado del Cliente</label>
    <select name="estado" required>
        <option value="activo"    <?= $cliente['cliente_estado']=="activo"?"selected":"" ?>>Activo</option>
        <option value="pausado"   <?= $cliente['cliente_estado']=="pausado"?"selected":"" ?>>Pausado</option>
        <option value="inactivo"  <?= $cliente['cliente_estado']=="inactivo"?"selected":"" ?>>Inactivo</option>
        <option value="bloqueado" <?= $cliente['cliente_estado']=="bloqueado"?"selected":"" ?>>Bloqueado</option>
    </select>

    <button type="submit" name="guardar">Guardar cambios</button>

</form>

</div>

</body>
</html>

<?php
// PROCESO POST
if (isset($_POST['guardar'])) {

    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $telefono = $_POST['telefono'];
    $cuil = $_POST['cuil'];
    $direccion = $_POST['direccion'];
    $loc = $_POST['localidad'];
    $estado = $_POST['estado'];

    // SIN PROVINCIA — ya no existe en la tabla clientes
    $update = "UPDATE clientes 
               SET nombre=?, apellido=?, direccion=?, telefono=?, 
                   cuil_cuit=?, rela_id_localidades=?, 
                   cliente_estado=? 
               WHERE id_cliente=?";

    $stmt3 = mysqli_prepare($conexion, $update);

    mysqli_stmt_bind_param(
        $stmt3,
        "sssssisi",
        $nombre, $apellido, $direccion, $telefono,
        $cuil, $loc, $estado, $id_cliente
    );

    mysqli_stmt_execute($stmt3);

    header("Location: ../usuarios/listar.php");
    exit;
}
?>
