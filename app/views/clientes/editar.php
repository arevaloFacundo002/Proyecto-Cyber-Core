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
    <input type="text" name="calle" value="<?= $cliente['calle'] ?>" required>
    <input type="text" name="numero" value="<?= $cliente['numero_exterior'] ?>" required>
    <input type="text"name="barrio" value="<?= $cliente['barrio_colonia'] ?>">

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
                <?= ($localidad['id_localidad'] == $cliente['id_localidad']) ? "selected" : "" ?>>
                <?= $localidad['nombre_localidad'] ?>
            </option>
        <?php } ?>
    </select>

    <button type="submit" name="guardar">Guardar cambios</button>

</form>

</div>

</body>
</html>
