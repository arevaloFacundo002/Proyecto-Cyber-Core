<?php

require_once __DIR__ . '/Database.php';

class Caja
{
    private mysqli $conexion;

    public function __construct()
    {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    public static function asignarPagos(mysqli $cx, int $id_pedido): void
    {
        $st = $cx->prepare(
            "UPDATE pagos
             SET rela_id_caja = (SELECT id_caja FROM caja WHERE estado = 'Abierta' ORDER BY id_caja DESC LIMIT 1)
             WHERE rela_id_pedidos = ? AND estado_pagos = 'Aprobado' AND rela_id_caja IS NULL"
        );
        $st->bind_param("i", $id_pedido);
        $st->execute();
    }

    public function abierta(): ?array
    {
        $r = $this->conexion->query(
            "SELECT c.*, u.nombre AS usuario_apertura
             FROM caja c
             INNER JOIN usuarios u ON u.id_usuario = c.rela_id_usuario_apertura
             WHERE c.estado = 'Abierta' ORDER BY c.id_caja DESC LIMIT 1"
        );
        return $r->fetch_assoc() ?: null;
    }

    public function obtener(int $id_caja): ?array
    {
        $st = $this->conexion->prepare(
            "SELECT c.*, ua.nombre AS usuario_apertura, uc.nombre AS usuario_cierre
             FROM caja c
             INNER JOIN usuarios ua ON ua.id_usuario = c.rela_id_usuario_apertura
             LEFT JOIN usuarios uc ON uc.id_usuario = c.rela_id_usuario_cierre
             WHERE c.id_caja = ?"
        );
        $st->bind_param("i", $id_caja);
        $st->execute();
        return $st->get_result()->fetch_assoc() ?: null;
    }

    public function listar(int $limite = 100): array
    {
        $limite = max(1, min(500, $limite));
        $r = $this->conexion->query(
            "SELECT c.*, ua.nombre AS usuario_apertura, uc.nombre AS usuario_cierre
             FROM caja c
             INNER JOIN usuarios ua ON ua.id_usuario = c.rela_id_usuario_apertura
             LEFT JOIN usuarios uc ON uc.id_usuario = c.rela_id_usuario_cierre
             ORDER BY c.id_caja DESC LIMIT " . $limite
        );
        return $r->fetch_all(MYSQLI_ASSOC);
    }

    public function movimientos(int $id_caja): array
    {
        $st = $this->conexion->prepare(
            "SELECT m.*, u.nombre AS usuario
             FROM caja_movimientos m
             INNER JOIN usuarios u ON u.id_usuario = m.rela_id_usuario
             WHERE m.rela_id_caja = ? ORDER BY m.id_caja_movimiento DESC"
        );
        $st->bind_param("i", $id_caja);
        $st->execute();
        return $st->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function resumen(int $id_caja): array
    {
        $caja = $this->obtener($id_caja);
        if (!$caja) {
            return [];
        }

        $st = $this->conexion->prepare(
            "SELECT COALESCE(SUM(CASE WHEN tipo = 'Ingreso' THEN monto END), 0) AS ingresos,
                    COALESCE(SUM(CASE WHEN tipo = 'Egreso'  THEN monto END), 0) AS egresos
             FROM caja_movimientos WHERE rela_id_caja = ?"
        );
        $st->bind_param("i", $id_caja);
        $st->execute();
        $mov = $st->get_result()->fetch_assoc();

        $st = $this->conexion->prepare(
            "SELECT COALESCE(mp.nombre, 'Sin método') AS metodo, COUNT(*) AS cantidad, SUM(pg.monto) AS total,
                    (mp.id_metodo_pago = 1) AS es_efectivo
             FROM pagos pg
             LEFT JOIN metodos_pago mp ON mp.id_metodo_pago = pg.rela_id_metodo_pago
             WHERE pg.rela_id_caja = ? AND pg.estado_pagos = 'Aprobado'
             GROUP BY mp.id_metodo_pago, mp.nombre
             ORDER BY total DESC"
        );
        $st->bind_param("i", $id_caja);
        $st->execute();
        $porMetodo = $st->get_result()->fetch_all(MYSQLI_ASSOC);

        $efectivo = 0.0;
        $otros    = 0.0;
        foreach ($porMetodo as $m) {
            if (!empty($m['es_efectivo'])) {
                $efectivo += (float) $m['total'];
            } else {
                $otros += (float) $m['total'];
            }
        }

        $inicial  = (float) $caja['monto_inicial'];
        $ingresos = (float) $mov['ingresos'];
        $egresos  = (float) $mov['egresos'];

        return [
            'caja'             => $caja,
            'monto_inicial'    => $inicial,
            'ventas_efectivo'  => round($efectivo, 2),
            'ventas_otros'     => round($otros, 2),
            'ingresos'         => round($ingresos, 2),
            'egresos'          => round($egresos, 2),
            'esperado'         => round($inicial + $efectivo + $ingresos - $egresos, 2),
            'por_metodo'       => $porMetodo,
        ];
    }

    public function abrir(int $id_usuario, float $monto_inicial): array
    {
        if ($monto_inicial < 0 || $monto_inicial > 99999999) {
            return ['ok' => false, 'error' => 'El monto inicial no es válido.'];
        }

        try {
            $this->conexion->begin_transaction();

            $r = $this->conexion->query("SELECT id_caja FROM caja WHERE estado = 'Abierta' LIMIT 1 FOR UPDATE");
            if ($r->fetch_assoc()) {
                $this->conexion->rollback();
                return ['ok' => false, 'error' => 'Ya hay una caja abierta. Cerrala antes de abrir otra.'];
            }

            $st = $this->conexion->prepare(
                "INSERT INTO caja (estado, fecha_apertura, monto_inicial, rela_id_usuario_apertura)
                 VALUES ('Abierta', NOW(), ?, ?)"
            );
            $st->bind_param("di", $monto_inicial, $id_usuario);
            $st->execute();
            $id = (int) $this->conexion->insert_id;

            $this->conexion->commit();
            return ['ok' => true, 'id_caja' => $id];
        } catch (Throwable $e) {
            $this->conexion->rollback();
            error_log("Error al abrir la caja: " . $e->getMessage());
            return ['ok' => false, 'error' => 'No se pudo abrir la caja.'];
        }
    }

    public function registrarMovimiento(int $id_caja, int $id_usuario, string $tipo, float $monto, string $motivo): array
    {
        $motivo = mb_substr(trim($motivo), 0, 200);

        if (!in_array($tipo, ['Ingreso', 'Egreso'], true)) {
            return ['ok' => false, 'error' => 'El tipo de movimiento no es válido.'];
        }
        if ($monto <= 0 || $monto > 99999999) {
            return ['ok' => false, 'error' => 'El monto tiene que ser mayor a 0.'];
        }
        if ($motivo === '') {
            return ['ok' => false, 'error' => 'Escribí el motivo del movimiento.'];
        }

        try {
            $this->conexion->begin_transaction();

            $st = $this->conexion->prepare("SELECT estado FROM caja WHERE id_caja = ? FOR UPDATE");
            $st->bind_param("i", $id_caja);
            $st->execute();
            $c = $st->get_result()->fetch_assoc();

            if (!$c || $c['estado'] !== 'Abierta') {
                $this->conexion->rollback();
                return ['ok' => false, 'error' => 'La caja no está abierta.'];
            }

            $st = $this->conexion->prepare(
                "INSERT INTO caja_movimientos (tipo, monto, motivo, fecha, rela_id_caja, rela_id_usuario)
                 VALUES (?, ?, ?, NOW(), ?, ?)"
            );
            $st->bind_param("sdsii", $tipo, $monto, $motivo, $id_caja, $id_usuario);
            $st->execute();

            $this->conexion->commit();
            return ['ok' => true];
        } catch (Throwable $e) {
            $this->conexion->rollback();
            error_log("Error al registrar el movimiento de caja: " . $e->getMessage());
            return ['ok' => false, 'error' => 'No se pudo registrar el movimiento.'];
        }
    }

    public function cerrar(int $id_caja, int $id_usuario, float $contado, string $nota = ''): array
    {
        $nota = mb_substr(trim($nota), 0, 255);

        if ($contado < 0 || $contado > 99999999) {
            return ['ok' => false, 'error' => 'El efectivo contado no es válido.'];
        }

        try {
            $this->conexion->begin_transaction();

            $st = $this->conexion->prepare("SELECT estado FROM caja WHERE id_caja = ? FOR UPDATE");
            $st->bind_param("i", $id_caja);
            $st->execute();
            $c = $st->get_result()->fetch_assoc();

            if (!$c) {
                $this->conexion->rollback();
                return ['ok' => false, 'error' => 'La caja no existe.'];
            }
            if ($c['estado'] !== 'Abierta') {
                $this->conexion->rollback();
                return ['ok' => false, 'error' => 'Esa caja ya estaba cerrada.'];
            }

            $res       = $this->resumen($id_caja);
            $esperado  = (float) $res['esperado'];
            $diferencia = round($contado - $esperado, 2);

            $st = $this->conexion->prepare(
                "UPDATE caja
                 SET estado = 'Cerrada', fecha_cierre = NOW(), rela_id_usuario_cierre = ?,
                     efectivo_esperado = ?, efectivo_contado = ?, diferencia = ?, nota_cierre = ?
                 WHERE id_caja = ?"
            );
            $st->bind_param("idddsi", $id_usuario, $esperado, $contado, $diferencia, $nota, $id_caja);
            $st->execute();

            $this->conexion->commit();
            return ['ok' => true, 'esperado' => $esperado, 'diferencia' => $diferencia];
        } catch (Throwable $e) {
            $this->conexion->rollback();
            error_log("Error al cerrar la caja: " . $e->getMessage());
            return ['ok' => false, 'error' => 'No se pudo cerrar la caja.'];
        }
    }
}