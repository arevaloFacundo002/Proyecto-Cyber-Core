<?php
require_once "../models/Cliente.php";
require_once "../models/Usuario.php";
require_once "../models/tablas_maestras/Ubicacion.php";
$cli = new Cliente();
$ubic = new Ubicacion();
$user = new Usuario();

require_once "../auth/auth.php";

// Validar ID de usuario
if (!isset($_GET['id_user'])) {
    echo "Error: No se recibió el ID del usuario.";
    exit();
}

$id_usuario = intval($_GET['id_user']);

// Obtener datos del usuario y validar que exista
$usuario = $user->obtener_usuario($id_usuario);
if (!$usuario) {
    die('El Usuario no existe');
}

//verificar que no tenga un cliente asociado
if($cli->existe_cliente($id_usuario)){
    die('El usuario ya tiene un cliente asociado');
}

// Obtener provincias
$provincias = $ubic->obtener_provincias();


// PROCESO POST
if (isset($_POST['guardar'])) {
    // Datos personales
    $nombre   = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $cuil = trim($_POST['cuil']);
    $telefono = trim($_POST['telefono']);
    $tipo_contacto = 1; //por defecto es celular

    // Dirección
    $calle = trim($_POST['calle']);
    $numero = trim($_POST['numero']);
    $barrio = trim($_POST['barrio']);
    $piso = trim($_POST['piso']);
    $referencia = trim($_POST['referencia']);
    $localidad = trim($_POST['localidad']);
    $fecha = date("Y-m-d H:i:s");

    // validaciones
    if (!is_numeric($cuil)) {
        die('El cuil debe ser un numero valido');
    }
    if ($localidad<=0) {
        die('Debe seleccionar una localidad valida');
    }


    //insertar direccion
    if (!$id_direccion = $ubic->insertar_direccion($calle,$numero,$barrio,$piso,$referencia,$localidad)) {
        die('Error al insertar direccion');
    }

    //Insertar cliente
    if($id_cliente = $cli->insertar_cliente($nombre, $apellido, $cuil, $fecha, $id_direccion, $id_usuario)
        and $cli->insertar_contacto_cliente($telefono,$tipo_contacto,$id_cliente)){
        header("Location: ../usuarios/listar.php");
        exit;
    }else{
        echo 'Error al insertar';
    }


}
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

        <label><b>Datos Personales:</b></label>
        <input type="text" name="nombre" placeholder="Nombre" required>
        <input type="text" name="apellido" placeholder="Apellido" required>
        <input type="text" name="telefono" placeholder="Teléfono" required>
        <input type="text" name="cuil" placeholder="CUIL/CUIT" required>

        <label ><b>Dirección:</b></label>
        <input type="text" name="calle" placeholder="Calle" required>
        <input type="text" name="numero" placeholder="Número" required>
        <input type="text" name="barrio" placeholder="Barrio">
        <input type="text" name="piso" placeholder="Piso / Departamento (OPCIONAL)">
        <textarea name="referencia" placeholder=" Referencias (OPCIONAL)"></textarea>

        <!-- PROVINCIA -->
        <select id="provincia" name="provincia" required>
            <option value="">Seleccione provincia...</option>
            <?php foreach($provincias as $provincia) { ?>
                <option value="<?php echo $provincia['id_provincia'] ?>">
                    <?php echo $provincia['nombre_provincia'] ?>
                </option>
            <?php } ?>
        </select>

        <!-- LOCALIDAD -->
        <select id="localidad" name="localidad" required>
            <option value="">Seleccione provincia primero...</option>
        </select>

        <button type="submit" name="guardar">Guardar Cliente</button>
    </form>

</div>

<!-- CON JSON -->
 <script>
document.getElementById("provincia").addEventListener("change", function() {

    let idProvincia = this.value;
    let localidadSelect = document.getElementById("localidad");

    // Reset
    localidadSelect.innerHTML = "<option>Cargando...</option>";

    if (!idProvincia) return;

    fetch("obtener_localidades.php?provincia=" + idProvincia)
        .then(response => response.json()) // 👈 ahora es JSON
        .then(data => {

            localidadSelect.innerHTML = "";

            if (data.length === 0) {
                localidadSelect.innerHTML = "<option>No hay localidades</option>";
                return;
            }

            // Crear options dinámicamente
            data.forEach(localidad => {
                let option = document.createElement("option");
                option.value = localidad.id_localidad;
                option.textContent = localidad.nombre_localidad;
                localidadSelect.appendChild(option);
            });

        })
        .catch(error => {
            console.error("Error:", error);
            localidadSelect.innerHTML = "<option>Error al cargar</option>";
        });
});
</script>

</body>
</html>