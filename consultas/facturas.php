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

if (empty($_SESSION['csrf_facturas'])) {
    $_SESSION['csrf_facturas'] = bin2hex(random_bytes(16));
}
$flash = $_SESSION['factura_flash'] ?? null;
unset($_SESSION['factura_flash']);

$tab    = ($_GET['tab'] ?? 'emitidas') === 'pendientes' ? 'pendientes' : 'emitidas';
$buscar = trim($_GET['buscar'] ?? '');

// Contadores
$nEmit = (int) mysqli_fetch_row(mysqli_query($conexion, "SELECT COUNT(*) FROM facturas"))[0];
$nPend = (int) mysqli_fetch_row(mysqli_query($conexion,
    "SELECT COUNT(*) FROM pedidos p WHERE p.estado <> 'Cancelado'
     AND NOT EXISTS (SELECT 1 FROM facturas f WHERE f.rela_id_pedidos = p.id_pedidos)"))[0];

$filas = [];
if ($tab === 'emitidas') {
    $sql = "SELECT f.id_facturas, f.numero_factura, f.fecha_emicion, f.tipo_factura, f.monto_total,
                   f.monto_total_bruto, f.monto_total_impuesto, f.estado_fiscal, f.rela_id_pedidos,
                   c.nombre, c.apellido
            FROM facturas f
            INNER JOIN pedidos p ON p.id_pedidos = f.rela_id_pedidos
            INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente";
    if ($buscar !== '') $sql .= " WHERE f.numero_factura LIKE ? OR f.rela_id_pedidos = ? OR CONCAT(c.nombre,' ',c.apellido) LIKE ?";
    $sql .= " ORDER BY f.id_facturas DESC";
    $st = mysqli_prepare($conexion, $sql);
    if ($buscar !== '') {
        $like = '%' . $buscar . '%';
        $id = (int) preg_replace('/\D/', '', $buscar);
        mysqli_stmt_bind_param($st, 'sis', $like, $id, $like);
    }
} else {
    $sql = "SELECT p.id_pedidos, p.fecha_pedidos, p.estado, p.monto_total, c.nombre, c.apellido
            FROM pedidos p
            INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
            WHERE p.estado <> 'Cancelado'
              AND NOT EXISTS (SELECT 1 FROM facturas f WHERE f.rela_id_pedidos = p.id_pedidos)";
    if ($buscar !== '') $sql .= " AND (p.id_pedidos = ? OR CONCAT(c.nombre,' ',c.apellido) LIKE ?)";
    $sql .= " ORDER BY p.id_pedidos DESC";
    $st = mysqli_prepare($conexion, $sql);
    if ($buscar !== '') {
        $id = (int) preg_replace('/\D/', '', $buscar);
        $like = '%' . $buscar . '%';
        mysqli_stmt_bind_param($st, 'is', $id, $like);
    }
}
mysqli_stmt_execute($st);
$r = mysqli_stmt_get_result($st);
while ($f = mysqli_fetch_assoc($r)) $filas[] = $f;

$ruta_raiz = '../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Facturas - CyberCore</title>
<link href="../css/theme.css" rel="stylesheet">
<?php require __DIR__ . '/_estilo_consultas.php'; ?>
<style>
    .tabs { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; }
    .tab { padding: 8px 16px; border-radius: 20px; border: 1px solid var(--cc-borde); color: var(--cc-texto);
        text-decoration: none; font-weight: 600; font-size: 14px; background: var(--cc-superficie-2); }
    .tab.activa { background: var(--cc-primario); color: var(--cc-texto-sobre-primario); border-color: var(--cc-primario); }
    .tab:hover { border-color: var(--cc-primario); }
</style>
</head>
<body>
<?php require __DIR__ . '/../includes/header_admin.php'; ?>

<div class="container">
    <h1>🧾 Facturas</h1>
    <div class="subtitulo">Consultá las facturas emitidas y emití las de los pedidos que todavía no tienen</div>

    <?php if ($flash): ?><div class="aviso <?= e($flash['tipo']) ?>"><?= e($flash['texto']) ?></div><?php endif; ?>

    <div class="tabs">
        <a class="tab <?= $tab === 'emitidas' ? 'activa' : '' ?>" href="facturas.php">Emitidas (<?= $nEmit ?>)</a>
        <a class="tab <?= $tab === 'pendientes' ? 'activa' : '' ?>" href="facturas.php?tab=pendientes">Pedidos sin facturar (<?= $nPend ?>)</a>
    </div>

    <form method="GET" class="filtros">
        <input type="hidden" name="tab" value="<?= e($tab) ?>">
        <div style="flex:1;min-width:220px;"><label>Buscar</label>
            <input type="text" name="buscar" style="width:100%;box-sizing:border-box;" placeholder="N° de factura, N° de pedido o cliente" value="<?= e($buscar) ?>"></div>
        <button class="btn-cc" type="submit">🔎 Buscar</button>
        <?php if ($buscar !== ''): ?><a class="btn-cc btn-sec" href="facturas.php?tab=<?= e($tab) ?>">Limpiar</a><?php endif; ?>
    </form>

    <?php if (!$filas): ?>
        <div class="empty"><?= $tab === 'emitidas' ? 'No hay facturas para mostrar.' : '¡Todos los pedidos están facturados!' ?></div>
    <?php elseif ($tab === 'emitidas'): ?>
    <table>
        <tr><th>N° Factura</th><th>Tipo</th><th>Fecha</th><th>Pedido</th><th>Cliente</th>
            <th class="num">Neto</th><th class="num">IVA</th><th class="num">Total</th><th></th></tr>
        <?php foreach ($filas as $f): ?>
        <tr>
            <td><strong><?= e($f['numero_factura']) ?></strong></td>
            <td><?= e($f['tipo_factura']) ?></td>
            <td><?= $f['fecha_emicion'] ? e(date('d/m/Y', strtotime($f['fecha_emicion']))) : '' ?></td>
            <td>#<?= (int) $f['rela_id_pedidos'] ?></td>
            <td><?= e($f['nombre'] . ' ' . $f['apellido']) ?></td>
            <td class="num"><?= plata($f['monto_total_bruto']) ?></td>
            <td class="num"><?= plata($f['monto_total_impuesto']) ?></td>
            <td class="num"><strong><?= plata($f['monto_total']) ?></strong></td>
            <td><a class="btn-cc" style="padding:6px 12px;font-size:13px;" href="factura_ver.php?id=<?= (int) $f['id_facturas'] ?>">Ver</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
    <table>
        <tr><th>Pedido</th><th>Fecha</th><th>Cliente</th><th>Estado</th><th class="num">Total</th><th></th></tr>
        <?php foreach ($filas as $f): ?>
        <tr>
            <td>#<?= (int) $f['id_pedidos'] ?></td>
            <td><?= e(date('d/m/Y', strtotime($f['fecha_pedidos']))) ?></td>
            <td><?= e($f['nombre'] . ' ' . $f['apellido']) ?></td>
            <td><?= e($f['estado']) ?></td>
            <td class="num"><strong><?= plata($f['monto_total']) ?></strong></td>
            <td>
                <form method="POST" action="factura_emitir.php" style="margin:0;">
                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_facturas']) ?>">
                    <input type="hidden" name="id_pedido" value="<?= (int) $f['id_pedidos'] ?>">
                    <button class="btn-cc" style="padding:6px 12px;font-size:13px;" type="submit">Emitir factura</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <a href="menu_consultas.php" class="volver" style="margin-top:24px;">⬅ Volver a Consultas</a>
</div>
<script src="../js/theme-toggle.js"></script>
</body>
</html>