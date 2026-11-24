<?php
require_once __DIR__ . '/Database.php';

/**
 * Módulo de Auditoría.
 *  - Los cambios en tablas críticas se registran SOLO con triggers (sql/auditoria.sql).
 *  - Los eventos que no son cambios de tabla (login, logout, exportaciones) se registran con Auditoria::registrar().
 */
class Auditoria
{
    private mysqli $cx;

    public function __construct()
    {
        $this->cx = (new DataBase())->getConexion();
    }

    /** Registra un evento manual (login, logout, exportación, etc.). Nunca lanza errores. */
    public static function registrar(string $modulo, string $accion, string $descripcion, string $criticidad = 'Baja', array $o = []): void
    {
        try {
            $cx = (new DataBase())->getConexion();
            $uid    = $o['uid']    ?? ($_SESSION['id_usuario'] ?? null);
            $nombre = $o['nombre'] ?? ($_SESSION['usuario'] ?? 'Anónimo');
            $rol    = $o['rol']    ?? ($_SESSION['rol'] ?? null);
            $ip     = $_SERVER['REMOTE_ADDR'] ?? null;
            $tabla  = $o['tabla'] ?? null;
            $reg    = isset($o['registro_id']) ? (string) $o['registro_id'] : null;
            $st = $cx->prepare(
                "INSERT INTO auditoria (rela_id_usuario, usuario_nombre, rol, ip, modulo, accion, criticidad, tabla, registro_id, descripcion)
                 VALUES (?,?,?,?,?,?,?,?,?,?)"
            );
            $uid = $uid !== null ? (int) $uid : null;
            $descripcion = mb_substr($descripcion, 0, 500);
            $st->bind_param("isssssssss", $uid, $nombre, $rol, $ip, $modulo, $accion, $criticidad, $tabla, $reg, $descripcion);
            $st->execute();
        } catch (Throwable $e) {
            // sin romper el flujo si la tabla todavía no existe
        }
    }

    /** Arma el WHERE a partir de los filtros del formulario. */
    private function where(array $f, array &$params, string &$types): string
    {
        $w = ["1=1"];
        $add = function (string $sql, string $t, $v) use (&$w, &$params, &$types) {
            $w[] = $sql; $params[] = $v; $types .= $t;
        };
        if (!empty($f['tabla']) && isset($f['registro']) && $f['registro'] !== '') {
            $add("a.tabla = ?", "s", $f['tabla']);
            $add("a.registro_id = ?", "s", (string) $f['registro']);
            return implode(" AND ", $w); // trazabilidad: ignora el resto de filtros
        }
        if (!empty($f['desde']))      $add("a.fecha >= ?", "s", $f['desde'] . " 00:00:00");
        if (!empty($f['hasta']))      $add("a.fecha <= ?", "s", $f['hasta'] . " 23:59:59");
        if (!empty($f['usuario']))    $add("a.rela_id_usuario = ?", "i", (int) $f['usuario']);
        if (!empty($f['modulo']))     $add("a.modulo = ?", "s", $f['modulo']);
        if (!empty($f['accion']))     $add("a.accion = ?", "s", $f['accion']);
        if (!empty($f['criticidad'])) $add("a.criticidad = ?", "s", $f['criticidad']);
        if (!empty($f['q'])) {
            $w[] = "(a.descripcion LIKE ? OR a.usuario_nombre LIKE ? OR a.registro_id LIKE ?)";
            $l = "%" . $f['q'] . "%";
            array_push($params, $l, $l, $l);
            $types .= "sss";
        }
        return implode(" AND ", $w);
    }

    private function consulta(string $sql, string $types, array $params): array
    {
        $st = $this->cx->prepare($sql);
        if ($params) $st->bind_param($types, ...$params);
        $st->execute();
        return $st->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function listar(array $f, int $limite = 50, int $offset = 0): array
    {
        $p = []; $t = "";
        $w = $this->where($f, $p, $t);
        $limite = max(1, min(20000, $limite));
        $offset = max(0, $offset);
        return $this->consulta("SELECT a.* FROM auditoria a WHERE $w ORDER BY a.id_auditoria DESC LIMIT $limite OFFSET $offset", $t, $p);
    }

    public function contar(array $f): int
    {
        $p = []; $t = "";
        $w = $this->where($f, $p, $t);
        $r = $this->consulta("SELECT COUNT(*) n FROM auditoria a WHERE $w", $t, $p);
        return (int) ($r[0]['n'] ?? 0);
    }

    public function kpis(array $f): array
    {
        $p = []; $t = "";
        $w = $this->where($f, $p, $t);
        $r = $this->consulta(
            "SELECT COUNT(*) total,
                    COALESCE(SUM(a.criticidad='Alta'),0) criticos,
                    COALESCE(SUM(a.accion='LOGIN_FALLIDO'),0) fallidos,
                    COALESCE(SUM(a.accion='ELIMINAR'),0) eliminaciones,
                    COUNT(DISTINCT a.rela_id_usuario) usuarios
             FROM auditoria a WHERE $w", $t, $p);
        return $r[0] ?? ['total' => 0, 'criticos' => 0, 'fallidos' => 0, 'eliminaciones' => 0, 'usuarios' => 0];
    }

    public function opciones(string $campo): array
    {
        if (!in_array($campo, ['modulo', 'accion'], true)) return [];
        $r = $this->cx->query("SELECT DISTINCT $campo v FROM auditoria ORDER BY v");
        return $r ? array_column($r->fetch_all(MYSQLI_ASSOC), 'v') : [];
    }

    public function usuariosPersonal(): array
    {
        $r = $this->cx->query(
            "SELECT u.id_usuario, u.nombre FROM usuarios u JOIN perfiles p ON p.id_perfil = u.rela_id_perfil
             WHERE p.nombre_perfil IN ('administrador','empleado') ORDER BY u.nombre");
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Control de caja: cada caja con quién abrió/cerró, diferencias y movimientos. */
    public function cajas(string $desde, string $hasta): array
    {
        return $this->consulta(
            "SELECT c.*, ua.nombre usuario_apertura, uc.nombre usuario_cierre,
                    (SELECT COUNT(*) FROM caja_movimientos m WHERE m.rela_id_caja = c.id_caja) n_mov,
                    (SELECT COALESCE(SUM(m.monto),0) FROM caja_movimientos m WHERE m.rela_id_caja = c.id_caja AND m.tipo='Ingreso') ingresos,
                    (SELECT COALESCE(SUM(m.monto),0) FROM caja_movimientos m WHERE m.rela_id_caja = c.id_caja AND m.tipo='Egreso') egresos,
                    (SELECT COALESCE(SUM(pg.monto),0) FROM pagos pg WHERE pg.rela_id_caja = c.id_caja AND pg.estado_pagos='Aprobado') cobros
             FROM caja c
             JOIN usuarios ua ON ua.id_usuario = c.rela_id_usuario_apertura
             LEFT JOIN usuarios uc ON uc.id_usuario = c.rela_id_usuario_cierre
             WHERE c.fecha_apertura BETWEEN ? AND ?
             ORDER BY c.id_caja DESC",
            "ss", [$desde . " 00:00:00", $hasta . " 23:59:59"]);
    }

    /** Actividad por usuario del personal (acciones auditadas). */
    public function actividadUsuarios(string $desde, string $hasta): array
    {
        return $this->consulta(
            "SELECT a.rela_id_usuario, a.usuario_nombre, a.rol, COUNT(*) total,
                    SUM(a.criticidad='Alta') altas,
                    SUM(a.accion='LOGIN') logins,
                    SUM(a.accion='LOGIN_FALLIDO') fallidos,
                    SUM(a.accion IN ('CREAR','MODIFICAR','ELIMINAR')) cambios,
                    MAX(a.fecha) ultima
             FROM auditoria a
             WHERE a.fecha BETWEEN ? AND ? AND a.rol IN ('administrador','empleado')
             GROUP BY a.rela_id_usuario, a.usuario_nombre, a.rol
             ORDER BY total DESC",
            "ss", [$desde . " 00:00:00", $hasta . " 23:59:59"]);
    }

    /** Quién atendió/vendió: pedidos y monto por usuario. */
    public function ventasPorUsuario(string $desde, string $hasta): array
    {
        return $this->consulta(
            "SELECT u.nombre, COUNT(*) pedidos, COALESCE(SUM(p.monto_total),0) monto
             FROM pedidos p JOIN usuarios u ON u.id_usuario = p.rela_id_usuario_atiende
             WHERE p.fecha_pedidos BETWEEN ? AND ? AND p.estado <> 'Cancelado'
             GROUP BY u.id_usuario, u.nombre ORDER BY monto DESC",
            "ss", [$desde, $hasta]);
    }
}
