<?php
session_start();

if (!isset($_GET['id'])) {
    header("Location: carrito.php");
    exit;
}

$id = intval($_GET['id']);

// Si existe el producto en el carrito → eliminarlo
if (isset($_SESSION['carrito'][$id])) {
    unset($_SESSION['carrito'][$id]);
}

// Si el carrito queda vacío, eliminarlo por completo
if (empty($_SESSION['carrito'])) {
    unset($_SESSION['carrito']);
}

// Volver al carrito
header("Location: carrito.php?deleted=1");
exit;
?>
