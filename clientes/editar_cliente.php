<?php  
session_start();
require_once "../conexion.php";
$dao = new UserDao();

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

$id_cliente = intval($_GET['id']);

// Obtener datos del cliente + provincia actual
$cliente = $dao->cliente_provincia($id_cliente);

if (!$cliente) {
    echo "Cliente no encontrado.";
    exit();
}
$rela_id_provincia = $cliente['rela_id_provincia'];

// Obtener provincias
$provincias = $dao->obtener_provincias();

// Obtener localidades según la provincia actual del cliente
$localidades = $dao->localidades_provincia_del_cliente($rela_id_provincia);


// PROCESO POST
if (isset($_POST['guardar'])) {

    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $telefono = $_POST['telefono'];
    $cuil = $_POST['cuil'];
    $direccion = $_POST['direccion'];
    $localidad = $_POST['localidad'];
    $tipo_contacto = 1;

    // SIN PROVINCIA — ya no existe en la tabla clientes
    if ($dao->actualizar_cliente($nombre, $apellido, $direccion,$cuil, $localidad, $id_cliente)
        and $dao->actualizar_contacto_cliente($telefono,$tipo_contacto,$id_cliente)) {
        header("Location: ../usuarios/listar.php");
        exit;
    }else{
        echo 'Error en la actualizacion';
    }
}
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
        <?php foreach($provincias as $provincia) { ?>
            <option value="<?= $provincia['id_provincia'] ?>"
                <?= ($provincia['id_provincia'] == $cliente['rela_id_provincia']) ? "selected" : "" ?>>
                <?= $provincia['nombre_provincia'] ?>
            </option>
        <?php } ?>
    </select>

    <label>Localidad</label>
    <select name="localidad" id="localidad" required>
        <?php foreach($localidades as $localidad) { ?>
            <option value="<?= $localidad['id_localidad'] ?>"
                <?= ($localidad['id_localidad'] == $cliente['rela_id_localidades']) ? "selected" : "" ?>>
                <?= $localidad['nombre_localidad'] ?>
            </option>
        <?php } ?>
    </select>

    <button type="submit" name="guardar">Guardar cambios</button>

</form>

</div>

</body>
</html>