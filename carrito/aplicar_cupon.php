 <?php
session_start();
require_once '../models/Cupon.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit;
}

if (!isset($_SESSION['usuario'], $_SESSION['id_usuario'])) {
    header("Location: ../login.php?msg=Debes iniciar sesión");
    exit;
}

$rol = $_SESSION['rol'] ?? null;
if ($rol === 'administrador' || $rol === 'empleado') {
    header("Location: ../inicio.php");
    exit;
}

$tokenSesion = $_SESSION['csrf_checkout'] ?? '';
$tokenForm   = $_POST['csrf'] ?? '';
if ($tokenSesion === '' || !hash_equals($tokenSesion, $tokenForm)) {
    $_SESSION['cupon_flash'] = ['tipo' => 'error', 'texto' => 'La sesión del formulario venció. Intentá de nuevo.'];
    header("Location: checkout.php");
    exit;
}

if (($_POST['accion'] ?? '') === 'quitar') {
    unset($_SESSION['cupon_codigo']);
    header("Location: checkout.php");
    exit;
}

$codigo = Cupon::normalizarCodigo((string) ($_POST['codigo'] ?? ''));
if ($codigo === '') {
    unset($_SESSION['cupon_codigo']);
    $_SESSION['cupon_flash'] = ['tipo' => 'error', 'texto' => 'Escribí el código del cupón.'];
} else {
    $_SESSION['cupon_codigo'] = $codigo;
}

header("Location: checkout.php");
exit;