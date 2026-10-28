<?php

function asegurar_tablas_notificaciones(mysqli $cx): void
{
    static $hecho = false;

    if ($hecho) {
        return;
    }
    $hecho = true;

    try {
        $cx->query("CREATE TABLE IF NOT EXISTS anuncios (
            id_anuncio INT AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(150) NOT NULL,
            mensaje TEXT NOT NULL,
            tipo VARCHAR(50) NOT NULL DEFAULT 'anuncio',
            canales VARCHAR(100) NOT NULL DEFAULT 'app',
            audiencia VARCHAR(50) NOT NULL DEFAULT 'todos_clientes',
            enviado TINYINT(1) NOT NULL DEFAULT 0,
            fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            fecha_envio DATETIME NULL,
            rela_id_usuario_creador INT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $cx->query("CREATE TABLE IF NOT EXISTS notificaciones (
            id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
            tipo VARCHAR(50) NOT NULL,
            titulo VARCHAR(150) NOT NULL,
            mensaje TEXT NOT NULL,
            url_accion VARCHAR(255) NULL,
            leida TINYINT(1) NOT NULL DEFAULT 0,
            fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            fecha_leida DATETIME NULL,
            rela_id_usuario INT NOT NULL,
            rela_id_anuncio INT NULL,
            INDEX idx_notif_usuario (rela_id_usuario, leida, fecha_creacion)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $cx->query("CREATE TABLE IF NOT EXISTS notificaciones_envios (
            id_envio INT AUTO_INCREMENT PRIMARY KEY,
            canal VARCHAR(20) NOT NULL,
            destino VARCHAR(255) NOT NULL,
            estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
            error TEXT NULL,
            fecha_envio DATETIME NULL,
            fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            rela_id_notificacion INT NOT NULL,
            INDEX idx_envio_notif (rela_id_notificacion)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        foreach (['notificaciones', 'anuncios'] as $tabla) {
            $r = $cx->query("SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tabla}' AND COLUMN_NAME = 'tipo'");
            $fila = $r ? $r->fetch_assoc() : null;

            if ($fila && strtolower($fila['DATA_TYPE']) === 'enum') {
                $cx->query("ALTER TABLE {$tabla} MODIFY tipo VARCHAR(50) NOT NULL");
            }
        }
    } catch (Throwable $e) {
        error_log('asegurar_tablas_notificaciones: ' . $e->getMessage());
    }
}