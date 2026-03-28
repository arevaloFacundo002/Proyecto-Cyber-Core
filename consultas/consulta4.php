<?php
include "../conexion.php";

$sql = "
SELECT 
    c.nombre,
    c.apellido,
    c.correo,
    COUNT(p.id_pedidos) AS cantidad_pedidos
FROM pedidos p
INNER JOIN clientes c ON p.rela_id_cliente = c.id_cliente
GROUP BY c.id_cliente, c.nombre, c.apellido, c.correo
ORDER BY cantidad_pedidos DESC;
";

$res = mysqli_query($conexion, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Clientes con más pedidos</title>
<style>
    body {
        margin: 0;
        background: #0d0d0d;
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
        text-shadow: 0 0 10px #00eaffaa;
        margin-bottom: 40px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: #111;
        box-shadow: 0 0 15px #00eaff33;
        border-radius: 10px;
        overflow: hidden;
    }
    th {
        background: #00eaff33;
        padding: 12px;
        color: #00eaff;
    }
    td {
        padding: 12px;
        border-bottom: 1px solid #222;
    }
    tr:hover {
        background: #00eaff11;
    }
</style>
</head>

<body>
<div class="container">
    <h1>👥 Clientes con Más Pedidos</h1>

    <table>
        <tr>
            <th>Cliente</th>
            <th>Email</th>
            <th>Pedidos Realizados</th>
        </tr>

        <?php while ($f = mysqli_fetch_assoc($res)) { ?>
        <tr>
            <td><?= $f['nombre'] . " " . $f['apellido'] ?></td>
            <td><?= $f['correo'] ?></td>
            <td><?= $f['cantidad_pedidos'] ?></td>
        </tr>
        <?php } ?>

    </table>
</div>
</body>
</html>
