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
require_once __DIR__ . '/../models/Pedido.php'; // solo para los colores/estados

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function plata($v) { return '$' . number_format((float) $v, 2, ',', '.'); }

// ---------- Filtros ----------
$desde  = trim($_GET['desde'] ?? '');
$hasta  = trim($_GET['hasta'] ?? '');
$estado = $_GET['estado'] ?? 'todos';
$buscar = trim($_GET['buscar'] ?? '');

$fechaValida = fn($f) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $f);
if (!$fechaValida($desde)) $desde = '';
if (!$fechaValida($hasta)) $hasta = '';
if ($estado !== 'todos' && !array_key_exists($estado, Pedido::ESTADOS)) $estado = 'todos';

$donde = []; $tipos = ''; $params = [];
if ($desde !== '') { $donde[] = "DATE(pd.fecha_pedidos) >= ?"; $tipos .= 's'; $params[] = $desde; }
if ($hasta !== '') { $donde[] = "DATE(pd.fecha_pedidos) <= ?"; $tipos .= 's'; $params[] = $hasta; }
if ($estado !== 'todos') { $donde[] = "pd.estado = ?"; $tipos .= 's'; $params[] = $estado; }
if ($buscar !== '') {
    $dig = preg_replace('/\D/', '', $buscar);
    $id  = ($dig !== '' && strlen($dig) < 10) ? (int) $dig : 0;
    $like = '%' . $buscar . '%';
    $donde[] = "(pd.id_pedidos = ? OR c.nombre LIKE ? OR c.apellido LIKE ? OR CONCAT(c.nombre,' ',c.apellido) LIKE ?)";
    $tipos .= 'isss';
    array_push($params, $id, $like, $like, $like);
}

// ---------- 1) Un registro por pedido ----------
$sql = "SELECT pd.id_pedidos, pd.fecha_pedidos, pd.estado, pd.monto_total,
               c.nombre, c.apellido,
               e.empresa_transporte, e.estado_envio,
               (SELECT f.id_facturas FROM facturas f WHERE f.rela_id_pedidos = pd.id_pedidos LIMIT 1) AS id_factura
        FROM pedidos pd
        INNER JOIN clientes c ON c.id_cliente = pd.rela_id_cliente
        LEFT JOIN envios e ON e.rela_id_pedidos = pd.id_pedidos"
     . ($donde ? " WHERE " . implode(' AND ', $donde) : "")
     . " ORDER BY pd.id_pedidos DESC";

$pedidos = [];
$stmt = mysqli_prepare($conexion, $sql);
if ($params) mysqli_stmt_bind_param($stmt, $tipos, ...$params);
mysqli_stmt_execute($stmt);
$r = mysqli_stmt_get_result($stmt);
while ($f = mysqli_fetch_assoc($r)) $pedidos[(int) $f['id_pedidos']] = $f;

// ---------- 2) Productos de todos esos pedidos (una sola consulta) ----------
$items = [];
if ($pedidos) {
    $ids = implode(',', array_map('intval', array_keys($pedidos)));
    $q = mysqli_query($conexion,
        "SELECT dp.rela_id_pedidos, p.nombre AS producto, dp.cantidad, dp.precio_unitario_base, dp.subtotal_final
         FROM detalle_pedidos dp
         INNER JOIN productos p ON p.id_producto = dp.rela_id_productos
         WHERE dp.rela_id_pedidos IN ($ids)
         ORDER BY dp.rela_id_pedidos, p.nombre");
    while ($f = mysqli_fetch_assoc($q)) $items[(int) $f['rela_id_pedidos']][] = $f;
}

$ruta_raiz = '../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consulta 2 - Pedidos detallados</title>
<link href="../css/theme.css" rel="stylesheet">
<?php require __DIR__ . '/_estilo_consultas.php'; ?>
<style>
    .pedido { border: 1px solid var(--cc-borde); border-radius: 12px; margin-bottom: 18px; overflow: hidden; background: var(--cc-superficie); }
    .pedido-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px;
        padding: 14px 18px; background: var(--cc-superficie-2); border-bottom: 1px solid var(--cc-borde); }
    .pedido-head .num { font-size: 18px; font-weight: 700; color: var(--cc-primario); }
    .pedido-head .meta { color: var(--cc-texto-secundario); font-size: 13px; }
    .pedido-body { padding: 4px 18px 14px; }
    .pedido-foot { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px;
        padding: 10px 18px 14px; color: var(--cc-texto-secundario); font-size: 13px; }
    .pedido-foot .total { font-size: 18px; font-weight: 700; color: var(--cc-texto); }
    .acciones a { margin-left: 6px; padding: 6px 12px; font-size: 13px; }
</style>
</head>
<body>

<?php require __DIR__ . '/../includes/header_admin.php'; ?>

<div class="container">
    <h1>Consulta 2 — Pedidos detallados</h1>
    <div class="subtitulo"><?= count($pedidos) ?> pedido(s) · cada bloque agrupa los productos de un pedido</div>

    <form method="GET" class="filtros">
        <div><label>Buscar</label><input type="text" name="buscar" placeholder="N° o cliente" value="<?= e($buscar) ?>"></div>
        <div><label>Desde</label><input type="date" name="desde" value="<?= e($desde) ?>"></div>
        <div><label>Hasta</label><input type="date" name="hasta" value="<?= e($hasta) ?>"></div>
        <div><label>Estado</label>
            <select name="estado">
                <option value="todos">Todos</option>
                <?php foreach (Pedido::ESTADOS as $n => $i): ?>
                    <option value="<?= e($n) ?>" <?= $estado === $n ? 'selected' : '' ?>><?= $i['emoji'] ?> <?= e($n) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-cc">🔎 Filtrar</button>
        <a href="consulta2.php" class="btn-cc btn-sec">Limpiar</a>
    </form>

    <?php if (!$pedidos): ?>
        <div class="empty">No se encontraron pedidos.</div>
    <?php endif; ?>

    <?php foreach ($pedidos as $id => $p):
        $estadoVisual = ['Pagado' => 'Confirmado', 'En proceso' => 'Confirmado'][$p['estado']] ?? $p['estado']; // estados viejos
        $info = Pedido::ESTADOS[$estadoVisual] ?? ['emoji' => '⚪', 'color' => '#999'];
        $lista = $items[$id] ?? [];
        $subtotal = array_sum(array_map(fn($x) => (float) $x['subtotal_final'], $lista));
        $envio = round((float) $p['monto_total'] - $subtotal, 2);
    ?>
    <div class="pedido">
        <div class="pedido-head">
            <div>
                <div class="num">Pedido #<?= $id ?></div>
                <div class="meta"><?= e($p['nombre'] . ' ' . $p['apellido']) ?> · <?= e(date('d/m/Y', strtotime($p['fecha_pedidos']))) ?></div>
            </div>
            <span class="badge" style="background: <?= e($info['color']) ?>;"><?= $info['emoji'] ?> <?= e($p['estado']) ?></span>
        </div>
        <div class="pedido-body">
            <table>
                <tr><th>Producto</th><th class="num">Precio</th><th class="num">Cant.</th><th class="num">Subtotal</th></tr>
                <?php foreach ($lista as $it): ?>
                <tr>
                    <td><?= e($it['producto']) ?></td>
                    <td class="num"><?= plata($it['precio_unitario_base']) ?></td>
                    <td class="num"><?= (int) $it['cantidad'] ?></td>
                    <td class="num"><?= plata($it['subtotal_final']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$lista): ?><tr><td colspan="4" class="empty">Sin productos cargados.</td></tr><?php endif; ?>
            </table>
        </div>
        <div class="pedido-foot">
            <div>
                🚚 <?= e($p['empresa_transporte'] ?: 'Sin transporte') ?> · <?= e($p['estado_envio'] ?: 'N/A') ?>
                <?php if ($envio > 0): ?>· Envío <?= plata($envio) ?><?php endif; ?>
            </div>
            <div class="acciones">
                <span class="total">Total <?= plata($p['monto_total']) ?></span>
                <a class="btn-cc btn-sec" href="../views/pedidos/verPedido.php?id=<?= $id ?>">Ver pedido</a>
                <?php if ($p['id_factura']): ?>
                    <a class="btn-cc" href="factura_ver.php?id=<?= (int) $p['id_factura'] ?>">🧾 Factura</a>
                <?php else: ?>
                    <a class="btn-cc btn-sec" href="facturas.php?tab=pendientes">Sin factura</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <a href="menu_consultas.php" class="volver">⬅ Volver a Consultas</a>
</div>

<script src="../js/theme-toggle.js"></script>
</body>
</html>