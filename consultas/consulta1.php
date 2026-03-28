<?php
include "../conexion.php";

// Productos + Proveedor + Último precio de compra
$sql = "
SELECT 
    p.nombre AS producto,
    m.nombre_marca AS marca,
    pr.nombre_apellido AS proveedor,
    dc.precio AS ultimo_precio_compra,
    cp.fecha_compra
FROM productos p
INNER JOIN marcas m ON p.rela_id_marcas = m.id_marcas
LEFT JOIN detalle_compras dc ON dc.rela_id_productos = p.id_productos
LEFT JOIN compras cp ON cp.id_compras = dc.rela_id_compras
LEFT JOIN proveedores pr ON cp.rela_id_proveedores = pr.id_proveedores
WHERE cp.fecha_compra = (
    SELECT MAX(c2.fecha_compra)
    FROM detalle_compras dc2
    INNER JOIN compras c2 ON c2.id_compras = dc2.rela_id_compras
    WHERE dc2.rela_id_productos = p.id_productos
)
ORDER BY cp.fecha_compra DESC
";

$res = mysqli_query($conexion, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Consulta 1 - Último precio de compra por producto</title>

<!-- ESTILO ESTÁNDAR -->
<style>
    body {
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
        background: linear-gradient(135deg, #0a0a0a, #0f1a20);
        color: #e6e6e6;
    }
    .container {
        width: 90%;
        max-width: 1100px;
        margin: 40px auto;
        background: #111;
        padding: 30px;
        border-radius: 12px;
        border: 1px solid #00eaff33;
        box-shadow: 0 0 15px #000;
    }
    h1 {
        text-align: center;
        color: #00eaff;
        text-shadow: 0 0 12px #00eaffaa;
        margin-bottom: 25px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: #0d0d0d;
        border-radius: 10px;
        overflow: hidden;
        margin-top: 20px;
    }
    th {
        background: #00eaff33;
        color: #00eaff;
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #00eaff44;
    }
    td {
        padding: 12px;
        border-bottom: 1px solid #1f1f1f;
    }
    tr:hover {
        background-color: #00eaff11;
    }
    .volver {
        display: inline-block;
        margin-top: 30px;
        background: #00eaff;
        padding: 12px 20px;
        color: black;
        font-weight: bold;
        text-decoration: none;
        border-radius: 10px;
        transition: .3s;
    }
    .volver:hover { background: #009ac0; }
    .empty {
        text-align:center;
        padding:18px;
        color:#ccc;
    }
</style>
</head>
<body>
<div class="container">
    <h1>Consulta 1 — Último precio de compra por producto</h1>

    <?php if ($res && mysqli_num_rows($res) > 0) { ?>
    <table>
        <tr>
            <th>Producto</th>
            <th>Marca</th>
            <th>Último precio</th>
            <th>Proveedor</th>
            <th>Fecha compra</th>
        </tr>
        <?php while ($f = mysqli_fetch_assoc($res)) { ?>
        <tr>
            <td><?= htmlspecialchars($f['producto']) ?></td>
            <td><?= htmlspecialchars($f['marca']) ?></td>
            <td>$<?= number_format($f['ultimo_precio_compra'] ?? 0, 2) ?></td>
            <td><?= htmlspecialchars($f['proveedor'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($f['fecha_compra'] ?? 'N/A') ?></td>
        </tr>
        <?php } ?>
    </table>
    <?php } else { ?>
        <div class="empty">No se encontraron registros para esta consulta.</div>
    <?php } ?>

    <a href="menu_consultas.php" class="volver">⬅ Volver a Consultas</a>
</div>
</body>
</html>
