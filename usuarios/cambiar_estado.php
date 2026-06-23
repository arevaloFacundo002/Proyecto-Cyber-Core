<?php
require_once "../models/Usuario.php";
$user = new Usuario();

// Si no hay sesión
require_once "../auth/auth.php";

if (!$_POST['id'] || !$_POST['estado']) {
    die('Datos invalidos');
}

$id = intval($_POST['id']);
$estado = $_POST['estado'];


// Verificar que el usuario exista
if(!$user->existe_usuario($id)){
    echo "El usuario no existe.";
    exit;
}

if ($user->cambiar_estado($id, $estado)) {
    header("Location: listar.php");
    exit;
}else {
    echo 'Error al cambiar el estado del usuario';
}


?>

