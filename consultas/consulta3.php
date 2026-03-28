<?php
include "../conexion.php";

$sql = "
SELECT 
    p.nombre AS producto,
    m.nombre_marca AS marca,
    SUM(dp.cantidad) AS total_vendido
FROM detalle_pedidos dp
INNER JOIN productos p ON p.id_productos = dp.rela_id_productos
INNER JOIN marcas m ON m.id_marcas = p.rela_id_marcas
GROUP BY p.id_productos, p.nombre, m.nombre_marca
ORDER BY total_vendido DESC
LIMIT 5;
";

$res = mysqli_query($conexion, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Top 5 Productos Más Vendidos</title>
<style>
    body {
        margin: 0;
        font-family: 'Segoe UI';
        background: #0a0a0a;
        color: white;
    }
    .container {
        width: 80%;
        margin: 50px auto;
        padding: 20px;
    }
    h1 {
        text-align: center;
        color: #00eaff;
        text-shadow: 0 0 10px #00eaff88;
        margin-bottom: 40px;
    }
    .card {
        background: #111;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 0 15px #00eaff33;
        margin-bottom: 15px;
        border: 1px solid #00eaff33;
    }
    .producto {
        font-size: 20px;
        color: #00eaff;
        margin-bottom: 5px;
    }
</style>
</head>

<body>
<div class="container">
    <h1>🔥 Top 5 Productos Más Vendidos</h1>

    <?php while ($f = mysqli_fetch_assoc($res)) { ?>
        <div class="card">
            <div class="producto"><b><?= $f['producto'] ?></b> (<?= $f['marca'] ?>)</div>
            <div>Total vendido: <b><?= $f['total_vendido'] ?></b></div>
        </div>
    <?php } ?>

</div>
</body>
</html>
