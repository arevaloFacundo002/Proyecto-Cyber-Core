<?php

require_once __DIR__ . '/Database.php';

class Promocion
{
    private mysqli $conexion;

    public function __construct()
    {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    public function listar(): array
    {
        $res = $this->conexion->query(
            "SELECT p.id_promociones, p.nombre, p.tipo, p.valor, p.monto_minmo_requererido,
                    p.fecha_inicio, p.fecha_fin, p.es_activa,
                    (SELECT COUNT(*) FROM detalle_pedidos dp WHERE dp.rela_id_promociones = p.id_promociones) AS usos
             FROM promociones p
             WHERE p.tipo IN ('Porcentaje', 'Fijo')
             ORDER BY p.es_activa DESC, p.id_promociones DESC"
        );
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function obtener(int $id): ?array
    {
        $st = $this->conexion->prepare(
            "SELECT id_promociones, nombre, tipo, valor, monto_minmo_requererido, fecha_inicio, fecha_fin, es_activa
             FROM promociones WHERE id_promociones = ? AND tipo IN ('Porcentaje', 'Fijo')"
        );
        $st->bind_param("i", $id);
        $st->execute();
        return $st->get_result()->fetch_assoc() ?: null;
    }

    public function validar(array $d): array
    {
        $nombre = trim($d['nombre'] ?? '');
        $tipo   = $d['tipo'] ?? '';
        $valor  = (float) str_replace(',', '.', (string) ($d['valor'] ?? 0));
        $minimo = (float) str_replace(',', '.', (string) ($d['minimo'] ?? 0));
        $inicio = trim($d['fecha_inicio'] ?? '');
        $fin    = trim($d['fecha_fin'] ?? '');

        if ($nombre === '' || mb_strlen($nombre) > 200) {
            return ['error' => 'Escribí un nombre para la promoción (hasta 200 caracteres).'];
        }
        if (!in_array($tipo, ['Porcentaje', 'Fijo'], true)) {
            return ['error' => 'Elegí si el descuento es un porcentaje o un monto fijo.'];
        }
        if ($valor <= 0) {
            return ['error' => 'El descuento tiene que ser mayor a cero.'];
        }
        if ($tipo === 'Porcentaje' && $valor > 90) {
            return ['error' => 'El porcentaje no puede ser mayor a 90.'];
        }
        if ($tipo === 'Fijo' && $valor > 10000000) {
            return ['error' => 'El monto fijo es demasiado alto.'];
        }
        if ($minimo < 0 || $minimo > 100000000) {
            return ['error' => 'El monto mínimo no es válido.'];
        }

        $ini = null;
        $fn  = null;
        if ($inicio !== '') {
            $t = DateTime::createFromFormat('Y-m-d', $inicio);
            if (!$t || $t->format('Y-m-d') !== $inicio) {
                return ['error' => 'La fecha de inicio no es válida.'];
            }
            $ini = $inicio . ' 00:00:00';
        }
        if ($fin !== '') {
            $t = DateTime::createFromFormat('Y-m-d', $fin);
            if (!$t || $t->format('Y-m-d') !== $fin) {
                return ['error' => 'La fecha de fin no es válida.'];
            }
            $fn = $fin . ' 23:59:59';
        }
        if ($ini && $fn && $fn < $ini) {
            return ['error' => 'La fecha de fin no puede ser anterior a la de inicio.'];
        }

        return ['ok' => true, 'nombre' => $nombre, 'tipo' => $tipo, 'valor' => round($valor, 2),
                'minimo' => round($minimo, 2), 'inicio' => $ini, 'fin' => $fn];
    }

    public function crear(array $v): bool
    {
        $st = $this->conexion->prepare(
            "INSERT INTO promociones (nombre, tipo, valor, monto_minmo_requererido, fecha_inicio, fecha_fin, es_activa)
             VALUES (?, ?, ?, ?, ?, ?, 1)"
        );
        $st->bind_param("ssddss", $v['nombre'], $v['tipo'], $v['valor'], $v['minimo'], $v['inicio'], $v['fin']);
        return $st->execute();
    }

    public function editar(int $id, array $v): bool
    {
        $st = $this->conexion->prepare(
            "UPDATE promociones SET nombre = ?, tipo = ?, valor = ?, monto_minmo_requererido = ?,
                    fecha_inicio = ?, fecha_fin = ?
             WHERE id_promociones = ? AND tipo IN ('Porcentaje', 'Fijo')"
        );
        $st->bind_param("ssddssi", $v['nombre'], $v['tipo'], $v['valor'], $v['minimo'], $v['inicio'], $v['fin'], $id);
        return $st->execute();
    }

    public function cambiarActiva(int $id, int $activa): bool
    {
        $st = $this->conexion->prepare(
            "UPDATE promociones SET es_activa = ? WHERE id_promociones = ? AND tipo IN ('Porcentaje', 'Fijo')"
        );
        $st->bind_param("ii", $activa, $id);
        return $st->execute();
    }

    public function eliminar(int $id): array
    {
        $st = $this->conexion->prepare("SELECT COUNT(*) FROM detalle_pedidos WHERE rela_id_promociones = ?");
        $st->bind_param("i", $id);
        $st->execute();
        if ((int) $st->get_result()->fetch_row()[0] > 0) {
            return ['ok' => false, 'error' => 'Esta promoción ya se usó en pedidos: no se puede borrar, pero sí desactivar.'];
        }

        $st = $this->conexion->prepare("DELETE FROM promociones WHERE id_promociones = ? AND tipo IN ('Porcentaje', 'Fijo')");
        $st->bind_param("i", $id);
        return ['ok' => $st->execute()];
    }
}