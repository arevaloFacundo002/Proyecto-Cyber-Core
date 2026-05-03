<?php
require_once "../conexion.php";
$dao = new UserDao();

// Si no hay sesión
require_once "../auth.php";

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

