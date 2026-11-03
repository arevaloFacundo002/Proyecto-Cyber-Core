<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'administrador') {
    header("Location: ../../home.php");
    exit;
}

require_once __DIR__ . '/../../models/Promocion.php';

$lista = "../../views/promociones/listarPromociones.php";

function volver(string $tipo, string $texto, string $url): void
{
    $_SESSION['promo_flash'] = ['tipo' => $tipo, 'texto' => $texto];
    header("Location: " . $url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . $lista);
    exit;
}

$token = $_POST['csrf'] ?? '';
if (empty($_SESSION['csrf_promociones']) || !hash_equals($_SESSION['csrf_promociones'], $token)) {
    volver('error', 'La sesión del formulario venció. Probá de nuevo.', $lista);
}

$modelo = new Promocion();
$accion = $_POST['accion'] ?? '';
$id     = (int) ($_POST['id_promociones'] ?? 0);

if ($accion === 'crear' || $accion === 'editar') {
    $form = $accion === 'editar' ? "../../views/promociones/formPromocion.php?id=" . $id : "../../views/promociones/formPromocion.php";
    $v = $modelo->validar($_POST);
    if (empty($v['ok'])) {
        $_SESSION['promo_old'] = $_POST;
        volver('error', $v['error'], $form);
    }

    if ($accion === 'crear') {
        $ok = $modelo->crear($v);
        volver($ok ? 'ok' : 'error', $ok ? 'Promoción creada.' : 'No se pudo crear la promoción.', $ok ? $lista : $form);
    }

    if ($id <= 0 || !$modelo->obtener($id)) {
        volver('error', 'La promoción no existe.', $lista);
    }
    $ok = $modelo->editar($id, $v);
    volver($ok ? 'ok' : 'error', $ok ? 'Promoción actualizada.' : 'No se pudo guardar.', $ok ? $lista : $form);
}

if ($id <= 0 || !$modelo->obtener($id)) {
    volver('error', 'La promoción no existe.', $lista);
}

if ($accion === 'activar' || $accion === 'desactivar') {
    $modelo->cambiarActiva($id, $accion === 'activar' ? 1 : 0);
    volver('ok', $accion === 'activar' ? 'Promoción activada.' : 'Promoción desactivada.', $lista);
}

if ($accion === 'eliminar') {
    $r = $modelo->eliminar($id);
    volver(!empty($r['ok']) ? 'ok' : 'error', !empty($r['ok']) ? 'Promoción eliminada.' : ($r['error'] ?? 'No se pudo eliminar.'), $lista);
}

volver('error', 'Acción no válida.', $lista);