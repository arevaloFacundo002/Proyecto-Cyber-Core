<?php
session_start();
require_once 'auth/auth.php';
require_once 'models/Producto.php';
$pro = new Producto();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    #verificar usuario
    if (!isset($_SESSION['id_usuario'])) {
        die('Tiene que iniciar sesion para comentar.');
    }

    $id_usuario = $_SESSION['id_usuario'];

    //producto
    $id_producto = $_POST['id_producto']?? null;
    $comentario = trim($_POST['comentario']?? null);
    $calificacion = $_POST['calificacion']?? null;

    #validaciones
    if (!$id_producto || !is_numeric($id_producto)) {
        die('Producto no valido');
    }

    $comentario = trim($comentario);

    if (!$comentario) {
        die('El comentario no puede estar vacio');
    }


    if ($pro->insertar_resenias($comentario, $calificacion,$id_producto)) {
        header('Location:producto.php?id='.$id_producto);
        exit();
    }


}

/*
Faltaria mostrar el nombre del usuario y validar que no pueda comentar mas de una vez (spam)
*/

?>