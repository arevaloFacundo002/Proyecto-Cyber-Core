<?php

require_once __DIR__ . '/Database.php';

class Cupon
{
    private mysqli $conexion;

    public function __construct()
    {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    public static function normalizarCodigo(string $codigo): string
    {
        $codigo = strtoupper(trim($codigo));
        return substr(preg_replace('/[^A-Z0-9_-]/', '', $codigo), 0, 40);
    }

    private static function plata(float $v): string
    {
        return '$' . number_format($v, 2, ',', '.');
    }

    // Revisa si un cupón se puede usar con ese subtotal y ese cliente. Con $bloquear lo deja
    // reservado dentro de la transacción de la compra, para que dos clientes no gasten el último uso a la vez.
    public static function evaluar(mysqli $cx, string $codigo, ?int $id_cliente, float $subtotal, bool $bloquear = false): array
    {
        $codigo = self::normalizarCodigo($codigo);
        if ($codigo === '') {
            return ['ok' => false, 'error' => 'Escribí el código del cupón.'];
        }

        $sql = "SELECT id_cupon, codigo, tipo, valor, compra_minima, fecha_inicio, fecha_fin,
                       usos_maximos, usos_realizados, es_activo
                FROM cupones WHERE codigo = ?" . ($bloquear ? " FOR UPDATE" : "");
        $st = $cx->prepare($sql);
        $st->bind_param("s", $codigo);
        $st->execute();
        $c = $st->get_result()->fetch_assoc();

        if (!$c || !(int) $c['es_activo']) {
            return ['ok' => false, 'error' => 'El cupón no existe o no está disponible.'];
        }

        $ahora = date('Y-m-d H:i:s');
        if ($c['fecha_inicio'] !== null && $c['fecha_inicio'] > $ahora) {
            return ['ok' => false, 'error' => 'Este cupón todavía no está vigente.'];
        }
        if ($c['fecha_fin'] !== null && $c['fecha_fin'] < $ahora) {
            return ['ok' => false, 'error' => 'Este cupón está vencido.'];
        }
        if ($c['usos_maximos'] !== null && (int) $c['usos_realizados'] >= (int) $c['usos_maximos']) {
            return ['ok' => false, 'error' => 'Este cupón ya alcanzó su límite de usos.'];
        }
        if ($subtotal < (float) $c['compra_minima']) {
            return ['ok' => false, 'error' => 'Este cupón requiere una compra mínima de ' . self::plata((float) $c['compra_minima']) . '.'];
        }

        if ($id_cliente) {
            $st = $cx->prepare("SELECT 1 FROM cupon_usos WHERE rela_id_cupon = ? AND rela_id_cliente = ? LIMIT 1");
            $st->bind_param("ii", $c['id_cupon'], $id_cliente);
            $st->execute();
            if ($st->get_result()->num_rows > 0) {
                return ['ok' => false, 'error' => 'Ya usaste este cupón.'];
            }
        }

        $desc = $c['tipo'] === 'Porcentaje'
            ? round($subtotal * (float) $c['valor'] / 100, 2)
            : (float) $c['valor'];
        $desc = round(min($desc, $subtotal), 2);

        if ($desc <= 0) {
            return ['ok' => false, 'error' => 'Este cupón no aplica a tu compra.'];
        }

        return [
            'ok'        => true,
            'id'        => (int) $c['id_cupon'],
            'codigo'    => $c['codigo'],
            'tipo'      => $c['tipo'],
            'valor'     => (float) $c['valor'],
            'descuento' => $desc,
        ];
    }

    public static function registrarUso(mysqli $cx, int $id_cupon, int $id_pedido, int $id_cliente, float $monto): void
    {
        $st = $cx->prepare(
            "INSERT INTO cupon_usos (rela_id_cupon, rela_id_pedidos, rela_id_cliente, monto_descuento, fecha)
             VALUES (?, ?, ?, ?, NOW())"
        );
        $st->bind_param("iiid", $id_cupon, $id_pedido, $id_cliente, $monto);
        $st->execute();

        $st = $cx->prepare("UPDATE cupones SET usos_realizados = usos_realizados + 1 WHERE id_cupon = ?");
        $st->bind_param("i", $id_cupon);
        $st->execute();
    }

    // Si el pedido se cancela, el cupón vuelve a quedar disponible para ese cliente.
    public static function liberarUso(mysqli $cx, int $id_pedido): void
    {
        $st = $cx->prepare("SELECT id_cupon_uso, rela_id_cupon FROM cupon_usos WHERE rela_id_pedidos = ?");
        $st->bind_param("i", $id_pedido);
        $st->execute();
        $uso = $st->get_result()->fetch_assoc();
        if (!$uso) {
            return;
        }

        $st = $cx->prepare("DELETE FROM cupon_usos WHERE id_cupon_uso = ?");
        $st->bind_param("i", $uso['id_cupon_uso']);
        $st->execute();

        $st = $cx->prepare("UPDATE cupones SET usos_realizados = GREATEST(usos_realizados - 1, 0) WHERE id_cupon = ?");
        $st->bind_param("i", $uso['rela_id_cupon']);
        $st->execute();
    }

    public function listar(): array
    {
        $res = $this->conexion->query(
            "SELECT id_cupon, codigo, descripcion, tipo, valor, compra_minima, fecha_inicio, fecha_fin,
                    usos_maximos, usos_realizados, es_activo
             FROM cupones ORDER BY es_activo DESC, id_cupon DESC"
        );
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function obtener(int $id): ?array
    {
        $st = $this->conexion->prepare(
            "SELECT id_cupon, codigo, descripcion, tipo, valor, compra_minima, fecha_inicio, fecha_fin,
                    usos_maximos, usos_realizados, es_activo
             FROM cupones WHERE id_cupon = ?"
        );
        $st->bind_param("i", $id);
        $st->execute();
        return $st->get_result()->fetch_assoc() ?: null;
    }

    public function validar(array $d, int $id_actual = 0): array
    {
        $codigo = self::normalizarCodigo((string) ($d['codigo'] ?? ''));
        $desc   = mb_substr(trim((string) ($d['descripcion'] ?? '')), 0, 200);
        $tipo   = $d['tipo'] ?? '';
        $valor  = (float) str_replace(',', '.', (string) ($d['valor'] ?? 0));
        $minimo = (float) str_replace(',', '.', (string) ($d['minimo'] ?? 0));
        $usos   = trim((string) ($d['usos_maximos'] ?? ''));
        $inicio = trim((string) ($d['fecha_inicio'] ?? ''));
        $fin    = trim((string) ($d['fecha_fin'] ?? ''));

        if (strlen($codigo) < 3) {
            return ['error' => 'El código tiene que tener al menos 3 letras o números (sin espacios).'];
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
            return ['error' => 'La compra mínima no es válida.'];
        }

        $maximo = null;
        if ($usos !== '') {
            if (!ctype_digit($usos) || (int) $usos < 1 || (int) $usos > 1000000) {
                return ['error' => 'El límite de usos tiene que ser un número entero mayor a cero (o dejarlo vacío).'];
            }
            $maximo = (int) $usos;
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

        $st = $this->conexion->prepare("SELECT id_cupon FROM cupones WHERE codigo = ? AND id_cupon <> ?");
        $st->bind_param("si", $codigo, $id_actual);
        $st->execute();
        if ($st->get_result()->num_rows > 0) {
            return ['error' => 'Ya existe un cupón con ese código.'];
        }

        return ['ok' => true, 'codigo' => $codigo, 'descripcion' => $desc !== '' ? $desc : null, 'tipo' => $tipo,
                'valor' => round($valor, 2), 'minimo' => round($minimo, 2), 'usos_maximos' => $maximo,
                'inicio' => $ini, 'fin' => $fn];
    }

    public function crear(array $v): bool
    {
        $st = $this->conexion->prepare(
            "INSERT INTO cupones (codigo, descripcion, tipo, valor, compra_minima, fecha_inicio, fecha_fin, usos_maximos, es_activo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)"
        );
        $st->bind_param("sssddssi", $v['codigo'], $v['descripcion'], $v['tipo'], $v['valor'], $v['minimo'],
            $v['inicio'], $v['fin'], $v['usos_maximos']);
        return $st->execute();
    }

    public function editar(int $id, array $v): bool
    {
        $st = $this->conexion->prepare(
            "UPDATE cupones SET codigo = ?, descripcion = ?, tipo = ?, valor = ?, compra_minima = ?,
                    fecha_inicio = ?, fecha_fin = ?, usos_maximos = ?
             WHERE id_cupon = ?"
        );
        $st->bind_param("sssddssii", $v['codigo'], $v['descripcion'], $v['tipo'], $v['valor'], $v['minimo'],
            $v['inicio'], $v['fin'], $v['usos_maximos'], $id);
        return $st->execute();
    }

    public function cambiarActivo(int $id, int $activo): bool
    {
        $st = $this->conexion->prepare("UPDATE cupones SET es_activo = ? WHERE id_cupon = ?");
        $st->bind_param("ii", $activo, $id);
        return $st->execute();
    }

    public function eliminar(int $id): array
    {
        $st = $this->conexion->prepare("SELECT COUNT(*) FROM cupon_usos WHERE rela_id_cupon = ?");
        $st->bind_param("i", $id);
        $st->execute();
        if ((int) $st->get_result()->fetch_row()[0] > 0) {
            return ['ok' => false, 'error' => 'Este cupón ya se usó en pedidos: no se puede borrar, pero sí desactivar.'];
        }

        $st = $this->conexion->prepare("DELETE FROM cupones WHERE id_cupon = ?");
        $st->bind_param("i", $id);
        return ['ok' => $st->execute()];
    }
}