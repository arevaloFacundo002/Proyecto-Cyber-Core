<?php
session_start();
include "../conexion.php";

// --------------------------------------
// VALIDAR QUE EL USUARIO ESTÉ LOGUEADO
// --------------------------------------
if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php?msg=Debes iniciar sesión para comprar");
    exit;
}

// --------------------------------------
// VALIDAR QUE EXISTA CARRITO
// --------------------------------------
if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    header("Location: ../carrito/carrito.php?msg=carrito_vacio");
    exit;
}

$carrito = $_SESSION['carrito'];

$total = 0;
foreach ($carrito as $item) {
    $total += $item['precio'] * $item['cantidad'];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Finalizar compra - CyberCore</title>

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

.total-box {
    text-align: right;
    margin-top: 20px;
    font-size: 22px;
    font-weight: bold;
}

.btn-next {
    background: #00eaff;
    padding: 12px 22px;
    border-radius: 20px;
    color: black;
    font-weight: bold;
    text-decoration: none;
    float: right;
    margin-top: 15px;
}

.btn-next:hover {
    background: #00bcd4;
}
</style>

</head>

<body>

<header>
    <div class="logo">CYBERCORE</div>
    <nav>
        <a href="../home.php">Inicio</a>
        <a href="../carrito/carrito.php">Carrito</a>
        <a href="../logout.php">Cerrar sesión</a>
    </nav>
</header>

<div class="container">

<h2>🧾 Resumen de tu compra</h2>

<p style="text-align:center; color:#bbb;">
    Revisá los productos antes de continuar al método de envío.
</p>

<table class="table">
    <tr>
        <th>Imagen</th>
        <th>Producto</th>
        <th>Precio</th>
        <th>Cantidad</th>
        <th>Subtotal</th>
    </tr>

    <?php foreach ($carrito as $item): 
        $subtotal = $item['precio'] * $item['cantidad'];
    ?>
    <tr>
        <td><img src="../img/<?php echo $item['imagen']; ?>"></td>

        <td><?php echo $item['nombre']; ?></td>

        <td>$<?php echo number_format($item['precio'], 2); ?></td>

        <td><?php echo $item['cantidad']; ?></td>

        <td>$<?php echo number_format($subtotal, 2); ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<div class="total-box">
    Total a pagar: $<?php echo number_format($total, 2); ?>
</div>

<br><br>

<!-- BOTÓN PARA CONTINUAR AL MÉTODO DE ENVÍO -->
<a class="btn-next" href="metodo_envio.php">
    Continuar a método de envío →
</a>

</div>

</body>
</html>
