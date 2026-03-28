<?php
session_start();
include "../conexion.php";

// Si no hay sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit;
}

if (!isset($_GET['id'])) {
    echo "ID no especificado.";
    exit;
}

$id = intval($_GET['id']);

// Verificar que el usuario exista
$sql_check = "SELECT id_usuario FROM usuarios WHERE id_usuario = ?";
$stmt = mysqli_prepare($conexion, $sql_check);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($res) == 0) {
    echo "El usuario no existe.";
    exit;
}

/* ----------------------------------------------------
   🚫 Verificar si este usuario tiene un cliente asociado
-----------------------------------------------------*/
$sql_cliente = "SELECT id_cliente FROM clientes WHERE rela_id_usuario = ?";
$stmtC = mysqli_prepare($conexion, $sql_cliente);
mysqli_stmt_bind_param($stmtC, "i", $id);
mysqli_stmt_execute($stmtC);
$resC = mysqli_stmt_get_result($stmtC);

// Si tiene cliente → NO SE PUEDE ELIMINAR
if (mysqli_num_rows($resC) > 0) {
    echo "<h2 style='color:red; text-align:center; margin-top:40px;'>
            ❌ No se puede eliminar este usuario porque tiene un cliente asociado.
          </h2>
          <div style='text-align:center; margin-top:20px;'>
            <a href='listar.php' style='padding:10px 20px; background:#00b1cc; color:white; text-decoration:none; border-radius:10px;'>
                Volver a la lista
            </a>
          </div>";
    exit;
}

/* -----------------------------------------
   ✔ Como NO tiene cliente → proceder a eliminar
------------------------------------------ */

$sql_delete = "DELETE FROM usuarios WHERE id_usuario = ?";
$stmtD = mysqli_prepare($conexion, $sql_delete);
mysqli_stmt_bind_param($stmtD, "i", $id);
mysqli_stmt_execute($stmtD);

header("Location: listar.php");
exit;
?>

