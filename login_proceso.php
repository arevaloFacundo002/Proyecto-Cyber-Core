 <?php
session_start();
require_once 'conexion.php';

// Recibimos los datos enviados desde el formulario
$correo = $_POST['correo'] ?? '';
$clave  = $_POST['password'] ?? '';

if (!empty($correo) && !empty($clave)) {

    try {

        // Buscamos el usuario por correo
        $sql = "SELECT id_usuario, nombre, correo, password, rela_id_perfil, estado
                FROM usuarios
                WHERE correo = :correo";

        $stmt = $conexion->prepare($sql);
        $stmt->execute([':correo' => $correo]);
        $usuario = $stmt->fetch();

        // Verificamos usuario y contraseña
        if ($usuario && password_verify($clave, $usuario['password'])) {

            // Verificamos que la cuenta esté activa
            if (isset($usuario['estado']) && $usuario['estado'] == 0) {
                echo "Usuario inactivo. Contacte al administrador.";
                exit;
            }

            // Guardamos datos en la sesión
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombre']     = $usuario['nombre'];
            $_SESSION['correo']     = $usuario['correo'];
            $_SESSION['perfil']     = $usuario['rela_id_perfil'];
            $_SESSION['usuario']    = $usuario['nombre'];

            // Convertimos el ID del perfil en el rol
            switch ($usuario['rela_id_perfil']) {

                case 1:
                    $_SESSION['rol'] = 'administrador';
                    break;

                case 2:
                    $_SESSION['rol'] = 'empleado';
                    break;

                case 3:
                    $_SESSION['rol'] = 'cliente';
                    break;

                case 4:
                    $_SESSION['rol'] = 'usuario';
                    break;

                default:
                    $_SESSION['rol'] = 'usuario';
                    break;
            }

            // Redirigir según el rol
            if ($_SESSION['rol'] == 'administrador' || $_SESSION['rol'] == 'empleado') {

                header("Location: inicio.php");
                exit;

            } else {

                header("Location: home.php");
                exit;
            }

        } else {

            echo "Correo o contraseña incorrectos.";
        }

    } catch (PDOException $e) {

        echo "Error en la autenticación: " . $e->getMessage();
    }

} else {

    echo "Por favor complete todos los campos.";
}
?>