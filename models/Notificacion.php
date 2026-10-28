<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/_tablas_notificaciones.php';

class Notificacion
{
    private mysqli $conexion;

    public function __construct()
    {
        $db = new Database();
        $this->conexion = $db->getConexion();
        asegurar_tablas_notificaciones($this->conexion);
    }

    public function crear(int $id_usuario, string $tipo, string $titulo, string $mensaje, ?string $url_accion = null, ?int $id_anuncio = null)
    {
        $sql = "INSERT INTO notificaciones (tipo, titulo, mensaje, url_accion, rela_id_usuario, rela_id_anuncio)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("ssssii", $tipo, $titulo, $mensaje, $url_accion, $id_usuario, $id_anuncio);

        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        return false;
    }

    public function listar_no_leidas(int $id_usuario, int $limite = 10)
    {
        $sql = "SELECT id_notificacion, tipo, titulo, mensaje, url_accion, fecha_creacion
                FROM notificaciones
                WHERE rela_id_usuario = ? AND leida = 0
                ORDER BY fecha_creacion DESC
                LIMIT ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("ii", $id_usuario, $limite);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function listar_todas(int $id_usuario, int $limite = 50)
    {
        $sql = "SELECT n.id_notificacion, n.tipo, n.titulo, n.mensaje, n.url_accion,
                       n.leida, n.fecha_creacion,
                       GROUP_CONCAT(CONCAT(e.canal, ':', e.estado) SEPARATOR ', ') AS canales_externos
                FROM notificaciones n
                LEFT JOIN notificaciones_envios e ON e.rela_id_notificacion = n.id_notificacion
                WHERE n.rela_id_usuario = ?
                GROUP BY n.id_notificacion
                ORDER BY n.fecha_creacion DESC
                LIMIT ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("ii", $id_usuario, $limite);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function listar_recientes(int $id_usuario, int $limite = 5)
    {
        $sql = "SELECT id_notificacion, tipo, titulo, mensaje, url_accion, leida, fecha_creacion
                FROM notificaciones
                WHERE rela_id_usuario = ?
                ORDER BY fecha_creacion DESC
                LIMIT ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("ii", $id_usuario, $limite);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function marcar_varias_leidas(array $ids, int $id_usuario)
    {
        if (empty($ids)) {
            return true;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE notificaciones SET leida = 1, fecha_leida = NOW()
                WHERE id_notificacion IN ($placeholders) AND rela_id_usuario = ?";

        $tipos = str_repeat('i', count($ids)) . 'i';
        $parametros = array_merge($ids, [$id_usuario]);

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param($tipos, ...$parametros);

        return $stmt->execute();
    }

    public function contar_no_leidas(int $id_usuario)
    {
        $sql = "SELECT COUNT(*) AS total FROM notificaciones WHERE rela_id_usuario = ? AND leida = 0";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();

        return (int) $stmt->get_result()->fetch_assoc()['total'];
    }

    public function obtener_ids_staff()
    {
        $sql = "SELECT u.id_usuario
                FROM usuarios u
                INNER JOIN perfiles p ON u.rela_id_perfil = p.id_perfil
                WHERE p.nombre_perfil IN ('administrador', 'empleado')
                AND u.estado = 'activo'";

        $resultado = $this->conexion->query($sql);

        $ids = [];
        while ($fila = $resultado->fetch_assoc()) {
            $ids[] = (int) $fila['id_usuario'];
        }
        return $ids;
    }

    public function obtener_ids_clientes()
    {
        $sql = "SELECT u.id_usuario
                FROM usuarios u
                INNER JOIN clientes c ON c.rela_id_usuario = u.id_usuario
                WHERE u.estado = 'activo'";

        $resultado = $this->conexion->query($sql);

        $ids = [];
        while ($fila = $resultado->fetch_assoc()) {
            $ids[] = (int) $fila['id_usuario'];
        }
        return $ids;
    }

    public function contar_audiencia(string $audiencia): int
    {
        switch ($audiencia) {
            case 'todos_clientes':
                return count($this->obtener_ids_clientes());
            case 'todos_staff':
                return count($this->obtener_ids_staff());
            case 'todos':
                return count(array_unique(array_merge($this->obtener_ids_clientes(), $this->obtener_ids_staff())));
            default:
                return 0;
        }
    }

    public function marcar_leida(int $id_notificacion, int $id_usuario)
    {
        $sql = "UPDATE notificaciones
                SET leida = 1, fecha_leida = NOW()
                WHERE id_notificacion = ? AND rela_id_usuario = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("ii", $id_notificacion, $id_usuario);

        return $stmt->execute();
    }
}