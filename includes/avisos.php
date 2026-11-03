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

        $htmlFactura = null;
        if ($factura) {
            try {
                require_once __DIR__ . '/factura_email.php';
                $htmlFactura = factura_email_de_pedido($id_pedido, "Hola {$p['nombre']}, aprobamos el pago de tu pedido #{$id_pedido}. Acá tenés tu factura.");
            } catch (Throwable $e) {
                error_log('avisar_pago_aprobado (factura): ' . $e->getMessage());
            }
        }

        notificar_multicanal(
            (int) $p['id_usuario'],
            'pago_aprobado',
            "Pago aprobado - pedido #{$id_pedido}",
            $msg,
            avisos_canales(['app', 'email']),
            'carrito/mi_pedido.php?id=' . $id_pedido,
            null,
            $htmlFactura
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

        $htmlFactura = null;
        try {
            require_once __DIR__ . '/factura_email.php';
            $htmlFactura = factura_email_de_pedido($id_pedido, "Hola {$p['nombre']}, emitimos la factura de tu pedido #{$id_pedido}.");
        } catch (Throwable $e) {
            error_log('avisar_factura_emitida (factura): ' . $e->getMessage());
        }

        notificar_multicanal(
            (int) $p['id_usuario'],
            'factura_emitida',
            "Tu factura {$numero} está lista",
            "Emitimos la factura {$numero} de tu pedido #{$id_pedido}. Podés verla en Mis pedidos.",
            avisos_canales(['app', 'email']),
            'carrito/mi_pedido.php?id=' . $id_pedido,
            null,
            $htmlFactura
        );
    } catch (Throwable $e) {
        error_log('avisar_factura_emitida: ' . $e->getMessage());
    }
}

function avisar_comprobante_recibido(mysqli $cx, int $id_pedido): void
{
    try {
        avisos_cargar();

        $p = avisos_datos_pedido($cx, $id_pedido);
        if (!$p) {
            return;
        }

        notificar_staff(
            'comprobante_recibido',
            "Comprobante del pedido #{$id_pedido}",
            trim($p['nombre'] . ' ' . $p['apellido']) . " subió el comprobante de la transferencia de " . avisos_dinero($p['monto_total']) . '. Revisalo y aprobá el pago.',
            'views/pedidos/verPedido.php?id=' . $id_pedido
        );
    } catch (Throwable $e) {
        error_log('avisar_comprobante_recibido: ' . $e->getMessage());
    }
}

function avisar_comprobante_rechazado(mysqli $cx, int $id_pedido, string $motivo): void
{
    try {
        avisos_cargar();

        $p = avisos_datos_pedido($cx, $id_pedido);
        if (!$p) {
            return;
        }

        notificar_multicanal(
            (int) $p['id_usuario'],
            'comprobante_rechazado',
            "Revisá el comprobante del pedido #{$id_pedido}",
            "No pudimos aceptar el comprobante de tu transferencia. Motivo: {$motivo}. Subí uno nuevo desde Mis pedidos.",
            avisos_canales(['app', 'email']),
            'carrito/mi_pedido.php?id=' . $id_pedido
        );
    } catch (Throwable $e) {
        error_log('avisar_comprobante_rechazado: ' . $e->getMessage());
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


function avisar_devolucion_solicitada(mysqli $cx, int $id_devolucion): void
{
    try {
        avisos_cargar();

        $st = $cx->prepare(
            "SELECT d.rela_id_pedidos, d.monto_reembolso, c.nombre, c.apellido, c.rela_id_usuario AS id_usuario
             FROM devolucion d
             INNER JOIN pedidos p ON p.id_pedidos = d.rela_id_pedidos
             INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
             WHERE d.id_devolucion = ?"
        );
        $st->bind_param("i", $id_devolucion);
        $st->execute();
        $d = $st->get_result()->fetch_assoc();
        if (!$d) {
            return;
        }

        $monto = avisos_dinero($d['monto_reembolso']);

        notificar_multicanal(
            (int) $d['id_usuario'],
            'devolucion_recibida',
            "Recibimos tu pedido de devolución #{$id_devolucion}",
            "Registramos tu pedido de devolución del pedido #{$d['rela_id_pedidos']} por {$monto}. Lo revisamos y te avisamos.",
            avisos_canales(['app', 'email']),
            'carrito/mi_pedido.php?id=' . $d['rela_id_pedidos']
        );

        notificar_staff(
            'devolucion_nueva',
            "Nueva devolución #{$id_devolucion}",
            trim($d['nombre'] . ' ' . $d['apellido']) . " pidió devolver productos del pedido #{$d['rela_id_pedidos']} ({$monto}).",
            'views/devoluciones/verDevolucion.php?id=' . $id_devolucion
        );
    } catch (Throwable $e) {
        error_log('avisar_devolucion_solicitada: ' . $e->getMessage());
    }
}

function avisar_devolucion_resuelta(mysqli $cx, int $id_devolucion): void
{
    try {
        avisos_cargar();

        $st = $cx->prepare(
            "SELECT d.estado, d.rela_id_pedidos, d.monto_reembolso, d.nota_admin, c.rela_id_usuario AS id_usuario
             FROM devolucion d
             INNER JOIN pedidos p ON p.id_pedidos = d.rela_id_pedidos
             INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
             WHERE d.id_devolucion = ?"
        );
        $st->bind_param("i", $id_devolucion);
        $st->execute();
        $d = $st->get_result()->fetch_assoc();
        if (!$d) {
            return;
        }

        if ($d['estado'] === 'Aprobada') {
            $titulo = "Devolución #{$id_devolucion} aprobada";
            $msg    = "Aprobamos tu devolución del pedido #{$d['rela_id_pedidos']}. Te corresponde un reembolso de "
                    . avisos_dinero($d['monto_reembolso']) . '.';
            $tipo   = 'devolucion_aprobada';
        } else {
            $titulo = "Devolución #{$id_devolucion} rechazada";
            $msg    = "No pudimos aprobar tu devolución del pedido #{$d['rela_id_pedidos']}.";
            $tipo   = 'devolucion_rechazada';
        }
        if (!empty($d['nota_admin'])) {
            $msg .= ' ' . $d['nota_admin'];
        }

        notificar_multicanal(
            (int) $d['id_usuario'],
            $tipo,
            $titulo,
            $msg,
            avisos_canales(['app', 'email']),
            'carrito/mi_pedido.php?id=' . $d['rela_id_pedidos']
        );
    } catch (Throwable $e) {
        error_log('avisar_devolucion_resuelta: ' . $e->getMessage());
    }
}