<?php
/**
 * ABM de usuarios del panel de administracion.
 * Todas las acciones requieren sesion iniciada.
 */
class UsuarioController extends Controller
{
    private Usuario $usuarioModel;

    public function __construct(string $base = '')
    {
        parent::__construct($base);
        $this->usuarioModel = new Usuario();
    }

    // usuarios/listar.php
    public function listar(): void
    {
        $this->requiereLogin();

        $busqueda = $_GET['buscar'] ?? "";
        $estado = $_GET['estado'] ?? "";

        $usuarios = $this->usuarioModel->listar_usuarios($busqueda, $estado);

        $this->render('usuarios/listar', [
            'busqueda' => $busqueda,
            'estado' => $estado,
            'usuarios' => $usuarios,
        ]);
    }

    // usuarios/agregar.php
    public function agregar(): void
    {
        $this->requiereLogin();

        $error = '';

        if (isset($_POST['guardar'])) {

            $nombre = $_POST['nombre'];
            $correo = $_POST['correo'];
            $password = $_POST['password'];
            $rela_id_perfil = $_POST['rol'];
            $fecha = date("Y-m-d");

            // VALIDACIONES
            if (strlen($nombre) < 3) {
                $error = "El nombre debe tener al menos 3 caracteres.";
            }
            elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $error = "El correo no es válido.";
            }
            elseif (strlen($password) < 6) {
                $error = "La contraseña debe tener al menos 6 caracteres.";
            }
            elseif ($this->usuarioModel->verificar_correo($correo)) {
                $error = "Ya existe una cuenta con este correo.";
            } else {

                if ($this->usuarioModel->agregar_usuario_panel($nombre, $password, $correo, $fecha, $rela_id_perfil)) {
                    $this->redirect('listar.php');
                } else {
                    echo 'Error al insertar';
                }
            }
        }

        $this->render('usuarios/agregar', ['error' => $error]);
    }

    // usuarios/editar.php
    public function editar(): void
    {
        $this->requiereLogin();

        if (!isset($_GET['id'])) {
            die('Usuario invalido');
        }

        $id = intval($_GET['id']);

        $usuario = $this->usuarioModel->obtener_usuario($id);

        if (!$usuario) {
            echo "Usuario no encontrado.";
            exit;
        }

        if (isset($_POST['guardar'])) {

            $nombre = trim($_POST['nombre']);
            $correo = trim($_POST['correo']);
            $rela_id_perfil = $_POST['nombre_perfil'];

            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                die("Correo inválido");
            }

            if ($this->usuarioModel->editar_usuario($nombre, $correo, $rela_id_perfil, $id)) {
                $this->redirect('listar.php');
            } else {
                echo 'Error al editar Usuario';
            }
        }

        $this->render('usuarios/editar', ['usuario' => $usuario]);
    }

    // usuarios/cambiar_estado.php
    public function cambiarEstado(): void
    {
        $this->requiereLogin();

        if (!$_POST['id'] || !$_POST['estado']) {
            die('Datos invalidos');
        }

        $id = intval($_POST['id']);
        $estado = $_POST['estado'];

        if (!$this->usuarioModel->existe_usuario($id)) {
            echo "El usuario no existe.";
            exit;
        }

        if ($this->usuarioModel->cambiar_estado($id, $estado)) {
            $this->redirect('listar.php');
        } else {
            echo 'Error al cambiar el estado del usuario';
        }
    }
}
