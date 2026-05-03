<?php
require_once "conexion.php";
$dao = new UserDao();

//Si no esta logeado lo sacamos
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $buscar = $_GET['buscar']?? "";
    $rol = $_SESSION['rol'];

    //busqueda de productos
    $productos = $dao->listar_productos($buscar);
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

<style>
    body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: #f5f5f5;
}

/* HEADER */
header {
    background: #0a0a0a;
    padding: 18px 50px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: white;
    position: sticky;
    top: 0;
    z-index: 100;
}

.logo {
    font-size: 28px;
    font-weight: bold;
    letter-spacing: 1px;
    color: #00eaff;
}

nav a {
    color: white;
    margin: 0 15px;
    text-decoration: none;
    font-weight: 500;
    transition: 0.3s;
}

nav a:hover {
    color: #00eaff;
}

.logout {
    color: #ff4b4b;
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
    background: rgba(10, 10, 10, 0.55);
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
    text-shadow: 0 0 10px #00eaff;
}

.hero-text p {
    font-size: 20px;
    opacity: 0.9;
    margin-bottom: 20px;
}

.hero-text .btn {
    padding: 12px 25px;
    background: #00eaff;
    color: black;
    font-weight: bold;
    border-radius: 25px;
    text-decoration: none;
    transition: 0.3s;
}

.hero-text .btn:hover {
    background: #009ac0;
}

/* SEARCH BAR */
.search-container {
    padding: 40px;
    text-align: center;
    background: linear-gradient(90deg, #0f0f0f, #1a1a1a);
}

.search-container input {
    width: 45%;
    padding: 15px;
    border-radius: 30px;
    border: none;
    outline: none;
    font-size: 16px;
}

.search-container button {
    padding: 15px 22px;
    background: #00eaff;
    border: none;
    border-radius: 30px;
    font-size: 16px;
    margin-left: 10px;
    cursor: pointer;
    font-weight: bold;
}

.search-container button:hover {
    background: #00b1cc;
}

/* PRODUCT GRID */
.products-title {
    text-align: center;
    margin-top: 40px;
    font-size: 28px;
    color: #111;
    font-weight: bold;
}

.grid {
    padding: 40px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 25px;
}

.card {
    background: white;
    border-radius: 14px;
    padding: 18px;
    text-align: center;
    box-shadow: 0 0 10px rgba(0,0,0,0.12);
    transition: 0.3s;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0 15px rgba(0,255,255,0.4);
}

.card img {
    width: 100%;
    height: 170px;
    object-fit: contain;
}

.card h3 {
    margin: 12px 0 4px 0;
}

.card small {
    color: #777;
}

.price {
    font-size: 22px;
    color: #00b1cc;
    margin-top: 10px;
    font-weight: bold;
}

.btn-ver {
    margin-top: 12px;
    display: inline-block;
    padding: 10px 18px;
    background: #00eaff;
    border-radius: 20px;
    text-decoration: none;
    color: black;
    font-weight: bold;
    transition: 0.3s;
}

.btn-ver:hover {
    background: #00b1cc;
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

        <?php }elseif($rol == 'usuario' || $rol == 'cliente'){ ?>
            <a href="carrito/carrito.php">🛒 Carrito</a>
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
            placeholder="Buscar productos, marcas, categorías..."
            value="<?php echo htmlspecialchars($buscar); ?>">
        <button type="submit">Buscar</button>
    </form>
</div>


<!-- TÍTULO LISTA -->
<h2 class="products-title" id="productos">Productos destacados</h2>


<!-- PRODUCTOS -->
<div class="grid">

<?php foreach($productos as $p) { ?>
    <div class="card">

        <img src="img/<?php echo $p['imagen_url']; ?>" alt="">

        <h3><?php echo $p['nombre']; ?></h3>
        <small><?php echo $p['nombre_marca']; ?> • <?php echo $p['categoria']; ?></small>

        <div class="price">$<?php echo number_format($p['precio'], 2); ?></div>


        <!-- STOCK -->
<?php if ($p['stock'] == 0): ?>

    <p style="color:red; font-weight:bold; margin-top:8px;">
        ❌ SIN STOCK
    </p>
    <a class="btn-ver" style="background:#888; pointer-events:none; cursor:not-allowed;">
        No disponible
    </a>

<?php elseif ($p['stock'] <= 2): ?>

    <p style="color:#ff4444; font-weight:bold; margin-top:8px;">
        🔥 Últimas unidades disponibles
    </p>
    <a class="btn-ver" href="producto.php?id=<?php echo $p['id_producto']; ?>">
        Ver más
    </a>

<?php elseif ($p['stock'] <= 4): ?>

    <p style="color:orange; font-weight:bold; margin-top:8px;">
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


</div>

</body>
</html>
