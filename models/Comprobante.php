<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../includes/avisos.php';

class Comprobante
{
    public const MAX_BYTES = 5 * 1024 * 1024;

    private const TIPOS = [
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
        'application/pdf' => 'pdf',
    ];

    private mysqli $conexion;

    public function __construct()
    {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    public static function carpeta(): string
    {
        $dir = dirname(__DIR__) . '/storage/comprobantes';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $ht = $dir . '/.htaccess';
        if (!file_exists($ht)) {
            file_put_contents($ht, "Require all denied\nDeny from all\n");
        }
        return $dir;
    }

    public static function rutaArchivo(string $nombre): ?string
    {
        $nombre = basename($nombre);
        if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp|pdf)$/', $nombre)) {
            return null;
        }
        $ruta = self::carpeta() . '/' . $nombre;
        return is_file($ruta) ? $ruta : null;
    }

    public function cuenta(): ?array
    {
        $r = $this->conexion->query("SELECT * FROM cuenta_bancaria WHERE es_activa = 1 ORDER BY id_cuenta LIMIT 1");
        return $r->fetch_assoc() ?: null;
    }

    public function guardarCuenta(array $d): array
    {
        $titular = trim((string) ($d['titular'] ?? ''));
        $banco   = trim((string) ($d['banco'] ?? ''));
        $cbu     = preg_replace('/\s+/', '', (string) ($d['cbu'] ?? ''));
        $alias   = trim((string) ($d['alias'] ?? ''));

        if ($titular === '' || mb_strlen($titular) > 100) {
            return ['ok' => false, 'error' => 'Escribí el titular de la cuenta (hasta 100 caracteres).'];
        }
        if ($banco === '' || mb_strlen($banco) > 100) {
            return ['ok' => false, 'error' => 'Escribí el nombre del banco (hasta 100 caracteres).'];
        }
        if (!preg_match('/^\d{22}$/', $cbu)) {
            return ['ok' => false, 'error' => 'El CBU tiene que tener exactamente 22 números.'];
        }
        if ($alias !== '' && !preg_match('/^[A-Za-z0-9.\-]{6,20}$/', $alias)) {
            return ['ok' => false, 'error' => 'El alias tiene de 6 a 20 caracteres: letras, números, puntos o guiones.'];
        }
        $alias = $alias !== '' ? strtoupper($alias) : null;

        $actual = $this->cuenta();
        if ($actual) {
            $st = $this->conexion->prepare("UPDATE cuenta_bancaria SET titular = ?, banco = ?, cbu = ?, alias = ? WHERE id_cuenta = ?");
            $st->bind_param("ssssi", $titular, $banco, $cbu, $alias, $actual['id_cuenta']);
        } else {
            $st = $this->conexion->prepare("INSERT INTO cuenta_bancaria (titular, banco, cbu, alias, es_activa) VALUES (?, ?, ?, ?, 1)");
            $st->bind_param("ssss", $titular, $banco, $cbu, $alias);
        }
        return $st->execute() ? ['ok' => true] : ['ok' => false, 'error' => 'No se pudo guardar la cuenta.'];
    }

    public function esTransferencia(int $id_metodo): bool
    {
        $st = $this->conexion->prepare("SELECT nombre FROM metodos_pago WHERE id_metodo_pago = ?");
        $st->bind_param("i", $id_metodo);
        $st->execute();
        $f = $st->get_result()->fetch_assoc();
        return $f && stripos((string) $f['nombre'], 'transferencia') !== false;
    }

    public function pagoDePedido(int $id_pedido): ?array
    {
        $st = $this->conexion->prepare(
            "SELECT pg.id_pagos, pg.estado_pagos, pg.url_comprobante, pg.comprobante_estado, pg.comprobante_fecha,
                    pg.comprobante_nota, pg.rela_id_metodo_pago, mp.nombre AS metodo, p.estado AS estado_pedido
             FROM pagos pg
             INNER JOIN pedidos p ON p.id_pedidos = pg.rela_id_pedidos
             LEFT JOIN metodos_pago mp ON mp.id_metodo_pago = pg.rela_id_metodo_pago
             WHERE pg.rela_id_pedidos = ?
             ORDER BY pg.id_pagos DESC LIMIT 1"
        );
        $st->bind_param("i", $id_pedido);
        $st->execute();
        return $st->get_result()->fetch_assoc() ?: null;
    }

    public function esDelUsuario(int $id_pedido, int $id_usuario): bool
    {
        $st = $this->conexion->prepare(
            "SELECT 1 FROM pedidos p INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
             WHERE p.id_pedidos = ? AND c.rela_id_usuario = ?"
        );
        $st->bind_param("ii", $id_pedido, $id_usuario);
        $st->execute();
        return $st->get_result()->num_rows > 0;
    }

    public function puedeSubir(?array $pago): bool
    {
        return $pago
            && $this->esTransferencia((int) $pago['rela_id_metodo_pago'])
            && $pago['estado_pagos'] === 'Pendiente'
            && $pago['estado_pedido'] !== 'Cancelado'
            && $pago['comprobante_estado'] !== 'Aprobado';
    }

    public function subir(int $id_pedido, int $id_usuario, array $archivo, bool $verificarSubida = true): array
    {
        if (!$this->esDelUsuario($id_pedido, $id_usuario)) {
            return ['ok' => false, 'error' => 'Ese pedido no existe.'];
        }

        $pago = $this->pagoDePedido($id_pedido);
        if (!$this->puedeSubir($pago)) {
            return ['ok' => false, 'error' => 'Este pedido no admite comprobante de transferencia.'];
        }

        $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'Elegí el archivo del comprobante.'];
        }
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            return ['ok' => false, 'error' => 'El archivo es demasiado grande (máximo 5 MB).'];
        }
        if ($error !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'No se pudo subir el archivo. Probá de nuevo.'];
        }

        $tmp = (string) ($archivo['tmp_name'] ?? '');
        if ($verificarSubida && !is_uploaded_file($tmp)) {
            return ['ok' => false, 'error' => 'No se pudo subir el archivo. Probá de nuevo.'];
        }
        if (!is_file($tmp) || filesize($tmp) === 0) {
            return ['ok' => false, 'error' => 'El archivo está vacío.'];
        }
        if (filesize($tmp) > self::MAX_BYTES) {
            return ['ok' => false, 'error' => 'El archivo es demasiado grande (máximo 5 MB).'];
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!isset(self::TIPOS[$mime])) {
            return ['ok' => false, 'error' => 'Solo se aceptan imágenes (JPG, PNG, WEBP) o PDF.'];
        }

        $nombre  = bin2hex(random_bytes(16)) . '.' . self::TIPOS[$mime];
        $destino = self::carpeta() . '/' . $nombre;

        $movido = $verificarSubida ? move_uploaded_file($tmp, $destino) : copy($tmp, $destino);
        if (!$movido) {
            return ['ok' => false, 'error' => 'No se pudo guardar el archivo.'];
        }

        $anterior = $pago['url_comprobante'];

        $st = $this->conexion->prepare(
            "UPDATE pagos SET url_comprobante = ?, comprobante_estado = 'Enviado', comprobante_fecha = NOW(), comprobante_nota = NULL
             WHERE id_pagos = ?"
        );
        $st->bind_param("si", $nombre, $pago['id_pagos']);
        if (!$st->execute()) {
            @unlink($destino);
            return ['ok' => false, 'error' => 'No se pudo registrar el comprobante.'];
        }

        if ($anterior && ($ruta = self::rutaArchivo($anterior))) {
            @unlink($ruta);
        }

        if (function_exists('avisar_comprobante_recibido')) {
            avisar_comprobante_recibido($this->conexion, $id_pedido);
        }

        return ['ok' => true];
    }

    public function rechazar(int $id_pedido, string $nota): array
    {
        $nota = mb_substr(trim($nota), 0, 255);
        if ($nota === '') {
            return ['ok' => false, 'error' => 'Escribí el motivo del rechazo para que el cliente sepa qué corregir.'];
        }

        $pago = $this->pagoDePedido($id_pedido);
        if (!$pago || $pago['comprobante_estado'] !== 'Enviado' || $pago['estado_pagos'] !== 'Pendiente') {
            return ['ok' => false, 'error' => 'Este pedido no tiene un comprobante pendiente de revisar.'];
        }

        $st = $this->conexion->prepare("UPDATE pagos SET comprobante_estado = 'Rechazado', comprobante_nota = ? WHERE id_pagos = ?");
        $st->bind_param("si", $nota, $pago['id_pagos']);
        if (!$st->execute()) {
            return ['ok' => false, 'error' => 'No se pudo rechazar el comprobante.'];
        }

        if (function_exists('avisar_comprobante_rechazado')) {
            avisar_comprobante_rechazado($this->conexion, $id_pedido, $nota);
        }

        return ['ok' => true];
    }

    public static function marcarAprobado(mysqli $cx, int $id_pedido): void
    {
        $st = $cx->prepare("UPDATE pagos SET comprobante_estado = 'Aprobado' WHERE rela_id_pedidos = ? AND comprobante_estado = 'Enviado'");
        $st->bind_param("i", $id_pedido);
        $st->execute();
    }
}