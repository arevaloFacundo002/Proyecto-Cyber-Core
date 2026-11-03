<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'administrador') {
    header("Location: ../../home.php");
    exit;
}

require_once __DIR__ . '/../../models/Cupon.php';

$lista = "../../views/cupones/listarCupones.php";

function volver(string $tipo, string $texto, string $url): void
{
    $_SESSION['cupon_flash'] = ['tipo' => $tipo, 'texto' => $texto];
    header("Location: " . $url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . $lista);
    exit;
}

$token = $_POST['csrf'] ?? '';
if (empty($_SESSION['csrf_cupones']) || !hash_equals($_SESSION['csrf_cupones'], $token)) {
    volver('error', 'La sesión del formulario venció. Probá de nuevo.', $lista);
}

$modelo = new Cupon();
$accion = $_POST['accion'] ?? '';
$id     = (int) ($_POST['id_cupon'] ?? 0);

if ($accion === 'crear' || $accion === 'editar') {
    $form = $accion === 'editar' ? "../../views/cupones/formCupon.php?id=" . $id : "../../views/cupones/formCupon.php";
    $v = $modelo->validar($_POST, $accion === 'editar' ? $id : 0);
    if (empty($v['ok'])) {
        $_SESSION['cupon_old'] = $_POST;
        volver('error', $v['error'], $form);
    }

    if ($accion === 'crear') {
        $ok = $modelo->crear($v);
        volver($ok ? 'ok' : 'error', $ok ? 'Cupón creado.' : 'No se pudo crear el cupón.', $ok ? $lista : $form);
    }

    if ($id <= 0 || !$modelo->obtener($id)) {
        volver('error', 'El cupón no existe.', $lista);
    }
    $ok = $modelo->editar($id, $v);
    volver($ok ? 'ok' : 'error', $ok ? 'Cupón actualizado.' : 'No se pudo guardar.', $ok ? $lista : $form);
}

if ($id <= 0 || !$modelo->obtener($id)) {
    volver('error', 'El cupón no existe.', $lista);
}

if ($accion === 'activar' || $accion === 'desactivar') {
    $modelo->cambiarActivo($id, $accion === 'activar' ? 1 : 0);
    volver('ok', $accion === 'activar' ? 'Cupón activado.' : 'Cupón desactivado.', $lista);
}

if ($accion === 'eliminar') {
    $r = $modelo->eliminar($id);
    volver(!empty($r['ok']) ? 'ok' : 'error', !empty($r['ok']) ? 'Cupón eliminado.' : ($r['error'] ?? 'No se pudo eliminar.'), $lista);
}

volver('error', 'Acción no válida.', $lista);