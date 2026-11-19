<?php
session_start();
require_once '../models/Favorito.php';
require_once '../includes/imagen_helper.php';

if (!isset($_SESSION['usuario'], $_SESSION['id_usuario'])) {
    header("Location: ../login.php?msg=Debes iniciar sesión");
    exit;
}

$rol = $_SESSION['rol'] ?? '';
if (!in_array($rol, ['cliente', 'usuario'], true)) {
    header("Location: ../home.php");
    exit;
}

if (empty($_SESSION['csrf_fav'])) {
    $_SESSION['csrf_fav'] = bin2hex(random_bytes(32));
}

$fav       = new Favorito();
$productos = $fav->listar((int) $_SESSION['id_usuario']);

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mis favoritos - CyberCore</title>
<link href="../css/theme.css" rel="stylesheet">
<link href="../css/tienda.css" rel="stylesheet">
<link href="../css/favoritos.css" rel="stylesheet">
</head>
<body>

<header class="tienda-header">
    <div class="logo">CYBERCORE</div>
    <nav>
        <a href="../home.php">Inicio</a>
        <?php $ruta_raiz = '../'; include __DIR__ . '/../includes/campana.php'; ?>
        <a href="carrito.php">🛒 Carrito</a>
        <a href="mis_pedidos.php">📦 Mis pedidos</a>
        <a href="mis_favoritos.php">❤ Favoritos</a>
        <a class="logout" href="../logout.php">Cerrar sesión</a>
    </nav>
</header>

<div class="tienda-container">

    <h1>❤ Mis favoritos</h1>

    <?php if (empty($productos)): ?>
        <div class="fav-vacio">
            Todavía no guardaste ningún producto.<br><br>
            <a class="btn-principal" href="../home.php">Ir a la tienda</a>
        </div>
    <?php else: ?>
        <div class="fav-grid">
            <?php foreach ($productos as $p): ?>
                <div class="fav-card">
                    <button type="button" class="btn-fav activo" aria-pressed="true" title="Quitar de favoritos"
                            data-id="<?= (int) $p['id_producto'] ?>" data-csrf="<?= e($_SESSION['csrf_fav']) ?>"
                            data-url="../ajax/favorito_toggle.php" data-quitar="1">❤</button>
                    <img src="<?= e(urlImagen($p['imagen_url'], '../')) ?>" alt="">
                    <h3><?= e($p['nombre']) ?></h3>
                    <small><?= e($p['nombre_marca']) ?> • <?= e($p['categoria']) ?></small>
                    <div class="fav-precio">$<?= number_format((float) $p['precio'], 2) ?></div>
                    <?php if ((int) $p['stock'] === 0): ?>
                        <div style="color:var(--cc-peligro);font-weight:bold;">❌ SIN STOCK</div>
                    <?php endif; ?>
                    <div class="fav-acciones">
                        <a class="btn-secundario" href="../producto.php?id=<?= (int) $p['id_producto'] ?>">Ver más</a>
                        <?php if ((int) $p['stock'] > 0): ?>
                            <a class="btn-principal" href="agregar_carrito.php?id=<?= (int) $p['id_producto'] ?>">🛒 Agregar</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script src="../js/favoritos.js"></script>
<script src="../js/theme-toggle.js"></script>
</body>
</html>