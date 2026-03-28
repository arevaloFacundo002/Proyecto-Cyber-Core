<?php 
include "../conexion.php";

// ======= RANGO (solo para mostrar, NO modifica la consulta) =======
$desde = isset($_GET['desde']) && $_GET['desde'] !== '' ? $_GET['desde'] : null;
$hasta = isset($_GET['hasta']) && $_GET['hasta'] !== '' ? $_GET['hasta'] : null;

// Pedidos: detalle completo con productos, cliente y envío
$sql = "
SELECT 
    pd.id_pedidos,
    c.nombre AS cliente,
    c.apellido,
    p.nombre AS producto,
    dp.cantidad,
    dp.subtotal_final,
    e.empresa_transporte,
    e.estado_envio
FROM pedidos pd
INNER JOIN clientes c ON pd.rela_id_cliente = c.id_cliente
INNER JOIN detalle_pedidos dp ON dp.rela_id_pedidos = pd.id_pedidos
INNER JOIN productos p ON p.id_productos = dp.rela_id_productos
LEFT JOIN envios e ON e.rela_id_pedidos = pd.id_pedidos
ORDER BY pd.id_pedidos DESC
";

$res = mysqli_query($conexion, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Consulta 2 - Pedidos detallados</title>

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
        margin-bottom: 8px;
    }
    .subtitulo {
        text-align: center;
        color: #cfefff;
        margin-bottom: 18px;
        font-size: 14px;
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
    <h1>Consulta 2 — Pedidos y detalle de envío</h1>

    <!-- === RANGO VISUAL === -->
    <div class="subtitulo">
        <?php if ($desde && $hasta): ?>
            Consulta desde <strong><?= htmlspecialchars($desde) ?></strong> hasta <strong><?= htmlspecialchars($hasta) ?></strong>
        <?php else: ?>
            Consulta desde <strong>inicio</strong> hasta <strong>últimos datos</strong> (sin rango aplicado)
        <?php endif; ?>
    </div>

    <?php if ($res && mysqli_num_rows($res) > 0) { ?>
    <table>
        <tr>
            <th>N° Pedido</th>
            <th>Cliente</th>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Subtotal</th>
            <th>Transporte</th>
            <th>Estado envío</th>
        </tr>
        <?php while ($f = mysqli_fetch_assoc($res)) { ?>
        <tr>
            <td><?= htmlspecialchars($f['id_pedidos']) ?></td>
            <td><?= htmlspecialchars($f['cliente'] . ' ' . $f['apellido']) ?></td>
            <td><?= htmlspecialchars($f['producto']) ?></td>
            <td><?= htmlspecialchars($f['cantidad']) ?></td>
            <td>$<?= number_format($f['subtotal_final'] ?? 0, 2) ?></td>
            <td><?= htmlspecialchars($f['empresa_transporte'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($f['estado_envio'] ?? 'N/A') ?></td>
        </tr>
        <?php } ?>
    </table>
    <?php } else { ?>
        <div class="empty">No se encontraron pedidos.</div>
    <?php } ?>

    <a href="menu_consultas.php" class="volver">⬅ Volver a Consultas</a>
</div>
</body>
</html>
