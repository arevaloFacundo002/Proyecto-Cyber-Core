<?php
session_start();

if (!isset($_POST['id']) || !isset($_POST['cantidad'])) {
    header("Location: carrito.php");
    exit;
}

$id = intval($_POST['id']);
$cantidad = intval($_POST['cantidad']);

if (!isset($_SESSION['carrito'][$id])) {
    header("Location: carrito.php");
    exit;
}

if ($cantidad < 1) {
    $cantidad = 1;
}

$stockDisponible = $_SESSION['carrito'][$id]['stock'];

if ($cantidad > $stockDisponible) {
    $cantidad = $stockDisponible;
}

$_SESSION['carrito'][$id]['cantidad'] = $cantidad;

header("Location: carrito.php?updated=1");
exit;
?>
