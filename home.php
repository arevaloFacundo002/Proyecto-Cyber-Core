<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'models/Producto.php';
require_once 'models/Favorito.php';
require_once 'models/FiltroProducto.php';
require_once 'includes/imagen_helper.php';   // Incluye la clase

$pro = new Producto();                // Instancia de la clase

$rol = $_SESSION['rol'] ?? null;

// Búsqueda (opcional, vía ?buscar=...)
$buscar = trim($_GET['buscar'] ?? '');

// Si escribió el código exacto de un producto, lo llevamos directo a su página
if ($buscar !== '') {
    $id_directo = $pro->id_por_codigo($buscar);
    if ($id_directo !== null) {
        header("Location: producto.php?id=" . $id_directo);
        exit;
    }
}

// Traer productos para la grilla
// Filtros (todos opcionales, vía GET)
$filtros_modelo = new FiltroProducto();
$f_categoria = (int) ($_GET['categoria'] ?? 0);
$f_marca     = (int) ($_GET['marca'] ?? 0);
$f_min       = (isset($_GET['precio_min']) && is_numeric($_GET['precio_min']) && $_GET['precio_min'] >= 0) ? (float) $_GET['precio_min'] : null;
$f_max       = (isset($_GET['precio_max']) && is_numeric($_GET['precio_max']) && $_GET['precio_max'] >= 0) ? (float) $_GET['precio_max'] : null;
$f_stock     = !empty($_GET['solo_stock']) ? 1 : 0;
$f_orden     = $_GET['orden'] ?? 'nuevos';
if (!isset(FiltroProducto::ORDENES[$f_orden])) {
    $f_orden = 'nuevos';
}

$productos = $filtros_modelo->listar($buscar, [
    'categoria'  => $f_categoria,
    'marca'      => $f_marca,
    'precio_min' => $f_min,
    'precio_max' => $f_max,
    'solo_stock' => $f_stock,
    'orden'      => $f_orden,
]);
$lista_categorias = $filtros_modelo->categorias();
$lista_marcas     = $filtros_modelo->marcas();
$hay_filtros = $buscar !== '' || $f_categoria || $f_marca || $f_min !== null || $f_max !== null || $f_stock || $f_orden !== 'nuevos';

// Favoritos del cliente (para pintar los corazones)
$es_cliente = in_array($rol, ['cliente', 'usuario'], true);
$mis_favoritos = [];
if ($es_cliente && isset($_SESSION['id_usuario'])) {
    $mis_favoritos = (new Favorito())->ids((int) $_SESSION['id_usuario']);
    if (empty($_SESSION['csrf_fav'])) {
        $_SESSION['csrf_fav'] = bin2hex(random_bytes(32));
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Cache-Control" content="no-store" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />

<title>CyberCore - Inicio</title>

<link href="css/theme.css" rel="stylesheet">
<link href="css/favoritos.css" rel="stylesheet">

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
    margin-left: 10px;
    text-decoration: none;
    font-weight: bold;
}
.logout:hover {
    text-decoration: underline;
}

/* HERO */
.hero {
    width: 100%;
    height: 420px;
    background: url('img/banner.jpg') center/cover no-repeat;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.hero-overlay {
    position: absolute;
    width: 100%;
    height: 100%;
    background: rgba(0, 5, 33, 0.6);
}

.hero-text {
    position: relative;
    text-align: center;
    color: white;
    z-index: 2;
}

.hero-text h1 {
    font-size: 48px;
    font-weight: bold;
    margin-bottom: 15px;
    text-shadow: 0 0 10px var(--cc-primario);
}

.hero-text p {
    font-size: 20px;
    opacity: 0.9;
    margin-bottom: 20px;
}

.hero-text .btn {
    padding: 12px 25px;
    background: var(--cc-primario);
    color: var(--cc-texto-sobre-primario);
    font-weight: bold;
    border-radius: 25px;
    text-decoration: none;
    transition: 0.3s;
}

.hero-text .btn:hover {
    background: var(--cc-primario-hover);
}

/* SEARCH BAR */
.search-container {
    padding: 40px;
    text-align: center;
    background: var(--cc-superficie-2);
}

.search-container input {
    width: 45%;
    padding: 15px;
    border-radius: 30px;
    border: 1px solid var(--cc-borde);
    outline: none;
    font-size: 16px;
    background: var(--cc-superficie);
    color: var(--cc-texto);
}

.search-container button {
    padding: 15px 22px;
    background: var(--cc-primario);
    color: var(--cc-texto-sobre-primario);
    border: none;
    border-radius: 30px;
    font-size: 16px;
    margin-left: 10px;
    cursor: pointer;
    font-weight: bold;
}

.search-container button:hover {
    background: var(--cc-primario-hover);
}

/* FILTROS */
.filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
    align-items: flex-end;
    margin-top: 22px;
}
.filtros label {
    display: block;
    font-size: 13px;
    color: var(--cc-texto-secundario);
    text-align: left;
    margin-bottom: 4px;
}
.filtros select, .filtros input[type=number] {
    width: auto;
    padding: 10px 14px;
    border-radius: 20px;
    border: 1px solid var(--cc-borde);
    background: var(--cc-superficie);
    color: var(--cc-texto);
    font-size: 15px;
    margin: 0;
}
.filtros input[type=number] { width: 120px; }
.filtros .check { display: flex; align-items: center; gap: 6px; color: var(--cc-texto); padding-bottom: 10px; }
.filtros .check input { width: auto; margin: 0; }
.filtros .limpiar { color: var(--cc-primario); padding-bottom: 10px; text-decoration: none; font-weight: bold; }
.resultado-info { text-align: center; color: var(--cc-texto-secundario); margin-top: 18px; }

/* PRODUCT GRID */
.products-title {
    text-align: center;
    margin-top: 40px;
    font-size: 28px;
    color: var(--cc-texto);
    font-weight: bold;
}

.grid {
    padding: 40px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 25px;
}

.card {
    background: var(--cc-superficie);
    border: 1px solid var(--cc-borde);
    border-radius: 14px;
    padding: 18px;
    text-align: center;
    color: var(--cc-texto);
    box-shadow: 0 0 10px rgba(0,0,0,0.35);
    transition: 0.3s;
}

.card:hover {
    transform: translateY(-5px);
    border-color: var(--cc-primario);
    box-shadow: 0 0 15px rgba(0,234,255,0.35);
}

.card img {
    width: 100%;
    height: 170px;
    object-fit: contain;
    background: var(--cc-superficie-2);
    border-radius: 8px;
}

.card h3 {
    margin: 12px 0 4px 0;
    color: var(--cc-texto);
}

.card small {
    color: var(--cc-texto-secundario);
}

.price {
    font-size: 22px;
    color: var(--cc-primario);
    margin-top: 10px;
    font-weight: bold;
}

.btn-ver {
    margin-top: 12px;
    display: inline-block;
    padding: 10px 18px;
    background: var(--cc-primario);
    border-radius: 20px;
    text-decoration: none;
    color: var(--cc-texto-sobre-primario);
    font-weight: bold;
    transition: 0.3s;
}

.btn-ver:hover {
    background: var(--cc-primario-hover);
}
</style>

</head>
<body>

<header>
    <div class="logo">CYBERCORE</div>
    <nav>
        <a href="home.php">Inicio</a>
        <?php $ruta_raiz = ''; include __DIR__ . '/includes/campana.php'; ?>
        <?php if ($rol == 'administrador' || $rol == 'empleado') { ?>
            <a href="inicio.php">Panel</a>

        <?php }elseif($rol == 'usuario' || $rol == 'cliente'){ ?>
            <a href="carrito/carrito.php">🛒 Carrito</a>
            <a href="carrito/mis_pedidos.php">📦 Mis pedidos</a>
            <a href="carrito/mis_favoritos.php">❤ Favoritos</a>
            <a class="logout" href="logout.php">Cerrar sesión</a>
            
        <?php }else{ ?>
            <a href="index.php">Iniciar sesión</a>
            <a href="registro.php">Registrarse</a>
        <?php } ?>
    </nav>
</header>

<!-- HERO con imagen y frase -->
<div class="hero">
    <div class="hero-overlay"></div>
    <div class="hero-text">
        <h1>Potenciá tu mundo gamer</h1>
        <p>Los mejores productos tecnológicos al alcance de tu mano</p>
        <a href="#productos" class="btn">Ver productos</a>
    </div>
</div>


<!-- BUSCADOR -->
<div class="search-container">
    <form method="GET">
        <input 
            type="text" 
            name="buscar" 
            placeholder="Buscar por nombre, código, marca o categoría..."
            value="<?php echo htmlspecialchars($buscar); ?>">
        <button type="submit">Buscar</button>

        <div class="filtros">
            <div>
                <label for="f_categoria">Categoría</label>
                <select name="categoria" id="f_categoria">
                    <option value="0">Todas</option>
                    <?php foreach ($lista_categorias as $c): ?>
                        <option value="<?php echo (int) $c['id_categoria']; ?>" <?php echo $f_categoria === (int) $c['id_categoria'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="f_marca">Marca</label>
                <select name="marca" id="f_marca">
                    <option value="0">Todas</option>
                    <?php foreach ($lista_marcas as $m): ?>
                        <option value="<?php echo (int) $m['id_marca']; ?>" <?php echo $f_marca === (int) $m['id_marca'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($m['nombre_marca']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="f_min">Precio desde</label>
                <input type="number" name="precio_min" id="f_min" min="0" step="1" placeholder="$ mín." value="<?php echo $f_min !== null ? htmlspecialchars((string) $f_min) : ''; ?>">
            </div>
            <div>
                <label for="f_max">Precio hasta</label>
                <input type="number" name="precio_max" id="f_max" min="0" step="1" placeholder="$ máx." value="<?php echo $f_max !== null ? htmlspecialchars((string) $f_max) : ''; ?>">
            </div>
            <div>
                <label for="f_orden">Ordenar por</label>
                <select name="orden" id="f_orden">
                    <option value="nuevos" <?php echo $f_orden === 'nuevos' ? 'selected' : ''; ?>>Más nuevos</option>
                    <option value="menor" <?php echo $f_orden === 'menor' ? 'selected' : ''; ?>>Precio: menor a mayor</option>
                    <option value="mayor" <?php echo $f_orden === 'mayor' ? 'selected' : ''; ?>>Precio: mayor a menor</option>
                    <option value="nombre" <?php echo $f_orden === 'nombre' ? 'selected' : ''; ?>>Nombre A-Z</option>
                </select>
            </div>
            <label class="check"><input type="checkbox" name="solo_stock" value="1" <?php echo $f_stock ? 'checked' : ''; ?>> Solo con stock</label>
            <?php if ($hay_filtros): ?>
                <a class="limpiar" href="home.php#productos">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>
</div>


<!-- TÍTULO LISTA -->
<h2 class="products-title" id="productos">Productos destacados</h2>
<?php if ($hay_filtros): ?>
    <p class="resultado-info"><?php echo count($productos); ?> producto(s) encontrado(s)</p>
<?php endif; ?>


<!-- PRODUCTOS -->
<div class="grid">

<?php if (empty($productos)): ?>

    <p style="grid-column: 1 / -1; text-align:center; color: var(--cc-texto-secundario);">
        No se encontraron productos con esos filtros.
    </p>

<?php else: ?>

<?php foreach($productos as $p) { ?>
    <div class="card">

        <?php if ($es_cliente): ?>
            <?php $es_fav = in_array((int) $p['id_producto'], $mis_favoritos, true); ?>
            <button type="button" class="btn-fav <?php echo $es_fav ? 'activo' : ''; ?>" aria-pressed="<?php echo $es_fav ? 'true' : 'false'; ?>"
                    title="<?php echo $es_fav ? 'Quitar de favoritos' : 'Agregar a favoritos'; ?>"
                    data-id="<?php echo (int) $p['id_producto']; ?>" data-csrf="<?php echo htmlspecialchars($_SESSION['csrf_fav']); ?>"
                    data-url="ajax/favorito_toggle.php">❤</button>
        <?php elseif ($rol === null): ?>
            <button type="button" class="btn-fav" title="Iniciá sesión para guardar favoritos"
                    data-id="<?php echo (int) $p['id_producto']; ?>" data-login="0" data-login-url="login.php">❤</button>
        <?php endif; ?>

        <img src="<?php echo htmlspecialchars(urlImagen($p['imagen_url'])); ?>" alt="">

        <h3><?php echo $p['nombre']; ?></h3>
        <small><?php echo $p['nombre_marca']; ?> • <?php echo $p['categoria']; ?></small>

        <div class="price">$<?php echo number_format($p['precio'], 2); ?></div>


        <!-- STOCK -->
<?php if ($p['stock'] == 0): ?>

    <p style="color:var(--cc-peligro); font-weight:bold; margin-top:8px;">
        ❌ SIN STOCK
    </p>
    <a class="btn-ver" style="background:var(--cc-texto-secundario); pointer-events:none; cursor:not-allowed;">
        No disponible
    </a>

<?php elseif ($p['stock'] <= 2): ?>

    <p style="color:var(--cc-peligro); font-weight:bold; margin-top:8px;">
        🔥 Últimas unidades disponibles
    </p>
    <a class="btn-ver" href="producto.php?id=<?php echo $p['id_producto']; ?>">
        Ver más
    </a>

<?php elseif ($p['stock'] <= 4): ?>

    <p style="color:var(--cc-advertencia); font-weight:bold; margin-top:8px;">
        ⚠️ Pocas unidades en stock
    </p>
    <a class="btn-ver" href="producto.php?id=<?php echo $p['id_producto']; ?>">
        Ver más
    </a>

<?php else: ?>

    <!-- Stock suficiente → no mostrar cantidad -->
    <a class="btn-ver" href="producto.php?id=<?php echo $p['id_producto']; ?>">
        Ver más
    </a>

<?php endif; ?>


    </div>
<?php } ?>

<?php endif; ?>

</div>

<script src="js/favoritos.js"></script>
<script src="js/theme-toggle.js"></script>

</body>
</html>