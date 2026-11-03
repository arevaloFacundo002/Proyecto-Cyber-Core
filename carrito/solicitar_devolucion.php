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
            <p class="texto-suave">Podés devolver hasta el <?= e($puede['limite']) ?>. Elegí cuántas unidades de cada producto.</p>
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
            <textarea name="motivo" class="campo-texto" rows="4" maxlength="1000" required
                      placeholder="Contanos por qué querés devolverlo (producto defectuoso, no era lo esperado, etc.)"></textarea>
        </div>

        <p><button type="submit" class="btn-principal">Enviar pedido de devolución</button></p>
    </form>
</div>

<script src="../js/theme-toggle.js"></script>
</body>
</html>