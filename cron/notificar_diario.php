<?php
/**
 * cron/notificar_diario.php
 *
 * Envía un resumen diario por correo a administradores y empleados
 * con los productos que tienen stock bajo.
 */

if (php_sapi_name() !== 'cli') {
    die('Este script solo puede ejecutarse por línea de comandos.');
}

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../config/notificaciones/mail.php';

$UMBRAL_STOCK_BAJO = 5;

try {
    $db = new Database();
    $conexion = $db->getConexion();
} catch (Exception $e) {
    die("[" . date('Y-m-d H:i:s') . "] Error al conectar a la base de datos: " . $e->getMessage() . "\n");
}

// 1) Productos con stock bajo
$sqlProductos = "SELECT codigo, nombre, stock
                  FROM productos
                  WHERE stock <= ?
                  ORDER BY stock ASC";

$stmt = $conexion->prepare($sqlProductos);
if (!$stmt) {
    die("[" . date('Y-m-d H:i:s') . "] Error en la consulta de productos: " . $conexion->error . "\n");
}

$stmt->bind_param("i", $UMBRAL_STOCK_BAJO);
$stmt->execute();
$productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($productos)) {
    echo "[" . date('Y-m-d H:i:s') . "] Sin productos con stock bajo (<= {$UMBRAL_STOCK_BAJO}). No se envía correo.\n";
    exit();
}

// 2) Armar el cuerpo del correo en HTML
$filas = '';
foreach ($productos as $p) {
    $filas .= "<tr>
        <td style='padding:6px 12px;border:1px solid #ddd;'>{$p['codigo']}</td>
        <td style='padding:6px 12px;border:1px solid #ddd;'>{$p['nombre']}</td>
        <td style='padding:6px 12px;border:1px solid #ddd;text-align:center;color:red;font-weight:bold;'>{$p['stock']}</td>
    </tr>";
}

$mensajeHTML = "
    <h2>Reporte diario de stock bajo - Cyber Core</h2>
    <p>Los siguientes productos tienen " . $UMBRAL_STOCK_BAJO . " unidades o menos en stock:</p>
    <table style='border-collapse:collapse;font-family:Segoe UI, sans-serif;'>
        <tr style='background:#0a0a0a;color:white;'>
            <th style='padding:6px 12px;'>Código</th>
            <th style='padding:6px 12px;'>Producto</th>
            <th style='padding:6px 12px;'>Stock</th>
        </tr>
        {$filas}
    </table>
    <p style='color:#777;font-size:12px;'>Correo generado automáticamente.</p>
";

// 3) Buscar destinatarios (admins y empleados activos)
// Nota: LOWER() evita problemas de coincidencia por mayúsculas/minúsculas
$sqlStaff = "SELECT u.correo, u.nombre
             FROM usuarios u
             INNER JOIN perfiles p ON u.rela_id_perfil = p.id_perfil
             WHERE LOWER(p.nombre_perfil) IN ('administrador', 'empleado', 'admin')
             AND u.estado = 'activo'";

$resStaff = $conexion->query($sqlStaff);
if (!$resStaff) {
    die("[" . date('Y-m-d H:i:s') . "] Error en la consulta de usuarios: " . $conexion->error . "\n");
}

$staff = $resStaff->fetch_all(MYSQLI_ASSOC);

if (empty($staff)) {
    echo "[" . date('Y-m-d H:i:s') . "] No se encontraron usuarios activos con rol de administrador o empleado.\n";
    exit();
}

$enviados = 0;
foreach ($staff as $persona) {
    if (empty($persona['correo'])) continue;

    $ok = enviar_mail(
        $persona['correo'],
        $persona['nombre'] ?? '',
        'Reporte diario de stock bajo',
        $mensajeHTML
    );

    if ($ok) {
        $enviados++;
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Reporte procesado. Enviado a {$enviados} de " . count($staff) . " destinatarios.\n";