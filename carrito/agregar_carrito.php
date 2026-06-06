<?php
session_start();
require_once "../models/Producto.php";
$pro = new Producto();

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Producto inválido.");
}

$id = intval($_GET['id']);

# si no esta el login, redirigir
if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php?msg=Debes iniciar sesión");
    exit;
}


// consultar producto y stock
$producto = $pro->consulta_producto_stock($id);
if (!$producto) {
    die("Producto no encontrado.");
}

// validar stock
if ($producto['stock'] <= 0) {
    header("Location: ../producto.php?id=$id&error=sin_stock");
    exit;
}

// --------------------------------------
// 4) CREAR CARRITO si no existe
// --------------------------------------
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// --------------------------------------
// 5) SI EL PRODUCTO YA ESTÁ → aumentar cantidad
// --------------------------------------
if (isset($_SESSION['carrito'][$id])) {
    // Validar que no supere el stock
    if ($_SESSION['carrito'][$id]['cantidad'] + 1 > $producto['stock']) {
        header("Location: ../producto.php?id=$id&error=stock_limit");
        exit;
    }

    $_SESSION['carrito'][$id]['cantidad']++;
} else {
    // --------------------------------------
    // 6) AGREGAR NUEVO PRODUCTO AL CARRITO
    // --------------------------------------
    $_SESSION['carrito'][$id] = [
        'id' => $producto['id_productos'],
        'nombre' => $producto['nombre'],
        'precio' => $producto['precio'],
        'imagen' => $producto['imagen_url'],
        'cantidad' => 1,
        'stock' => $producto['stock']
    ];
}

// --------------------------------------
// 7) REDIRIGIR AL CARRITO (RUTA CORREGIDA)
// --------------------------------------
header("Location: ../carrito/carrito.php?ok=1");
exit;

?>
