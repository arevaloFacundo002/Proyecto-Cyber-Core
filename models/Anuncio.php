<?php

require_once __DIR__ . '/Database.php';

class Anuncio
{
    private mysqli $conexion;

    public function __construct()
    {
        $db = new Database();
        $this->conexion = $db->getConexion();
    }

    // CREAR (todavia no se envia, solo se guarda)
    public function crear(string $titulo, string $mensaje, string $tipo, string $canales, string $audiencia, int $id_creador)
    {
        $sql = "INSERT INTO anuncios (titulo, mensaje, tipo, canales, audiencia, rela_id_usuario_creador)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("sssssi", $titulo, $mensaje, $tipo, $canales, $audiencia, $id_creador);

        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        return false;
    }

    public function obtener(int $id_anuncio)
    {
        $sql = "SELECT * FROM anuncios WHERE id_anuncio = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id_anuncio);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function listar(int $limite = 50)
    {
        $sql = "SELECT * FROM anuncios ORDER BY fecha_creacion DESC LIMIT ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $limite);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function marcar_enviado(int $id_anuncio)
    {
        $sql = "UPDATE anuncios SET enviado = 1, fecha_envio = NOW() WHERE id_anuncio = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id_anuncio);
        return $stmt->execute();
    }

    // EDITAR (solo tiene sentido mientras es borrador, enviado = 0)
    public function editar(int $id_anuncio, string $titulo, string $mensaje, string $tipo, string $canales, string $audiencia)
    {
        $sql = "UPDATE anuncios SET titulo = ?, mensaje = ?, tipo = ?, canales = ?, audiencia = ?
                WHERE id_anuncio = ? AND enviado = 0";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("sssssi", $titulo, $mensaje, $tipo, $canales, $audiencia, $id_anuncio);

        return $stmt->execute();
    }

    // ELIMINAR (solo borradores; un anuncio ya enviado queda como registro historico)
    public function eliminar(int $id_anuncio)
    {
        $sql = "DELETE FROM anuncios WHERE id_anuncio = ? AND enviado = 0";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id_anuncio);
        return $stmt->execute();
    }
}