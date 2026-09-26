 <?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario']) ||
   ($_SESSION['rol'] != "administrador" && $_SESSION['rol'] != "empleado")) {
    header("Location: ../home.php");
    exit;
}

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
INNER JOIN marcas m ON p.rela_id_marca = m.id_marca
LEFT JOIN detalle_compras dc ON dc.rela_id_productos = p.id_producto
LEFT JOIN compras cp ON cp.id_compras = dc.rela_id_compras
LEFT JOIN proveedores pr ON cp.rela_id_proveedores = pr.id_proveedores
WHERE cp.fecha_compra = (
    SELECT MAX(c2.fecha_compra)
    FROM detalle_compras dc2
    INNER JOIN compras c2 ON c2.id_compras = dc2.rela_id_compras
    WHERE dc2.rela_id_productos = p.id_producto
)
ORDER BY cp.fecha_compra DESC
";

$res = mysqli_query($conexion, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consulta 1 - Último precio de compra por producto</title>

<link href="../css/theme.css" rel="stylesheet">

<style>
    body {
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
    }
    .container {
        width: 90%;
        max-width: 1100px;
        margin: 40px auto;
        background: var(--cc-superficie);
        padding: 30px;
        border-radius: 12px;
        border: 1px solid var(--cc-borde);
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.25);
    }
    h1 {
        text-align: center;
        color: var(--cc-primario);
        text-shadow: 0 0 12px rgba(0, 234, 255, 0.4);
        margin-bottom: 25px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    th {
        background: rgba(0, 234, 255, 0.12);
        color: var(--cc-primario);
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid var(--cc-borde);
    }
    td {
        padding: 12px;
        border-bottom: 1px solid var(--cc-borde);
        color: var(--cc-texto);
    }
    tr:hover {
        background-color: rgba(0, 234, 255, 0.07);
    }
    .volver {
        display: inline-block;
        margin-top: 30px;
        background: var(--cc-primario);
        padding: 12px 20px;
        color: var(--cc-texto-sobre-primario);
        font-weight: bold;
        text-decoration: none;
        border-radius: 10px;
        transition: .2s;
    }
    .volver:hover { background: var(--cc-primario-hover); }
    .empty {
        text-align: center;
        padding: 18px;
        color: var(--cc-texto-secundario);
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

<script src="../js/theme-toggle.js"></script>

</body>
</html>