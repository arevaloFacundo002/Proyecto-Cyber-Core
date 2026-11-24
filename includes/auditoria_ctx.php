<?php
// Le avisa a MySQL quién es el usuario de la sesión actual.
// Los triggers de auditoría (sql/auditoria.sql) leen estas variables para saber
// QUIÉN hizo cada cambio. Se llama automáticamente desde models/Database.php y conexion.php.
function cc_auditoria_contexto(mysqli $cx): void
{
    try {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $uid    = isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : null;
        $nombre = $_SESSION['usuario'] ?? null;
        $rol    = $_SESSION['rol'] ?? null;
        $ip     = $_SERVER['REMOTE_ADDR'] ?? null;

        $q = fn($v) => $v === null ? 'NULL' : "'" . $cx->real_escape_string((string) $v) . "'";
        $cx->query("SET @cc_uid = " . ($uid === null ? 'NULL' : $uid) .
                   ", @cc_unombre = " . $q($nombre) .
                   ", @cc_rol = " . $q($rol) .
                   ", @cc_ip = " . $q($ip));
    } catch (Throwable $e) {
        // La auditoría nunca debe romper la aplicación
    }
}
