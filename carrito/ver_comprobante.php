<?php
session_start();
require_once '../models/Comprobante.php';

if (!isset($_SESSION['usuario'], $_SESSION['id_usuario'])) {
    http_response_code(403);
    exit('Sin acceso.');
}

$id_pedido = (int) ($_GET['id'] ?? 0);
$modelo    = new Comprobante();
$esStaff   = in_array($_SESSION['rol'] ?? '', ['administrador', 'empleado'], true);

if ($id_pedido <= 0 || (!$esStaff && !$modelo->esDelUsuario($id_pedido, (int) $_SESSION['id_usuario']))) {
    http_response_code(404);
    exit('No encontrado.');
}

$pago = $modelo->pagoDePedido($id_pedido);
$ruta = ($pago && $pago['url_comprobante']) ? Comprobante::rutaArchivo($pago['url_comprobante']) : null;

if (!$ruta) {
    http_response_code(404);
    exit('No encontrado.');
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($ruta);
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($ruta));
header('Content-Disposition: inline; filename="comprobante-pedido-' . $id_pedido . '.' . pathinfo($ruta, PATHINFO_EXTENSION) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($ruta);
exit;