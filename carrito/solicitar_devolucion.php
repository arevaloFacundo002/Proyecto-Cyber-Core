<?php
session_start();
require_once '../models/Devolucion.php';
require_once __DIR__ . '/_helpers_pedidos.php';

if (!isset($_SESSION['usuario'], $_SESSION['id_usuario'])) {
    header("Location: ../login.php?msg=Debes iniciar sesión");
    exit;
}

$id_pedido = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$modelo    = new Devolucion();
$puede     = $id_pedido > 0 ? $modelo->puedeSolicitar($id_pedido, (int) $_SESSION['id_usuario']) : ['ok' => false];

if (!$puede['ok']) {
    $_SESSION['dev_flash'] = ['tipo' => 'error', 'texto' => $puede['error'] ?? 'No se puede pedir la devolución.'];
    header("Location: " . ($id_pedido > 0 ? "mi_pedido.php?id=" . $id_pedido : "mis_pedidos.php"));
    exit;
}

if (empty($_SESSION['csrf_devolucion'])) {
    $_SESSION['csrf_devolucion'] = bin2hex(random_bytes(16));
}

$lineas = $modelo->lineasDevolvibles($id_pedido);
$flash  = $_SESSION['dev_flash'] ?? null;
unset($_SESSION['dev_flash']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedir devolución - CyberCore</title>
<link href="../css/theme.css" rel="stylesheet">
<link href="../css/tienda.css" rel="stylesheet">
<link href="../css/mis_pedidos.css" rel="stylesheet">
</head>
<body>

<header class="tienda-header">
    <div class="logo">CYBERCORE</div>
    <nav>
        <a href="../home.php">Inicio</a>
        <a href="carrito.php">🛒 Carrito</a>
        <a href="mis_pedidos.php">📦 Mis pedidos</a>
        <a class="logout" href="../logout.php">Cerrar sesión</a>
    </nav>
</header>

<div class="tienda-container">

    <p><a class="btn-secundario" href="mi_pedido.php?id=<?= $id_pedido ?>">← Pedido #<?= $id_pedido ?></a></p>

    <h1>Pedir una devolución</h1>

    <?php if ($flash): ?>
        <div class="aviso"><?= e($flash['texto']) ?></div>
    <?php endif; ?>

    <form method="POST" action="procesar_devolucion.php">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_devolucion']) ?>">
        <input type="hidden" name="id_pedido" value="<?= $id_pedido ?>">

        <div class="caja">
            <h2>¿Qué querés devolver?</h2>
            <p class="texto-suave">Elegí cuántas unidades de cada producto querés devolver.</p>
            <table class="tabla-compra">
                <tr><th>Producto</th><th class="num">Precio</th><th class="num">Compraste</th><th class="num">Devolver</th></tr>
                <?php foreach ($lineas as $l): ?>
                <tr>
                    <td><?= e($l['nombre']) ?></td>
                    <td class="num"><?= plata($l['precio_unit']) ?></td>
                    <td class="num"><?= $l['cantidad'] ?><?php if ($l['ya_devuelta'] > 0): ?><br><span class="texto-suave">(<?= $l['ya_devuelta'] ?> ya pedidas)</span><?php endif; ?></td>
                    <td class="num">
                        <?php if ($l['disponible'] > 0): ?>
                            <input type="number" name="cant[<?= (int) $l['id_detalle_pedidos'] ?>]" class="campo-texto"
                                   style="width:80px;text-align:center;" min="0" max="<?= $l['disponible'] ?>" value="0">
                        <?php else: ?>
                            <span class="texto-suave">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="caja">
            <h2>Motivo</h2>
            <select name="tipo_motivo" class="campo-texto" required>
                <option value="">Elegí el motivo…</option>
                <?php foreach (Devolucion::MOTIVOS as $clave => $m): ?>
                    <?php $bloqueado = !$m['falla'] && !$puede['arrep_vigente']; ?>
                    <option value="<?= e($clave) ?>" <?= $bloqueado ? 'disabled' : '' ?>>
                        <?= e($m['texto']) ?><?= $bloqueado ? ' (el plazo de ' . Devolucion::PLAZO_ARREPENTIMIENTO . ' días ya pasó)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <textarea name="motivo" class="campo-texto" rows="3" maxlength="1000" style="margin-top:10px;"
                      placeholder="Comentario (opcional; obligatorio si elegís «Otro motivo»)"></textarea>
        </div>

        <div class="caja">
            <h2>Políticas de devolución</h2>
            <ul style="margin:0 0 0 18px;line-height:1.7;">
                <li><strong>Falla, producto dañado o equivocado:</strong> podés pedirla hasta <?= Devolucion::PLAZO_DIAS ?> días después de la entrega (hasta el <?= e($puede['limite']) ?>). Te reembolsamos el producto y también el envío.</li>
                <li><strong>Arrepentimiento u otro motivo:</strong> hasta <?= Devolucion::PLAZO_ARREPENTIMIENTO ?> días después de la entrega (hasta el <?= e($puede['limite_arrep']) ?>). Te reembolsamos el producto, sin el envío.</li>
                <li>El producto tiene que estar sin uso y con su caja y accesorios.</li>
                <li>Revisamos tu pedido y te avisamos si lo aprobamos o por qué no.</li>
            </ul>
        </div>

        <p><button type="submit" class="btn-principal">Enviar pedido de devolución</button></p>
    </form>
</div>

<script src="../js/theme-toggle.js"></script>
</body>
</html>