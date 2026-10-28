<?php

const AVISOS_UMBRAL_STOCK_BAJO = 5;

function avisos_cargar(): void
{
    require_once __DIR__ . '/../config/notificaciones/notificar_multicanal.php';
}

function avisos_canales(array $deseados): array
{
    $canales = ['app'];

    if (in_array('email', $deseados, true) && mail_configurado()) {
        $canales[] = 'email';
    }
    if (in_array('whatsapp', $deseados, true) && whatsapp_configurado()) {
        $canales[] = 'whatsapp';
    }

    return $canales;
}

function avisos_dinero($n): string
{
    return '$ ' . number_format((float) $n, 2, ',', '.');
}

function avisos_datos_pedido(mysqli $cx, int $id_pedido): ?array
{
    $st = $cx->prepare(
        "SELECT p.id_pedidos, p.estado, p.monto_total,
                c.rela_id_usuario AS id_usuario, c.nombre, c.apellido
         FROM pedidos p
         INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
         WHERE p.id_pedidos = ?"
    );
    $st->bind_param("i", $id_pedido);
    $st->execute();

    return $st->get_result()->fetch_assoc() ?: null;
}

function avisar_pedido_nuevo(mysqli $cx, int $id_pedido, array $ids_productos = []): void
{
    try {
        avisos_cargar();

        $p = avisos_datos_pedido($cx, $id_pedido);
        if (!$p) {
            return;
        }

        $total  = avisos_dinero($p['monto_total']);
        $numero = '#' . $id_pedido;

        notificar_multicanal(
            (int) $p['id_usuario'],
            'pedido_recibido',
            "Recibimos tu pedido {$numero}",
            "¡Gracias por tu compra! Tu pedido {$numero} por {$total} fue registrado. Te avisamos apenas se confirme el pago.",
            avisos_canales(['app', 'email']),
            'carrito/mi_pedido.php?id=' . $id_pedido
        );

        notificar_staff(
            'pedido_nuevo',
            "Nuevo pedido {$numero}",
            trim($p['nombre'] . ' ' . $p['apellido']) . " hizo un pedido por {$total}.",
            'views/pedidos/verPedido.php?id=' . $id_pedido
        );

        avisar_stock_bajo($cx, $ids_productos);

    } catch (Throwable $e) {
        error_log('avisar_pedido_nuevo: ' . $e->getMessage());
    }
}

function avisar_stock_bajo(mysqli $cx, array $ids_productos): void
{
    try {
        $ids = array_values(array_unique(array_map('intval', $ids_productos)));
        if (!$ids) {
            return;
        }

        avisos_cargar();

        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $st     = $cx->prepare(
            "SELECT id_producto, codigo, nombre, stock FROM productos
             WHERE id_producto IN ($marcas) AND stock <= ?"
        );
        $umbral = AVISOS_UMBRAL_STOCK_BAJO;
        $st->bind_param(str_repeat('i', count($ids)) . 'i', ...array_merge($ids, [$umbral]));
        $st->execute();

        foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $prod) {
            notificar_staff(
                'stock_bajo',
                'Stock bajo: ' . $prod['nombre'],
                "El producto {$prod['codigo']} - {$prod['nombre']} quedó con {$prod['stock']} unidades.",
                'views/productos/listarProducto.php?buscar=' . urlencode($prod['codigo'])
            );
        }
    } catch (Throwable $e) {
        error_log('avisar_stock_bajo: ' . $e->getMessage());
    }
}

function avisar_pago_aprobado(mysqli $cx, int $id_pedido, ?string $factura = null): void
{
    try {
        avisos_cargar();

        $p = avisos_datos_pedido($cx, $id_pedido);
        if (!$p) {
            return;
        }

        $msg = "Aprobamos el pago de tu pedido #{$id_pedido} por " . avisos_dinero($p['monto_total']) . '.';
        if ($factura) {
            $msg .= " Ya podés ver tu factura {$factura} en Mis pedidos.";
        }

        notificar_multicanal(
            (int) $p['id_usuario'],
            'pago_aprobado',
            "Pago aprobado - pedido #{$id_pedido}",
            $msg,
            avisos_canales(['app', 'email']),
            'carrito/mi_pedido.php?id=' . $id_pedido
        );
    } catch (Throwable $e) {
        error_log('avisar_pago_aprobado: ' . $e->getMessage());
    }
}

function avisar_factura_emitida(mysqli $cx, int $id_pedido, string $numero): void
{
    try {
        avisos_cargar();

        $p = avisos_datos_pedido($cx, $id_pedido);
        if (!$p) {
            return;
        }

        notificar_multicanal(
            (int) $p['id_usuario'],
            'factura_emitida',
            "Tu factura {$numero} está lista",
            "Emitimos la factura {$numero} de tu pedido #{$id_pedido}. Podés verla en Mis pedidos.",
            avisos_canales(['app', 'email']),
            'carrito/mi_pedido.php?id=' . $id_pedido
        );
    } catch (Throwable $e) {
        error_log('avisar_factura_emitida: ' . $e->getMessage());
    }
}

function avisar_estado_pedido(mysqli $cx, int $id_pedido, string $estado, ?string $nota = null, ?string $transportista = null, ?string $tracking = null): void
{
    try {
        avisos_cargar();

        $p = avisos_datos_pedido($cx, $id_pedido);
        if (!$p) {
            return;
        }

        $textos = [
            'Confirmado'           => 'Confirmamos tu pedido.',
            'Preparando pedido'    => 'Estamos preparando tu pedido.',
            'Listo para despachar' => 'Tu pedido está listo para despachar.',
            'En camino'            => 'Tu pedido salió y está en camino.',
            'Entregado'            => '¡Tu pedido fue entregado! Gracias por elegirnos.',
            'Cancelado'            => 'Tu pedido fue cancelado.',
        ];

        $msg = $textos[$estado] ?? "El estado de tu pedido cambió a {$estado}.";

        if (!empty($transportista)) {
            $msg .= " Transporte: {$transportista}.";
        }
        if (!empty($tracking)) {
            $msg .= " N° de seguimiento: {$tracking}.";
        }

        $deseados = ['app', 'email'];
        if (in_array($estado, ['En camino', 'Entregado'], true)) {
            $deseados[] = 'whatsapp';
        }

        notificar_multicanal(
            (int) $p['id_usuario'],
            'estado_pedido',
            "Pedido #{$id_pedido}: {$estado}",
            $msg,
            avisos_canales($deseados),
            'carrito/mi_pedido.php?id=' . $id_pedido
        );
    } catch (Throwable $e) {
        error_log('avisar_estado_pedido: ' . $e->getMessage());
    }
}