<?php

require_once '../auth/auth.php';
define('REPORTES_OK', 1);
require '../conexion.php';
require_once __DIR__ . '/ReportePDF.php';
require_once __DIR__ . '/motor.php';

$catalogo = reportes_catalogo();
$id = (string) ($_GET['r'] ?? '');

if (!isset($catalogo[$id])) {
    http_response_code(404);
    exit('Reporte no encontrado.');
}

$def = $catalogo[$id];

if (!empty($def['solo_admin']) && ($_SESSION['rol'] ?? '') !== 'administrador') {
    http_response_code(403);
    exit('No tenés permiso para ver este reporte.');
}

$f = reporte_filtros($def, $_GET);
$res = $def['generar']($conexion, $f);
$formato = in_array($_GET['formato'] ?? 'pdf', ['xlsx', 'csv'], true) ? 'xlsx' : 'pdf';

if ($formato === 'xlsx') {
    reporte_xlsx($id, $def, $res, $f, $conexion);
} else {
    reporte_pdf($id, $def, $res, $f, $conexion);
}