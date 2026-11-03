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

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function plata($v) { return '$' . number_format((float) $v, 2, ',', '.'); }

$desde  = $_GET['desde'] ?? '';
$hasta  = $_GET['hasta'] ?? '';
$metodo = (int) ($_GET['metodo'] ?? 0);
$estado = $_GET['estado'] ?? '';

if ($desde !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = '';
if ($hasta !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = '';

$estados = ['Aprobado', 'Pendiente', 'A reembolsar', 'Cancelado'];
if ($estado !== '' && !in_array($estado, $estados, true)) $estado = '';

$where  = [];
$tipos  = '';
$params = [];

if ($desde !== '')  { $where[] = "DATE(pg.fechas_pagos) >= ?"; $tipos .= 's'; $params[] = $desde; }
if ($hasta !== '')  { $where[] = "DATE(pg.fechas_pagos) <= ?"; $tipos .= 's'; $params[] = $hasta; }
if ($metodo > 0)    { $where[] = "pg.rela_id_metodo_pago = ?";  $tipos .= 'i'; $params[] = $metodo; }
if ($estado !== '') { $where[] = "pg.estado_pagos = ?";         $tipos .= 's'; $params[] = $estado; }

$cond = $where ? ("WHERE " . implode(" AND ", $where)) : "";

$sql = "SELECT pg.id_pagos, pg.fechas_pagos, pg.monto, pg.estado_pagos, pg.referencia_transaccion,
               pg.rela_id_pedidos, mp.nombre AS metodo, c.nombre, c.apellido
        FROM pagos pg
        INNER JOIN pedidos p ON p.id_pedidos = pg.rela_id_pedidos
        INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
        LEFT JOIN metodos_pago mp ON mp.id_metodo_pago = pg.rela_id_metodo_pago
        $cond
        ORDER BY pg.fechas_pagos DESC, pg.id_pagos DESC";

$st = mysqli_prepare($conexion, $sql);
if ($params) {
    mysqli_stmt_bind_param($st, $tipos, ...$params);
}
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);

$filas = [];
$tot = ['Aprobado' => 0.0, 'Pendiente' => 0.0, 'A reembolsar' => 0.0, 'Cancelado' => 0.0];
$cant = ['Aprobado' => 0, 'Pendiente' => 0, 'A reembolsar' => 0, 'Cancelado' => 0];
$porMetodo = [];

while ($f = mysqli_fetch_assoc($res)) {
    $filas[] = $f;
    $s = $f['estado_pagos'];
    if (isset($tot[$s])) {
        $tot[$s] += (float) $f['monto'];
        $cant[$s]++;
    }
    if ($s === 'Aprobado') {
        $m = $f['metodo'] ?: 'Sin método';
        $porMetodo[$m] = ($porMetodo[$m] ?? 0) + (float) $f['monto'];
    }
}
arsort($porMetodo);

$metodos = [];
$rm = mysqli_query($conexion, "SELECT id_metodo_pago, nombre FROM metodos_pago ORDER BY nombre");
while ($m = mysqli_fetch_assoc($rm)) $metodos[] = $m;

$colores = ['Aprobado' => '#3DDC97', 'Pendiente' => '#FFC857', 'A reembolsar' => '#FF9F5A', 'Cancelado' => '#FF5C7A'];

$ruta_raiz = '../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Transacciones - CyberCore</title>
<link href="../css/theme.css" rel="stylesheet">
<?php require __DIR__ . '/_estilo_consultas.php'; ?>
<style>
    .resumen { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px; margin-bottom: 20px; }
    .tarjeta { background: var(--cc-superficie-2); border: 1px solid var(--cc-borde); border-radius: 12px; padding: 14px 16px; }
    .tarjeta small { color: var(--cc-texto-secundario); display: block; margin-bottom: 4px; }
    .tarjeta strong { font-size: 22px; color: var(--cc-texto); }
    .tarjeta span { font-size: 12px; color: var(--cc-texto-secundario); display: block; margin-top: 2px; }
    .por-metodo { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
    .por-metodo div { background: var(--cc-superficie-2); border: 1px solid var(--cc-borde); border-radius: 20px; padding: 6px 14px; font-size: 14px; }
    h3 { color: var(--cc-primario); margin: 20px 0 8px; font-size: 16px; }
</style>
</head>
<body>
<?php require __DIR__ . '/../includes/header_admin.php'; ?>

<div class="container">
    <h1>💳 Transacciones</h1>
    <div class="subtitulo">Todos los pagos del sitio, con sus totales por estado y por método de pago</div>

    <form method="GET" class="filtros">
        <div><label>Desde</label><input type="date" name="desde" value="<?= e($desde) ?>"></div>
        <div><label>Hasta</label><input type="date" name="hasta" value="<?= e($hasta) ?>"></div>
        <div><label>Método de pago</label>
            <select name="metodo">
                <option value="0">Todos</option>
                <?php foreach ($metodos as $m): ?>
                    <option value="<?= (int) $m['id_metodo_pago'] ?>" <?= $metodo === (int) $m['id_metodo_pago'] ? 'selected' : '' ?>><?= e($m['nombre']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div><label>Estado</label>
            <select name="estado">
                <option value="">Todos</option>
                <?php foreach ($estados as $s): ?>
                    <option value="<?= e($s) ?>" <?= $estado === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                <?php endforeach; ?>
            </select></div>
        <button class="btn-cc" type="submit">🔎 Filtrar</button>
        <?php if ($desde !== '' || $hasta !== '' || $metodo > 0 || $estado !== ''): ?>
            <a class="btn-cc btn-sec" href="transacciones.php">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="resumen">
        <div class="tarjeta"><small>Cobrado (aprobado)</small><strong><?= plata($tot['Aprobado']) ?></strong><span><?= $cant['Aprobado'] ?> pago(s)</span></div>
        <div class="tarjeta"><small>Pendiente de cobro</small><strong><?= plata($tot['Pendiente']) ?></strong><span><?= $cant['Pendiente'] ?> pago(s)</span></div>
        <div class="tarjeta"><small>A reembolsar</small><strong><?= plata($tot['A reembolsar']) ?></strong><span><?= $cant['A reembolsar'] ?> pago(s)</span></div>
        <div class="tarjeta"><small>Cancelado</small><strong><?= plata($tot['Cancelado']) ?></strong><span><?= $cant['Cancelado'] ?> pago(s)</span></div>
    </div>

    <?php if ($porMetodo): ?>
        <h3>Cobrado por método de pago</h3>
        <div class="por-metodo">
            <?php foreach ($porMetodo as $nombre => $monto): ?>
                <div><?= e($nombre) ?>: <strong><?= plata($monto) ?></strong></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!$filas): ?>
        <div class="empty">No hay transacciones para mostrar.</div>
    <?php else: ?>
    <table>
        <tr><th>Fecha</th><th>Pedido</th><th>Cliente</th><th>Método</th><th>Referencia</th><th>Estado</th><th class="num">Monto</th></tr>
        <?php foreach ($filas as $f): ?>
        <tr>
            <td><?= $f['fechas_pagos'] ? e(date('d/m/Y H:i', strtotime($f['fechas_pagos']))) : '' ?></td>
            <td><a href="../views/pedidos/verPedido.php?id=<?= (int) $f['rela_id_pedidos'] ?>" style="color:var(--cc-primario);">#<?= (int) $f['rela_id_pedidos'] ?></a></td>
            <td><?= e($f['nombre'] . ' ' . $f['apellido']) ?></td>
            <td><?= e($f['metodo'] ?: '-') ?></td>
            <td><?= e($f['referencia_transaccion'] ?: '-') ?></td>
            <td><span class="badge" style="background:<?= e($colores[$f['estado_pagos']] ?? '#8FA3BF') ?>;"><?= e($f['estado_pagos']) ?></span></td>
            <td class="num"><strong><?= plata($f['monto']) ?></strong></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <a href="menu_consultas.php" class="volver" style="margin-top:24px;">⬅ Volver a Consultas</a>
</div>
<script src="../js/theme-toggle.js"></script>
</body>
</html>