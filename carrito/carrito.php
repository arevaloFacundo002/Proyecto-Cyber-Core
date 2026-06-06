<?php
session_start();
require_once '../models/Producto.php';
$pro = new Producto();

// Si no existe el carrito, crearlo vacío
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$carrito = $_SESSION['carrito'];
$rol = $_SESSION['rol']?? null;

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Carrito - CyberCore</title>

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: #0a0a0a;
    color: white;
}

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
    font-size: 26px;
    font-weight: bold;
    color: #00eaff;
}

nav a {
    color: white;
    margin: 0 15px;
    text-decoration: none;
}

nav a:hover {
    color: #00eaff;
}

.container {
    max-width: 1100px;
    margin: 40px auto;
    background: #111;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 0 20px #00eaff33;
}

h2 {
    text-align: center;
    color: #00eaff;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 25px;
}

.table th, .table td {
    padding: 15px;
    border-bottom: 1px solid #333;
    text-align: center;
}

.table img {
    width: 70px;
}

.qty-input {
    width: 50px;
    padding: 5px;
    text-align: center;
}

.btn-eliminar {
    color: #ff4444;
    font-weight: bold;
    text-decoration: none;
}

.btn-eliminar:hover {
    color: #ff7777;
}

.total-box {
    text-align: right;
    margin-top: 20px;
    font-size: 22px;
    font-weight: bold;
}

.btn-finalizar {
    background: #00eaff;
    padding: 12px 20px;
    border-radius: 20px;
    color: black;
    font-weight: bold;
    text-decoration: none;
    float: right;
    margin-top: 15px;
}

.btn-finalizar:hover {
    background: #00bcd4;
}
</style>
</head>

<body>

<header>
    <div class="logo">CYBERCORE</div>
    <nav>
        <?php if ($rol == 'administrador' || $rol == 'empleado') { ?>
            <a href="../inicio.php">Panel</a>

        <?php }elseif($rol == 'usuario' || $rol == 'cliente'){ ?>
            <a href="carrito.php">🛒 Carrito</a>
            <a class="logout" href="../logout.php">Cerrar sesión</a>
            
        <?php }else{ ?>
            <a href="index.php">Iniciar sesión</a>
            <a href="registro.php">Registrarse</a>
        <?php } ?>
    </nav>
</header>

<div class="container">

<h2>🛒 Tu carrito</h2>

<?php if (empty($carrito)): ?>
    <p style="text-align:center; margin-top:25px;">
        El carrito está vacío.
    </p>
<?php else: ?>

<table class="table">
    <tr>
        <th>Imagen</th>
        <th>Producto</th>
        <th>Precio</th>
        <th>Cantidad</th>
        <th>Subtotal</th>
        <th>Eliminar</th>
    </tr>

    <?php
    $total = 0;
    foreach ($carrito as $item):
        $subtotal = $item['precio'] * $item['cantidad'];
        $total += $subtotal;
    ?>
    <tr>
        <td><img src="../img/<?php echo $item['imagen']; ?>"></td>

        <td><?php echo $item['nombre']; ?></td>

        <td>$<?php echo number_format($item['precio'], 2); ?></td>

        <td>
            <form action="modificar_carrito.php" method="POST" style="display:inline;">
                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">

                <input type="number" 
                       class="qty-input" 
                       name="cantidad" 
                       value="<?php echo $item['cantidad']; ?>" 
                       min="1" 
                       max="<?php echo $item['stock']; ?>">

                <button type="submit">✔️</button>
            </form>
        </td>

        <td>$<?php echo number_format($subtotal, 2); ?></td>

        <td>
            <a class="btn-eliminar" 
               href="eliminar_item.php?id=<?php echo $item['id']; ?>">
               X
            </a>
        </td>
    </tr>

    <?php endforeach; ?>
</table>

<div class="total-box">
    Total: $<?php echo number_format($total, 2); ?>
</div>

<a class="btn-finalizar" href="#">Finalizar compra</a>

<?php endif; ?>

</div>
</body>
</html>
