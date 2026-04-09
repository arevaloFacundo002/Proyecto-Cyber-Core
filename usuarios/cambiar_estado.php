<?php
session_start();
require_once "../conexion.php";
$dao = new UserDao();

// Si no hay sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit;
}

if (!$_POST['id'] || !$_POST['estado']) {
    die('Datos invalidos');
}

$id = intval($_POST['id']);
$estado = $_POST['estado'];


// Verificar que el usuario exista
if(!$dao->existe_usuario($id)){
    echo "El usuario no existe.";
    exit;
}

if ($dao->cambiar_estado($id, $estado)) {
    header("Location: listar.php");
    exit;
}else {
    echo 'Error al cambiar el estado del usuario';
}


?>

