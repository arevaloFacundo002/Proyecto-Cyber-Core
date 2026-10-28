<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

require_once __DIR__ . '/../models/Notificacion.php';

$notificacion = new Notificacion();
$id_usuario = (int) $_SESSION['id_usuario'];

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

switch ($accion) {

    case 'listar':
        echo json_encode([
            'notificaciones' => $notificacion->listar_no_leidas($id_usuario),
            'total' => $notificacion->contar_no_leidas($id_usuario)
        ]);
        break;

    case 'recientes':
        $limite = (int) ($_GET['limite'] ?? 5);
        echo json_encode([
            'notificaciones' => $notificacion->listar_recientes($id_usuario, $limite),
            'total_no_leidas' => $notificacion->contar_no_leidas($id_usuario)
        ]);
        break;

    case 'marcar_leida':
        $id_notificacion = (int) ($_POST['id_notificacion'] ?? 0);
        $ok = $notificacion->marcar_leida($id_notificacion, $id_usuario);
        echo json_encode(['ok' => $ok]);
        break;

    case 'marcar_vistas':
        $ids = array_map('intval', $_POST['ids'] ?? []);
        $ok = $notificacion->marcar_varias_leidas($ids, $id_usuario);
        echo json_encode(['ok' => $ok]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Accion invalida']);
}