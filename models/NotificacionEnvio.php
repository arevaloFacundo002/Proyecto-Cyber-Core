<?php

require_once __DIR__ . '/Database.php';

class NotificacionEnvio
{
    private mysqli $conexion;

    public function __construct()
    {
        $db = new Database();
        $this->conexion = $db->getConexion();
    }

    // Registra el intento de envio en estado 'pendiente' y devuelve su id
    public function registrar(int $id_notificacion, string $canal, string $destino)
    {
        $sql = "INSERT INTO notificaciones_envios (canal, destino, rela_id_notificacion)
                VALUES (?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("ssi", $canal, $destino, $id_notificacion);

        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        return false;
    }

    public function marcar_enviado(int $id_envio)
    {
        $sql = "UPDATE notificaciones_envios SET estado = 'enviado', fecha_envio = NOW() WHERE id_envio = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id_envio);
        return $stmt->execute();
    }

    public function marcar_fallido(int $id_envio, string $error)
    {
        $sql = "UPDATE notificaciones_envios SET estado = 'fallido', error = ? WHERE id_envio = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("si", $error, $id_envio);
        return $stmt->execute();
    }
}