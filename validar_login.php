<?php
session_start();
include "conexion.php";

$correo = $_POST['correo'];
$contrasena = $_POST['contrasena'];

$sql = "SELECT * FROM usuarios WHERE correo = ? AND contrasena = ?";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "ss", $correo, $contrasena);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

// -------------------------------
//   USUARIO ENCONTRADO
// -------------------------------
if (mysqli_num_rows($result) == 1) {

    $usuario = mysqli_fetch_assoc($result);

    // ===============================
    //  🔍 Verificar si es cliente
    // ===============================
    $sql2 = "SELECT cliente_estado 
             FROM clientes 
             WHERE rela_id_usuario = ?";
    $stmt2 = mysqli_prepare($conexion, $sql2);
    mysqli_stmt_bind_param($stmt2, "i", $usuario['id_usuario']);
    mysqli_stmt_execute($stmt2);
    $result2 = mysqli_stmt_get_result($stmt2);

    if ($result2 && mysqli_num_rows($result2) > 0) {

        $cliente = mysqli_fetch_assoc($result2);
        $estado = $cliente['cliente_estado'];

        // Estados bloqueados
        if ($estado == "bloqueado" || $estado == "inactivo") {
            echo "<script>alert('Tu cuenta de cliente está $estado. Acceso denegado.'); 
                  window.location='index.php';</script>";
            exit;
        }
    }

    // ===============================
    //   LOGIN EXITOSO
    // ===============================
    $_SESSION['usuario'] = $usuario['nombre'];
    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['rol'] = $usuario['tipo_usuario'];
    $_SESSION['correo'] = $usuario['correo'];

    // Redirección por roles
    if ($usuario['tipo_usuario'] == "admin" || $usuario['tipo_usuario'] == "empleado") {
        header("Location: inicio.php");
        exit;
    } else {
        header("Location: home.php");
        exit;
    }

} else {
    echo "<script>alert('Correo o contraseña incorrectos'); window.location='index.php';</script>";
}
?>
