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

<link href="../css/theme.css" rel="stylesheet">
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: var(--cc-fondo);
    color: var(--cc-texto);
}

header {
    background: var(--cc-superficie);
    padding: 18px 50px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: var(--cc-texto);
    border-bottom: 1px solid var(--cc-borde);
}

.logo {
    font-size: 26px;
    font-weight: bold;
    color: var(--cc-primario);
}

nav a {
    color: var(--cc-texto);
    margin: 0 15px;
    text-decoration: none;
}

nav a:hover {
    color: var(--cc-primario);
}

.container {
    max-width: 1100px;
    margin: 40px auto;
    background: var(--cc-superficie);
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 0 20px rgba(0,0,0,.25);
}

h2 {
    text-align: center;
    color: var(--cc-primario);
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 25px;
}

.table th, .table td {
    padding: 15px;
    border-bottom: 1px solid var(--cc-borde);
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
    background: var(--cc-primario);
    padding: 12px 22px;
    border-radius: 20px;
    color: var(--cc-texto-sobre-primario);
    font-weight: bold;
    text-decoration: none;
    float: right;
    margin-top: 15px;
}

.btn-next:hover {
    background: var(--cc-primario-hover);
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

<p style="text-align:center; color:var(--cc-texto-secundario);">
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

<script src="../js/theme-toggle.js"></script>
</body>
</html>