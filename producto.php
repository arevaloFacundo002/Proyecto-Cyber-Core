 <?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'models/Producto.php';
$pro = new Producto();

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Producto no encontrado.");
}

$rol = $_SESSION['rol'] ?? null;
$id = intval($_GET['id']);

// Consulta del producto
$producto = $pro->busqueda_de_producto($id);
if (!$producto) {
    die("Producto no encontrado.");
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($producto['nombre']); ?> - CyberCore</title>

<link href="css/theme.css" rel="stylesheet">

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
}

/* HEADER */
header {
    background: var(--cc-superficie-2);
    padding: 18px 50px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: var(--cc-texto);
    position: sticky;
    top: 0;
    z-index: 100;
    border-bottom: 1px solid var(--cc-borde);
}

.logo {
    font-size: 28px;
    font-weight: bold;
    letter-spacing: 1px;
    color: var(--cc-primario);
}

nav a {
    color: var(--cc-texto);
    margin: 0 15px;
    text-decoration: none;
    font-weight: 500;
    transition: 0.3s;
}

nav a:hover {
    color: var(--cc-primario);
}

.logout {
    color: var(--cc-peligro);
}

/* PRODUCT PAGE */
.container {
    display: flex;
    padding: 50px;
    gap: 40px;
    max-width: 1200px;
    margin: auto;
    flex-wrap: wrap;
}

.product-img {
    width: 45%;
    min-width: 280px;
    background: var(--cc-superficie);
    padding: 25px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 0 15px rgba(0, 234, 255, 0.15);
    border: 1px solid var(--cc-borde);
}

.product-img img {
    width: 100%;
    height: 350px;
    object-fit: contain;
}

.details {
    flex: 1;
    min-width: 280px;
    color: var(--cc-texto);
}

.details h1 {
    font-size: 36px;
    margin-bottom: 10px;
    color: var(--cc-texto);
}

.category {
    color: var(--cc-primario);
    margin-bottom: 20px;
    font-size: 18px;
}

.price {
    font-size: 32px;
    font-weight: bold;
    color: var(--cc-primario);
    margin-top: 10px;
}

.description {
    margin-top: 20px;
    line-height: 1.5;
    font-size: 17px;
    color: var(--cc-texto-secundario);
}

.btn-add {
    display: inline-block;
    margin-top: 25px;
    padding: 14px 30px;
    background: var(--cc-primario);
    color: var(--cc-texto-sobre-primario);
    font-weight: bold;
    border-radius: 25px;
    text-decoration: none;
    font-size: 18px;
    transition: 0.3s;
}

.btn-add:hover {
    background: var(--cc-primario-hover);
}

.disabled-btn {
    background: var(--cc-texto-secundario) !important;
    pointer-events: none;
    cursor: not-allowed;
}

.stock-aviso {
    font-size: 18px;
    font-weight: bold;
}

.stock-sin      { color: var(--cc-peligro); }
.stock-ultimas  { color: var(--cc-peligro); }
.stock-pocas    { color: var(--cc-advertencia); }

/* RESEÑAS */
.reseñas-box {
    max-width: 1200px;
    margin: 50px auto;
    background: var(--cc-superficie);
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(0, 234, 255, 0.12);
    border: 1px solid var(--cc-borde);
    color: var(--cc-texto);
}

.reseñas-box h2 {
    color: var(--cc-primario);
    margin-bottom: 20px;
}

.reseña-item {
    background: var(--cc-superficie-2);
    padding: 18px;
    border-radius: 8px;
    margin-bottom: 15px;
    border-left: 4px solid var(--cc-primario);
}

.reseña-item strong {
    color: var(--cc-primario);
}

.reseñas-box hr {
    border-color: var(--cc-borde);
    margin: 25px 0;
}

.reseñas-box textarea,
.reseñas-box select {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid var(--cc-borde);
    background: var(--cc-fondo);
    color: var(--cc-texto);
}

.reseñas-box textarea {
    height: 120px;
}

.reseñas-box select {
    width: auto;
    margin-bottom: 12px;
}

.reseñas-box label {
    color: var(--cc-texto);
    margin-top: 10px;
    display: block;
}

.reseñas-box button {
    margin-top: 12px;
    padding: 12px 25px;
    background: var(--cc-primario);
    color: var(--cc-texto-sobre-primario);
    border: none;
    border-radius: 20px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.2s;
}

.reseñas-box button:hover {
    background: var(--cc-primario-hover);
}

.texto-secundario {
    color: var(--cc-texto-secundario);
}
</style>
</head>

<body>

<header>
    <div class="logo">CYBERCORE</div>
    <nav>
        <a href="home.php">Inicio</a>
        <?php if ($rol == 'administrador' || $rol == 'empleado') { ?>
            <a href="inicio.php">Panel</a>

        <?php } elseif ($rol == 'usuario' || $rol == 'cliente') { ?>
            <a href="carrito/carrito.php">🛒 Carrito</a>
            <a class="logout" href="logout.php">Cerrar sesión</a>

        <?php } else { ?>
            <a href="index.php">Iniciar sesión</a>
            <a href="registro.php">Registrarse</a>
        <?php } ?>
    </nav>
</header>

<div class="container">

    <!-- IMAGEN -->
    <div class="product-img">
        <img src="img/<?php echo htmlspecialchars($producto['imagen_url']); ?>" alt="">
    </div>

    <!-- DETALLES -->
    <div class="details">
        <h1><?php echo htmlspecialchars($producto['nombre']); ?></h1>

        <div class="category">
            <?php echo htmlspecialchars($producto['nombre_marca']); ?> • <?php echo htmlspecialchars($producto['categoria']); ?>
        </div>

        <div class="price">$<?php echo number_format($producto['precio'], 2); ?></div>

        <!-- STOCK -->
        <?php if ($producto['stock'] == 0): ?>

            <p class="stock-aviso stock-sin">❌ SIN STOCK</p>

        <?php elseif ($producto['stock'] <= 2): ?>

            <p class="stock-aviso stock-ultimas">🔥 Últimas unidades disponibles</p>

        <?php elseif ($producto['stock'] <= 4): ?>

            <p class="stock-aviso stock-pocas">⚠️ Pocas unidades en stock</p>

        <?php endif; ?>

        <p class="description"><?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?></p>

        <!-- AGREGAR AL CARRITO -->
        <?php if ($producto['stock'] == 0): ?>
            <a class="btn-add disabled-btn">No disponible 🛒</a>
        <?php else: ?>
            <a href="carrito/agregar_carrito.php?id=<?php echo $producto['id_producto']; ?>" class="btn-add">
                Agregar al carrito 🛒
            </a>
        <?php endif; ?>

    </div>
</div>

<!-- RESEÑAS -->
<div class="reseñas-box">

    <h2>⭐ Reseñas del producto</h2>

    <?php
    $reseñas = $pro->consultar_reseñas($id);
    ?>

    <!-- LISTADO DE RESEÑAS -->
    <?php if (!empty($reseñas)): ?>
        <?php foreach ($reseñas as $r): ?>
            <div class="reseña-item">
                <strong>⭐ <?php echo htmlspecialchars($r['calificacion']); ?>/5</strong>
                <p><?php echo htmlspecialchars($r['comentario']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="texto-secundario">No hay reseñas todavía. ¡Sé el primero en opinar!</p>
    <?php endif; ?>

    <hr>

    <!-- FORMULARIO PARA AGREGAR RESEÑA -->
    <h3>Dejar una reseña</h3>

    <?php if (isset($_SESSION['usuario'])): ?>

        <form action="guardar_resena.php" method="POST">
            <input type="hidden" name="id_producto" value="<?php echo $id; ?>">

            <textarea name="comentario" placeholder="Escribe tu reseña..." required></textarea>

            <label>Calificación:</label>

            <select name="calificacion" required>
                <option value="5">⭐⭐⭐⭐⭐</option>
                <option value="4">⭐⭐⭐⭐</option>
                <option value="3">⭐⭐⭐</option>
                <option value="2">⭐⭐</option>
                <option value="1">⭐</option>
            </select>

            <button type="submit">Publicar reseña ⭐</button>
        </form>

    <?php else: ?>
        <p class="texto-secundario">Debes iniciar sesión para dejar una reseña.</p>
    <?php endif; ?>

</div>

<script src="js/theme-toggle.js"></script>

</body>
</html>