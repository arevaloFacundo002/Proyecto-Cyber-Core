<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol = $_SESSION['rol'] ?? '';
if (!isset($_SESSION['usuario']) || !in_array($rol, ['administrador', 'empleado'], true)) {
    header("Location: ../../home.php");
    exit;
}

require_once __DIR__ . '/../../models/Devolucion.php';

function volver(string $tipo, string $texto, string $url): void
{
    $_SESSION['devolucion_flash'] = ['tipo' => $tipo, 'texto' => $texto];
    header("Location: " . $url);
    exit;
}

$id = (int) ($_POST['id_devolucion'] ?? 0);
$destino = "../../views/devoluciones/verDevolucion.php?id=" . $id;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id <= 0) {
    header("Location: ../../views/devoluciones/listarDevoluciones.php");
    exit;
}

$token = $_POST['csrf'] ?? '';
if (empty($_SESSION['csrf_admin_devoluciones']) || !hash_equals($_SESSION['csrf_admin_devoluciones'], $token)) {
    volver('error', 'La sesión del formulario venció. Probá de nuevo.', $destino);
}

$accion = $_POST['accion'] ?? '';
$nota   = trim($_POST['nota'] ?? '');
$autor  = $_SESSION['usuario'] ?? 'admin';
$modelo = new Devolucion();

if ($accion === 'aprobar') {
    $r = $modelo->aprobar($id, $autor, $nota !== '' ? $nota : null);
    if (!empty($r['ok'])) {
        volver('ok', 'Devolución #' . $id . ' aprobada. Los productos volvieron al stock y se avisó al cliente.', $destino);
    }
    volver('error', $r['error'] ?? 'No se pudo aprobar.', $destino);
}

if ($accion === 'rechazar') {
    $r = $modelo->rechazar($id, $autor, $nota);
    if (!empty($r['ok'])) {
        volver('ok', 'Devolución #' . $id . ' rechazada. Se avisó al cliente.', $destino);
    }
    volver('error', $r['error'] ?? 'No se pudo rechazar.', $destino);
}

volver('error', 'Acción no válida.', $destino);