<?php
session_start();
include "../conexion.php";

// Validar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Producto inválido.");
}

$id = intval($_GET['id']);

// --------------------------------------
// 1) SI NO ESTÁ LOGIN → redirigir
// --------------------------------------
if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php?msg=Debes iniciar sesión");
    exit;
}

// --------------------------------------
// 2) CONSULTAR PRODUCTO Y STOCK
// --------------------------------------
$sql = "SELECT id_productos, nombre, precio, stock, imagen_url 
        FROM productos 
        WHERE id_productos = ?";

$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$producto = mysqli_fetch_assoc($result);
if (!$producto) {
    die("Producto no encontrado.");
}

// --------------------------------------
// 3) VALIDAR STOCK
// --------------------------------------
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
