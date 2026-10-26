<?php

require_once __DIR__ . '/../env.php';
require_once __DIR__ . '/../../models/NotificacionEnvio.php';

define('META_WHATSAPP_TOKEN', $_ENV['META_WHATSAPP_TOKEN']);
define('META_PHONE_NUMBER_ID', $_ENV['META_PHONE_NUMBER_ID']);
define('META_TEMPLATE_NAME', $_ENV['META_TEMPLATE_NAME'] ?? 'hello_world');
define('META_TEMPLATE_LANG', $_ENV['META_TEMPLATE_LANG'] ?? 'en_US');

/**
 * Normaliza un numero argentino al formato que pide Meta: 549 + area + numero,
 * sin importar si lo cargaron con +, espacios, guiones, o sin el 54/9.
 * Ej: "3705033550", "+54 370 503-3550", "543705033550" -> "5493705033550"
 */
function normalizar_whatsapp_ar(string $numero): string
{
    $limpio = preg_replace('/\D/', '', $numero); // deja solo digitos

    if (str_starts_with($limpio, '549')) {
        return $limpio;
    }
    if (str_starts_with($limpio, '54')) {
        return '549' . substr($limpio, 2);
    }
    return '549' . $limpio;
}

/**
 * Envia un WhatsApp usando la API de Meta (WhatsApp Cloud API) y deja
 * registro del intento en notificaciones_envios.
 */
function enviar_whatsapp(int $id_notificacion, string $numero_destino, string $mensaje): bool
{
    $envio = new NotificacionEnvio();
    $id_envio = $envio->registrar($id_notificacion, 'whatsapp', $numero_destino);

    $numero_limpio = normalizar_whatsapp_ar($numero_destino);

    $body = [
        'messaging_product' => 'whatsapp',
        'to' => $numero_limpio,
        'type' => 'template',
        'template' => [
            'name' => META_TEMPLATE_NAME,
            'language' => ['code' => META_TEMPLATE_LANG],
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $mensaje]
                    ]
                ]
            ]
        ]
    ];

    if (META_TEMPLATE_NAME === 'hello_world') {
        unset($body['template']['components']);
    }

    $ch = curl_init('https://graph.facebook.com/v21.0/' . META_PHONE_NUMBER_ID . '/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . META_WHATSAPP_TOKEN,
            'Content-Type: application/json'
        ]
    ]);

    $respuesta = curl_exec($ch);
    $codigo_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error_curl = curl_error($ch);
    curl_close($ch);

    if ($codigo_http === 200 || $codigo_http === 201) {
        if ($id_envio) {
            $envio->marcar_enviado($id_envio);
        }
        return true;
    }

    $motivo = $error_curl ?: $respuesta;
    if ($id_envio) {
        $envio->marcar_fallido($id_envio, "HTTP {$codigo_http}: {$motivo}");
    }
    error_log("Error al enviar WhatsApp (Meta): HTTP {$codigo_http} - {$motivo}");
    return false;
}