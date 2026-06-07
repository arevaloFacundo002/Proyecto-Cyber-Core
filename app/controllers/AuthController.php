<?php
/**
 * Maneja autenticacion y cuentas:
 * login, logout, registro, validacion de cuenta, reenvio de verificacion
 * y recuperacion de contrasena.
 */
class AuthController extends Controller
{
    private Usuario $usuarioModel;

    public function __construct(string $base = '')
    {
        parent::__construct($base);
        $this->usuarioModel = new Usuario();
    }

    // login.php
    public function login(): void
    {
        $error = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $correo = $_POST['correo'] ?? null;
            $password = $_POST['password'] ?? null;

            $usuario = $this->usuarioModel->login($correo);

            if ($usuario && password_verify($password, $usuario['password'])) {

                if ($usuario['validado'] == 0) {
                    $error = "Debes verificar tu correo antes de iniciar sesión";
                    $_SESSION['reenviar_correo'] = $usuario['correo'];
                }
                elseif ($usuario['estado'] == "bloqueado" || $usuario['estado'] == "inactivo") {
                    $error = "Tu cuenta está bloqueada o inactiva";
                } else {

                    $_SESSION['usuario'] = $usuario['nombre'];
                    $_SESSION['id_usuario'] = $usuario['id_usuario'];
                    $_SESSION['rol'] = $usuario['nombre_perfil'];
                    $_SESSION['correo'] = $usuario['correo'];

                    if ($usuario['nombre_perfil'] == 'administrador' || $usuario['nombre_perfil'] == 'empleado') {
                        $this->redirect('inicio.php');
                    } elseif ($usuario['nombre_perfil'] == 'cliente' || $usuario['nombre_perfil'] == 'usuario') {
                        $this->redirect('home.php');
                    } else {
                        $error = "Rol no reconocido";
                    }
                }

            } else {
                $error = "Correo o contraseña incorrectos";
            }
        }

        $this->render('auth/login', ['error' => $error]);
    }

    // logout.php
    public function logout(): void
    {
        $_SESSION = [];

        // borrar cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();

        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");

        $this->redirect('login.php');
    }

    // registro.php
    public function registro(): void
    {
        require_once ROOT_PATH . '/config/mail.php';

        $error = "";
        $exito = "";

        if (isset($_POST['registrar'])) {

            $nombre = trim($_POST['nombre']);
            $correo = trim($_POST['correo']);
            $password = trim($_POST['password']);
            $password2 = trim($_POST['password2']);
            $fecha = date("Y-m-d");
            $rela_id_perfil = 4; // por defecto usuario normal, todavia no es cliente

            // === VALIDACIONES ===
            if (strlen($nombre) < 3) {
                $error = "El nombre debe tener al menos 3 caracteres.";
            }
            elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $error = "El correo no es válido.";
            }
            elseif ($password !== $password2) {
                $error = "Las contraseñas no coinciden.";
            }
            elseif (strlen($password) < 6) {
                $error = "La contraseña debe tener al menos 6 caracteres.";
            }
            else {
                // verificar correo duplicado
                if ($this->usuarioModel->verificar_correo($correo)) {
                    $error = "Ya existe una cuenta con este correo.";
                } else {

                    $token = bin2hex(random_bytes(32));
                    $base_url = "http://" . $_SERVER['HTTP_HOST'];
                    $link = $base_url . "/validar_cuenta.php?token=$token";

                    $mensajeHTML = "<h2>Hola! $nombre</h2>
                        <p>Gracias por registrarte en CYBER CORE</p>
                        <p>Hace click para validar tu cuenta: </p>
                        <p><a href='$link'>Validar Cuenta</a></p>";

                    $this->usuarioModel->registrar_usuario($nombre, $correo, $password, $rela_id_perfil, $fecha, $token);

                    enviar_mail($correo, $nombre, 'Verificar Cuenta', $mensajeHTML);

                    $exito = "Registro exitoso. Revisá tu correo para validar tu cuenta.";
                }
            }
        }

        $this->render('auth/registro', ['error' => $error, 'exito' => $exito]);
    }

    // validar_cuenta.php
    public function validarCuenta(): void
    {
        $error = '';
        $exito = '';

        if (!isset($_GET['token']) || empty($_GET['token'])) {
            $error = 'Token invalido o vacio';
        } else {

            $token = $_GET['token'];
            $usuario = $this->usuarioModel->buscar_por_token($token);

            if (!$usuario) {
                $error = 'Token inválido o expirado';
            }
            elseif ($usuario['validado'] == 1) {
                $error = 'La cuenta ya fue validada.';
            }
            else {
                if ($this->usuarioModel->validar_usuario($token)) {
                    $exito = "Cuenta validada con éxito. Ya podés iniciar sesión.";
                } else {
                    $error = "Error al validar la cuenta.";
                }
            }
        }

        $this->render('auth/validar_cuenta', ['error' => $error, 'exito' => $exito]);
    }

    // config/reenviar_mail.php
    public function reenviarMail(): void
    {
        require_once ROOT_PATH . '/config/mail.php';

        $error = '';
        $exito = '';

        if (!isset($_POST['correo'])) {
            $error = 'Error';
        } else {

            $correo = $_POST['correo'];
            $usuario = $this->usuarioModel->login($correo);

            if (!$usuario) {
                $error = 'No se encontró al usuario.';
            }
            elseif ($usuario['validado'] == 1) {
                $error = 'El usuario ya está validado.';
            }
            else {
                $token = bin2hex(random_bytes(32));
                $this->usuarioModel->actualizar_token($token, $correo);

                $base_url = "http://" . $_SERVER['HTTP_HOST'];
                $link = $base_url . "/validar_cuenta.php?token=$token";

                $mensaje = "
                    <h2>Hola {$usuario['nombre']}</h2>
                    <p>Reenvío de verificación:</p>
                    <a href='$link'>Validar cuenta</a>
                ";

                enviar_mail($correo, $usuario['nombre'], 'Reenvío de verificación', $mensaje);

                $exito = "Correo reenviado correctamente.";
            }
        }

        $this->render('auth/reenviar_mail', ['error' => $error, 'exito' => $exito]);
    }

    // config/recuperar/olvide_password.php
    public function olvidePassword(): void
    {
        $this->render('auth/olvide_password');
    }

    // config/recuperar/enviar_recuperacion.php
    public function enviarRecuperacion(): void
    {
        require_once ROOT_PATH . '/config/mail.php';

        $error = '';
        $exito = '';

        if (!isset($_POST['correo'])) {
            $error = 'Error';
        } else {

            $correo = $_POST['correo'];
            $usuario = $this->usuarioModel->login($correo);

            if (!$usuario) {
                $error = "No existe una cuenta con ese correo";
            } else {

                $token = bin2hex(random_bytes(32));
                $this->usuarioModel->actualizar_token($token, $correo);

                $base_url = "http://" . $_SERVER['HTTP_HOST'];
                $link = $base_url . "/config/recuperar/nueva_password.php?token=$token";

                $mensaje = "
                    <h2>Cyber Core</h2>
                    <h2>Recuperar contraseña</h2>
                    <p>Hacé click en el siguiente enlace:</p>
                    <a href='$link'>Restablecer contraseña</a>
                ";

                enviar_mail($correo, $usuario['nombre'], 'Recuperar contraseña', $mensaje);
                $exito = 'Revisá tu email para cambiar tu contraseña';
            }
        }

        $this->render('auth/enviar_recuperacion', ['error' => $error, 'exito' => $exito]);
    }

    // config/recuperar/nueva_password.php
    public function nuevaPassword(): void
    {
        $error = '';
        $exito = '';

        if (!isset($_GET['token'])) {
            $error = 'Error';
        } else {

            $token = $_GET['token'];
            $usuario = $this->usuarioModel->buscar_por_token($token);

            if (!$usuario) {
                $error = "Token inválido o expirado";
            } else {

                if (isset($_POST['guardar'])) {

                    $pass1 = trim($_POST['password']);
                    $pass2 = trim($_POST['password2']);

                    if ($pass1 !== $pass2) {
                        $error = "Las contraseñas no coinciden";
                    } elseif (strlen($pass1) < 6) {
                        $error = "Mínimo 6 caracteres";
                    } else {

                        $hash = password_hash($pass1, PASSWORD_DEFAULT);
                        $this->usuarioModel->actualizar_password($token, $hash);

                        $exito = "Contraseña actualizada correctamente";
                    }
                }
            }
        }

        $this->render('auth/nueva_password', ['error' => $error, 'exito' => $exito]);
    }
}
