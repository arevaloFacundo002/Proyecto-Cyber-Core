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

$sql = "
SELECT 
    p.nombre AS producto,
    m.nombre_marca AS marca,
    SUM(dp.cantidad) AS total_vendido
FROM detalle_pedidos dp
INNER JOIN productos p ON p.id_producto = dp.rela_id_productos
INNER JOIN marcas m ON m.id_marca = p.rela_id_marca
GROUP BY p.id_producto, p.nombre, m.nombre_marca
ORDER BY total_vendido DESC
LIMIT 5;
";

$res = mysqli_query($conexion, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Top 5 Productos Más Vendidos</title>

<link href="../css/theme.css" rel="stylesheet">

<style>
    body {
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
    }
    .container {
        width: 90%;
        max-width: 700px;
        margin: 50px auto;
        padding: 20px;
    }
    h1 {
        text-align: center;
        color: var(--cc-primario);
        text-shadow: 0 0 10px rgba(0, 234, 255, 0.5);
        margin-bottom: 40px;
    }
    .card {
        background: var(--cc-superficie);
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 0 15px rgba(0, 234, 255, 0.15);
        margin-bottom: 15px;
        border: 1px solid var(--cc-borde);
    }
    .producto {
        font-size: 20px;
        color: var(--cc-primario);
        margin-bottom: 5px;
    }
    .card div {
        color: var(--cc-texto);
    }
    .empty {
        text-align: center;
        padding: 18px;
        color: var(--cc-texto-secundario);
    }
    .volver {
        display: inline-block;
        margin-top: 15px;
        background: var(--cc-primario);
        padding: 12px 20px;
        color: var(--cc-texto-sobre-primario);
        font-weight: bold;
        text-decoration: none;
        border-radius: 10px;
        transition: .2s;
    }
    .volver:hover { background: var(--cc-primario-hover); }
</style>
</head>

<body>
<div class="container">
    <h1>🔥 Top 5 Productos Más Vendidos</h1>

    <?php if ($res && mysqli_num_rows($res) > 0) { ?>
        <?php while ($f = mysqli_fetch_assoc($res)) { ?>
            <div class="card">
                <div class="producto"><b><?= htmlspecialchars($f['producto']) ?></b> (<?= htmlspecialchars($f['marca']) ?>)</div>
                <div>Total vendido: <b><?= htmlspecialchars($f['total_vendido']) ?></b></div>
            </div>
        <?php } ?>
    <?php } else { ?>
        <div class="empty">No se encontraron registros para esta consulta.</div>
    <?php } ?>

    <a href="menu_consultas.php" class="volver">⬅ Volver a Consultas</a>
</div>

<script src="../js/theme-toggle.js"></script>

</body>
</html>