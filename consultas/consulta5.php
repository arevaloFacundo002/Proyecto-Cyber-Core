<?php
include "../conexion.php";

$sql = "
SELECT 
    m.nombre_marca,
    COUNT(p.id_productos) AS total_productos
FROM marcas m
LEFT JOIN productos p ON p.rela_id_marcas = m.id_marcas
GROUP BY m.id_marcas, m.nombre_marca
ORDER BY total_productos DESC;
";

$res = mysqli_query($conexion, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Marcas con más productos</title>
<style>
    body {
        margin: 0;
        background: #090909;
        font-family: 'Segoe UI';
        color: white;
    }
    .container {
        width: 80%;
        margin: 50px auto;
    }
    h1 {
        text-align: center;
        color: #00eaff;
        margin-bottom: 35px;
        text-shadow: 0 0 10px #00eaffaa;
    }
    .card {
        background: #111;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 15px;
        border: 1px solid #00eaff33;
        box-shadow: 0 0 15px #00eaff33;
    }
    .marca {
        font-size: 20px;
        color: #00eaff;
    }
</style>
</head>

<body>
<div class="container">
    <h1>🏷️ Marcas con Más Productos</h1>

    <?php while ($f = mysqli_fetch_assoc($res)) { ?>
        <div class="card">
            <div class="marca"><b><?= $f['nombre_marca'] ?></b></div>
            <div>Total productos: <b><?= $f['total_productos'] ?></b></div>
        </div>
    <?php } ?>

</div>
</body>
</html>
