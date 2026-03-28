<?php
include "../conexion.php";

if (!isset($_GET['provincia'])) {
    echo "<option value=''>Error</option>";
    exit();
}

$idProvincia = $_GET['provincia'];

// Consultar localidades por provincia
$sql = "SELECT id_localidades, nombre_localidad 
        FROM localidades 
        WHERE rela_id_provincias = ?
        ORDER BY nombre_localidad ASC";

$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $idProvincia);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

echo "<option value=''>Seleccione localidad...</option>";

while ($row = mysqli_fetch_assoc($res)) {
    echo "<option value='{$row['id_localidades']}'>{$row['nombre_localidad']}</option>";
}
?>
