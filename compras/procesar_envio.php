<?php
session_start();

if (!isset($_POST['metodo_envio'])) {
    header("Location: metodo_envio.php?error=no_seleccionado");
    exit;
}

$_SESSION['metodo_envio'] = $_POST['metodo_envio'];

header("Location: metodo_pago.php");
exit;
?>
