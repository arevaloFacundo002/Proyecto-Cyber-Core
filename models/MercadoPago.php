<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Pedido.php';
require_once __DIR__ . '/../config/env.php';

class MercadoPago
{
    private mysqli $conexion;

    public function __construct()
    {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    public static function configurado(): bool
    {
        return env_valor('MP_ACCESS_TOKEN') !== '';
    }

    public static function esPasarela(string $nombreMetodo): bool
    {
        $n = mb_strtolower($nombreMetodo, 'UTF-8');
        return strpos($n, 'transferencia') === false && strpos($n, 'efectivo') === false;
    }

    public static function urlRetorno(): string
    {
        $esquema = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $carpeta = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        $carpeta = implode('/', array_map('rawurlencode', explode('/', $carpeta)));
        return $esquema . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $carpeta . '/mp_retorno.php';
    }

    private function llamar(string $metodo, string $ruta, ?array $cuerpo = null): array
    {
        $base = rtrim(env_valor('MP_API_BASE', 'https://api.mercadopago.com'), '/');

        $ch = curl_init($base . $ruta);
        $cabeceras = [
            'Authorization: Bearer ' . env_valor('MP_ACCESS_TOKEN'),
            'Accept: application/json',
        ];
        $opciones = [
            CURLOPT_CUSTOMREQUEST  => $metodo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT        => 30,
        ];
        if ($cuerpo !== null) {
            $cabeceras[] = 'Content-Type: application/json';
            $opciones[CURLOPT_POSTFIELDS] = json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
        }
        $opciones[CURLOPT_HTTPHEADER] = $cabeceras;
        $ca = env_valor('MP_CA_BUNDLE');
        if ($ca !== '') {
            $opciones[CURLOPT_CAINFO] = $ca;
        }
        curl_setopt_array($ch, $opciones);

        $resp   = curl_exec($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            throw new Exception('No se pudo conectar con Mercado Pago (' . $error . ').');
        }
        $json = json_decode($resp, true);
        if (!is_array($json)) {
            throw new Exception('Respuesta inesperada de Mercado Pago.');
        }
        if ($codigo >= 400) {
            throw new Exception('Mercado Pago respondió con un error (' . $codigo . '): ' . ($json['message'] ?? 'sin detalle'));
        }
        return $json;
    }

    private function pedidoDelUsuario(int $id_pedido, int $id_usuario): ?array
    {
        $st = $this->conexion->prepare(
            "SELECT p.id_pedidos, p.monto_total, p.estado, pg.id_pagos, pg.estado_pagos, mp.nombre AS metodo
             FROM pedidos p
             INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
             INNER JOIN pagos pg ON pg.rela_id_pedidos = p.id_pedidos
             LEFT JOIN metodos_pago mp ON mp.id_metodo_pago = pg.rela_id_metodo_pago
             WHERE p.id_pedidos = ? AND c.rela_id_usuario = ?
             ORDER BY pg.id_pagos DESC LIMIT 1"
        );
        $st->bind_param("ii", $id_pedido, $id_usuario);
        $st->execute();
        return $st->get_result()->fetch_assoc() ?: null;
    }

    public function puedePagar(?array $p): bool
    {
        return $p
            && self::configurado()
            && self::esPasarela((string) $p['metodo'])
            && $p['estado_pagos'] === 'Pendiente'
            && $p['estado'] !== 'Cancelado';
    }

    public function puedePagarPedido(int $id_pedido, int $id_usuario): bool
    {
        return $this->puedePagar($this->pedidoDelUsuario($id_pedido, $id_usuario));
    }

    public function iniciarPago(int $id_pedido, int $id_usuario, string $urlRetorno): array
    {
        $p = $this->pedidoDelUsuario($id_pedido, $id_usuario);
        if (!$this->puedePagar($p)) {
            return ['ok' => false, 'aplica' => false, 'error' => 'Este pedido no se puede pagar con Mercado Pago.'];
        }

        try {
            $json = $this->llamar('POST', '/checkout/preferences', [
                'items' => [[
                    'title'       => 'Pedido #' . $id_pedido . ' - CyberCore',
                    'quantity'    => 1,
                    'unit_price'  => round((float) $p['monto_total'], 2),
                    'currency_id' => 'ARS',
                ]],
                'external_reference' => (string) $id_pedido,
                'back_urls' => [
                    'success' => $urlRetorno,
                    'failure' => $urlRetorno,
                    'pending' => $urlRetorno,
                ],
            ]);
        } catch (Throwable $e) {
            error_log('MercadoPago::iniciarPago: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'No pudimos conectar con Mercado Pago. Probá de nuevo en unos minutos.'];
        }

        $url = $json['init_point'] ?? '';
        if ($url === '' || empty($json['id'])) {
            return ['ok' => false, 'error' => 'Mercado Pago no devolvió el enlace de pago.'];
        }

        $st = $this->conexion->prepare("UPDATE pagos SET mp_preference_id = ? WHERE id_pagos = ?");
        $st->bind_param("si", $json['id'], $p['id_pagos']);
        $st->execute();

        return ['ok' => true, 'url' => $url];
    }

    public function confirmarRetorno(int $id_pedido, int $id_usuario, string $payment_id): array
    {
        if (!preg_match('/^\d{1,20}$/', $payment_id)) {
            return ['ok' => false, 'error' => 'No recibimos los datos del pago.'];
        }

        $p = $this->pedidoDelUsuario($id_pedido, $id_usuario);
        if (!$p) {
            return ['ok' => false, 'error' => 'Ese pedido no existe.'];
        }

        return $this->confirmarPago($p, $id_pedido, $payment_id);
    }

    // Le pregunta a Mercado Pago por los pagos del pedido y registra el que corresponda.
    // Con $id_usuario null lo usa el personal (sin controlar que el pedido sea suyo).
    public function verificarPedido(int $id_pedido, ?int $id_usuario = null): array
    {
        if ($id_usuario === null) {
            $st = $this->conexion->prepare(
                "SELECT p.id_pedidos, p.monto_total, p.estado, pg.id_pagos, pg.estado_pagos, mp.nombre AS metodo
                 FROM pedidos p
                 INNER JOIN pagos pg ON pg.rela_id_pedidos = p.id_pedidos
                 LEFT JOIN metodos_pago mp ON mp.id_metodo_pago = pg.rela_id_metodo_pago
                 WHERE p.id_pedidos = ?
                 ORDER BY pg.id_pagos DESC LIMIT 1"
            );
            $st->bind_param("i", $id_pedido);
            $st->execute();
            $p = $st->get_result()->fetch_assoc() ?: null;
        } else {
            $p = $this->pedidoDelUsuario($id_pedido, $id_usuario);
        }

        if (!$p) {
            return ['ok' => false, 'error' => 'Ese pedido no existe.'];
        }
        if ($p['estado_pagos'] === 'Aprobado') {
            return ['ok' => true, 'estado' => 'approved'];
        }
        if (!self::configurado()) {
            return ['ok' => false, 'error' => 'Mercado Pago no está configurado.'];
        }

        try {
            $json = $this->llamar('GET', '/v1/payments/search?external_reference=' . $id_pedido . '&sort=date_created&criteria=desc&limit=10');
        } catch (Throwable $e) {
            error_log('MercadoPago::verificarPedido: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'No pudimos consultar Mercado Pago. Probá de nuevo en unos minutos.'];
        }

        $elegido = null;
        foreach (($json['results'] ?? []) as $pago) {
            if (($pago['status'] ?? '') === 'approved') {
                $elegido = $pago;
                break;
            }
        }
        if ($elegido === null && !empty($json['results'])) {
            $elegido = $json['results'][0];
        }
        if ($elegido === null || empty($elegido['id'])) {
            return ['ok' => false, 'error' => 'Todavía no encontramos ningún pago de este pedido en Mercado Pago.'];
        }

        return $this->confirmarPago($p, $id_pedido, (string) $elegido['id']);
    }

    private function confirmarPago(array $p, int $id_pedido, string $payment_id): array
    {
        if ($p['estado_pagos'] === 'Aprobado') {
            return ['ok' => true, 'estado' => 'approved'];
        }
        if (!self::configurado()) {
            return ['ok' => false, 'error' => 'Mercado Pago no está configurado.'];
        }

        try {
            $pago = $this->llamar('GET', '/v1/payments/' . $payment_id);
        } catch (Throwable $e) {
            error_log('MercadoPago::confirmarPago: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'No pudimos consultar el pago en Mercado Pago. Si ya pagaste, avisanos y lo confirmamos.'];
        }

        if ((string) ($pago['external_reference'] ?? '') !== (string) $id_pedido) {
            return ['ok' => false, 'error' => 'Ese pago no corresponde a este pedido.'];
        }
        if (abs((float) ($pago['transaction_amount'] ?? 0) - (float) $p['monto_total']) > 0.01
            || ($pago['currency_id'] ?? 'ARS') !== 'ARS') {
            return ['ok' => false, 'error' => 'El monto del pago no coincide con el del pedido.'];
        }

        $estado = (string) ($pago['status'] ?? 'unknown');

        try {
            $st = $this->conexion->prepare("UPDATE pagos SET mp_payment_id = ?, mp_status = ? WHERE id_pagos = ?");
            $st->bind_param("ssi", $payment_id, $estado, $p['id_pagos']);
            $st->execute();
        } catch (Throwable $e) {
            if ((int) $this->conexion->errno === 1062 || strpos($e->getMessage(), 'Duplicate') !== false) {
                return ['ok' => false, 'error' => 'Ese pago ya fue usado en otro pedido.'];
            }
            error_log('MercadoPago::confirmarPago (guardar): ' . $e->getMessage());
            return ['ok' => false, 'error' => 'No se pudo registrar el pago.'];
        }

        if ($estado === 'approved') {
            $r = (new Pedido())->aprobarPago($id_pedido, 'Mercado Pago');
            if (empty($r['ok']) && strpos((string) ($r['error'] ?? ''), 'ya estaba aprobado') === false) {
                return ['ok' => false, 'error' => 'El pago está aprobado pero no se pudo registrar. Avisanos y lo confirmamos.'];
            }
        }

        return ['ok' => true, 'estado' => $estado];
    }
}