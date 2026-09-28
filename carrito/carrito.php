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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Carrito - CyberCore</title>

<link href="../css/theme.css" rel="stylesheet">

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
}

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

.container {
    max-width: 1100px;
    margin: 40px auto;
    background: var(--cc-superficie);
    padding: 25px;
    border-radius: 12px;
    border: 1px solid var(--cc-borde);
    box-shadow: 0 0 20px rgba(0, 234, 255, 0.15);
    color: var(--cc-texto);
}

h2 {
    text-align: center;
    color: var(--cc-primario);
}

.tabla-carrito {
    width: 100%;
    border-collapse: collapse;
    margin-top: 25px;
}

.tabla-carrito th {
    color: var(--cc-primario);
    background: rgba(0, 234, 255, 0.10);
}

.tabla-carrito th, .tabla-carrito td {
    padding: 15px;
    border-bottom: 1px solid var(--cc-borde);
    text-align: center;
    color: var(--cc-texto);
}

.tabla-carrito th {
    color: var(--cc-primario);
}

.tabla-carrito img {
    width: 70px;
}

.qty-input {
    width: 50px;
    padding: 5px;
    text-align: center;
    border-radius: 6px;
    border: 1px solid var(--cc-borde);
    background: var(--cc-fondo);
    color: var(--cc-texto);
}

.tabla-carrito button {
    background: transparent;
    border: 1px solid var(--cc-borde);
    border-radius: 6px;
    cursor: pointer;
    padding: 4px 8px;
}

.btn-eliminar {
    color: var(--cc-peligro);
    font-weight: bold;
    text-decoration: none;
}

.btn-eliminar:hover {
    text-decoration: underline;
}

.total-box {
    text-align: right;
    margin-top: 20px;
    font-size: 22px;
    font-weight: bold;
    color: var(--cc-texto);
}

.btn-finalizar {
    background: var(--cc-primario);
    padding: 12px 20px;
    border-radius: 20px;
    color: var(--cc-texto-sobre-primario);
    font-weight: bold;
    text-decoration: none;
    float: right;
    margin-top: 15px;
    transition: 0.2s;
}

.btn-finalizar:hover {
    background: var(--cc-primario-hover);
}

.vacio {
    text-align: center;
    margin-top: 25px;
    color: var(--cc-texto-secundario);
}
</style>
</head>

<body>

<header>
    <div class="logo">CYBERCORE</div>
    <nav>
        <a href="../home.php">Inicio</a>
        <?php if ($rol == 'administrador' || $rol == 'empleado') { ?>
            <a href="../inicio.php">Panel</a>

        <?php }elseif($rol == 'usuario' || $rol == 'cliente'){ ?>
            <a href="carrito.php">🛒 Carrito</a>
            <a class="logout" href="../logout.php">Cerrar sesión</a>
            
        <?php }else{ ?>
            <a href="../login.php">Iniciar sesión</a>
            <a href="../registro.php">Registrarse</a>
        <?php } ?>
    </nav>
</header>

<div class="container">

<h2>🛒 Tu carrito</h2>

<?php if (empty($carrito)): ?>
    <p class="vacio">
        El carrito está vacío.
    </p>
<?php else: ?>

<table class="tabla-carrito">
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
        <td><img src="../img/<?php echo htmlspecialchars($item['imagen']); ?>"></td>

        <td><?php echo htmlspecialchars($item['nombre']); ?></td>

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
<script src="../js/theme-toggle.js"></script>

</body>
</html>