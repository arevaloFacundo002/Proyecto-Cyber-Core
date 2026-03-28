<?php
include "../conexion.php";
session_start();

// Validar ID de usuario
if (!isset($_GET['u'])) {
    echo "Error: No se recibió el ID del usuario.";
    exit();
}

$id_usuario = $_GET['u'];

// Obtener datos del usuario
$consulta = "SELECT nombre, correo FROM usuarios WHERE id_usuario = ?";
$stmt = mysqli_prepare($conexion, $consulta);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$usuario = mysqli_fetch_assoc($res);

// Obtener provincias
$prov_q = "SELECT id_provincias, nombre_provincia FROM provincias ORDER BY nombre_provincia ASC";
$provincias = mysqli_query($conexion, $prov_q);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Crear Cliente</title>

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
    width: 480px;
    margin: 40px auto;
    background: white;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 0 12px rgba(0,0,0,0.15);
}

h2 {
    text-align: center;
    color: #111;
    margin-bottom: 15px;
}

.info-user {
    background: #00eaff22;
    padding: 12px;
    border-radius: 10px;
    font-size: 14px;
    margin-bottom: 20px;
    border-left: 4px solid #00eaff;
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
</style>
</head>

<body>

<header>
    <div><strong>CyberCore - Panel Admin</strong></div>
    <a href="../usuarios/listar.php">← Volver</a>
</header>

<div class="form-container">

    <h2>Crear Cliente</h2>

    <div class="info-user">
        <strong>Usuario:</strong> <?= $usuario['nombre'] ?> (<?= $usuario['correo'] ?>)<br>
        Este cliente quedará asociado al usuario <strong>#<?= $id_usuario ?></strong>.
    </div>

    <form method="POST">

        <input type="text" name="nombre" placeholder="Nombre" required>
        <input type="text" name="apellido" placeholder="Apellido" required>
        <input type="text" name="direccion" placeholder="Dirección" required>
        <input type="text" name="telefono" placeholder="Teléfono" required>
        <input type="text" name="cuil" placeholder="CUIL/CUIT" required>

        <!-- PROVINCIA -->
        <select id="provincia" name="provincia" required>
            <option value="">Seleccione provincia...</option>
            <?php while ($p = mysqli_fetch_assoc($provincias)) { ?>
                <option value="<?= $p['id_provincias'] ?>">
                    <?= $p['nombre_provincia'] ?>
                </option>
            <?php } ?>
        </select>

        <!-- LOCALIDAD (se llenará con AJAX) -->
        <select id="localidad" name="localidad" required>
            <option value="">Seleccione provincia primero...</option>
        </select>

        <button type="submit" name="guardar">Guardar Cliente</button>
    </form>

</div>

<!-- AJAX -->
<script>
// Cuando cambia la provincia
document.getElementById("provincia").addEventListener("change", function() {
    
    let idProvincia = this.value;

    // Select de localidad
    let localidadSelect = document.getElementById("localidad");
    localidadSelect.innerHTML = "<option>Cargando...</option>";

    // Petición AJAX con fetch()
    fetch("obtener_localidades.php?provincia=" + idProvincia)
        .then(response => response.text())
        .then(data => {
            localidadSelect.innerHTML = data;
        });
});
</script>

</body>
</html>

<?php
// PROCESO POST
if (isset($_POST['guardar'])) {

    $nombre   = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];
    $cuil = $_POST['cuil'];
    $localidad = $_POST['localidad'];
    $fecha = date("Y-m-d H:i:s");

    // Insertar cliente
    $sql = "INSERT INTO clientes 
            (nombre, apellido, correo, direccion, contrasena, telefono, cuil_cuit, fecha_registro, rela_id_localidades, rela_id_usuario)
            VALUES (?, ?, ?, ?, '', ?, ?, ?, ?, ?)";

    $stmt2 = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt2, "sssssssii",
        $nombre,
        $apellido,
        $usuario['correo'],
        $direccion,
        $telefono,
        $cuil,
        $fecha,
        $localidad,
        $id_usuario
    );

    mysqli_stmt_execute($stmt2);

    header("Location: ../usuarios/listar.php");
    exit;
}
?>
