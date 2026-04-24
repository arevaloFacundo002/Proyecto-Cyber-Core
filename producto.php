<?php
require_once "conexion.php";
session_start();
$dao = new UserDao();

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Producto no encontrado.");
}
    $rol = $_SESSION['rol'];
    $id = intval($_GET['id']);      # intval() obtiene el valor entero (integer) de una variable

    // Consulta del producto (agregado STOCK)
    $producto = $dao->busqueda_de_producto($id);
    if (!$producto) {
        die("Producto no encontrado.");
    }

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $producto['nombre']; ?> - CyberCore</title>

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: #0a0a0a;
    color: white;
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
    border-bottom: 1px solid #00eaff55;
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

/* PRODUCT PAGE */
.container {
    display: flex;
    padding: 50px;
    gap: 40px;
    max-width: 1200px;
    margin: auto;
}

.product-img {
    width: 45%;
    background: #111;
    padding: 25px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 0 15px #00eaff33;
}

.product-img img {
    width: 100%;
    height: 350px;
    object-fit: contain;
}

.details {
    flex: 1;
}

.details h1 {
    font-size: 36px;
    margin-bottom: 10px;
}

.category {
    color: #00eaff;
    margin-bottom: 20px;
    font-size: 18px;
}

.price {
    font-size: 32px;
    font-weight: bold;
    color: #00c8ff;
    margin-top: 10px;
}

.description {
    margin-top: 20px;
    line-height: 1.5;
    font-size: 17px;
    color: #ddd;
}

.btn-add {
    display: inline-block;
    margin-top: 25px;
    padding: 14px 30px;
    background: #00eaff;
    color: black;
    font-weight: bold;
    border-radius: 25px;
    text-decoration: none;
    font-size: 18px;
    transition: 0.3s;
}

.btn-add:hover {
    background: #00bcd4;
}

.disabled-btn {
    background: #666 !important;
    pointer-events: none;
    cursor: not-allowed;
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

<div class="container">

    <!-- IMAGEN -->
    <div class="product-img">
        <img src="img/<?php echo $producto['imagen_url']; ?>" alt="">
    </div>

    <!-- DETALLES -->
    <div class="details">
        <h1><?php echo $producto['nombre']; ?></h1>

        <div class="category">
            <?php echo $producto['nombre_marca']; ?> • <?php echo $producto['categoria']; ?>
        </div>

        <div class="price">$<?php echo number_format($producto['precio'], 2); ?></div> 

        <!-- STOCK -->
<?php if ($producto['stock'] == 0): ?>

    <p style="color:red; font-size:18px; font-weight:bold;">
        ❌ SIN STOCK
    </p>

<?php elseif ($producto['stock'] <= 2): ?>

    <p style="color:#ff4444; font-size:18px; font-weight:bold;">
        🔥 Últimas unidades disponibles
    </p>

<?php elseif ($producto['stock'] <= 4): ?>

    <p style="color:orange; font-size:18px; font-weight:bold;">
        ⚠️ Pocas unidades en stock
    </p>

<?php endif; ?>


        

        <p class="description"><?php echo $producto['descripcion']; ?></p>

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
<div style="max-width:1200px; margin:50px auto; background:#111; padding:30px; border-radius:12px; box-shadow:0 0 15px #00eaff33;">

    <h2 style="color:#00eaff; margin-bottom:20px;">⭐ Reseñas del producto</h2>


    <?php
    // Consultar reseñas del producto actual (estructura REAL)
    $reseñas = $dao->consultar_reseñas($id);

    ?>

    <!-- LISTADO DE RESEÑAS -->
    <?php if (!empty($reseñas)): ?>
        <?php foreach($reseñas as $r): ?>
            <div style="background:#1a1a1a; padding:18px; border-radius:8px; margin-bottom:15px; border-left:4px solid #00eaff;">

                <strong style="color:#00eaff;">⭐ <?php echo $r['calificacion']; ?>/5</strong>

                <p style="margin:8px 0;"><?php echo htmlspecialchars($r['comentario']); ?></p>

            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color:#aaa;">No hay reseñas todavía. ¡Sé el primero en opinar!</p>
    <?php endif; ?>

    <hr style="border-color:#333; margin:25px 0;">

    
       <!-- FORMULARIO PARA AGREGAR RESEÑA -->
    <h3 style="margin-bottom:10px;">Dejar una reseña</h3>

    <?php if (isset($_SESSION['usuario'])): ?>

        <form action="guardar_resena.php" method="POST">
            <input type="hidden" name="id_producto" value="<?php echo $id; ?>">

            <textarea name="comentario" placeholder="Escribe tu reseña..." required
                style="width:100%; padding:12px; border-radius:8px; border:none; height:120px; background:#222; color:white;"></textarea>

            <label style="color:white; margin-top:10px; display:block;">Calificación:</label>

            <select name="calificacion" required
                    style="padding:10px; border-radius:8px; background:#222; color:white; margin-bottom:12px;">
                <option value="5">⭐⭐⭐⭐⭐</option>
                <option value="4">⭐⭐⭐⭐</option>
                <option value="3">⭐⭐⭐</option>
                <option value="2">⭐⭐</option>
                <option value="1">⭐</option>
            </select>

            <button type="submit"
                style="margin-top:12px; padding:12px 25px; background:#00eaff; border:none; border-radius:20px; font-weight:bold; cursor:pointer;">
                Publicar reseña ⭐
            </button>
        </form>

    <?php else: ?>
        <p style="color:#aaa;">Debes iniciar sesión para dejar una reseña.</p>
    <?php endif; ?>

</div>

</body> 
</html>
