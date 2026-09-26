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
    c.nombre,
    c.apellido,
    u.correo,
    COUNT(p.id_pedidos) AS cantidad_pedidos
FROM pedidos p
INNER JOIN clientes c ON p.rela_id_cliente = c.id_cliente
LEFT JOIN usuarios u ON c.rela_id_usuario = u.id_usuario
GROUP BY c.id_cliente, c.nombre, c.apellido, u.correo
ORDER BY cantidad_pedidos DESC;
";

$res = mysqli_query($conexion, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Clientes con más pedidos</title>

<link href="../css/theme.css" rel="stylesheet">

<style>
    body {
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
    }
    .container {
        width: 90%;
        max-width: 900px;
        margin: 50px auto;
    }
    h1 {
        text-align: center;
        color: var(--cc-primario);
        text-shadow: 0 0 10px rgba(0, 234, 255, 0.5);
        margin-bottom: 40px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: var(--cc-superficie);
        box-shadow: 0 0 15px rgba(0, 234, 255, 0.1);
        border-radius: 10px;
        overflow: hidden;
    }
    th {
        background: rgba(0, 234, 255, 0.12);
        padding: 12px;
        color: var(--cc-primario);
        text-align: left;
    }
    td {
        padding: 12px;
        border-bottom: 1px solid var(--cc-borde);
        color: var(--cc-texto);
    }
    tr:hover {
        background: rgba(0, 234, 255, 0.07);
    }
    .empty {
        text-align: center;
        padding: 18px;
        color: var(--cc-texto-secundario);
    }
    .volver {
        display: inline-block;
        margin-top: 25px;
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
    <h1>👥 Clientes con Más Pedidos</h1>

    <?php if ($res && mysqli_num_rows($res) > 0) { ?>
    <table>
        <tr>
            <th>Cliente</th>
            <th>Email</th>
            <th>Pedidos Realizados</th>
        </tr>

        <?php while ($f = mysqli_fetch_assoc($res)) { ?>
        <tr>
            <td><?= htmlspecialchars($f['nombre'] . " " . $f['apellido']) ?></td>
            <td><?= htmlspecialchars($f['correo'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($f['cantidad_pedidos']) ?></td>
        </tr>
        <?php } ?>

    </table>
    <?php } else { ?>
        <div class="empty">No se encontraron clientes con pedidos.</div>
    <?php } ?>

    <a href="menu_consultas.php" class="volver">⬅ Volver a Consultas</a>
</div>

<script src="../js/theme-toggle.js"></script>

</body>
</html>