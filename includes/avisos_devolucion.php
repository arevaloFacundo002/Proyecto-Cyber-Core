<?php
// Avisos y datos de contacto del circuito de devoluciones (por la app y por correo).

require_once __DIR__ . '/avisos.php';
require_once __DIR__ . '/../config/env.php';

// Datos del negocio que ve el cliente para entregar el producto. Se configuran en el .env.
function devolucion_datos_negocio(): array
{
    return [
        'domicilio' => env_valor('EMPRESA_DOMICILIO', 'Consultá la dirección escribiéndonos'),
        'horario'   => env_valor('EMPRESA_HORARIO', 'Lunes a viernes de 9 a 18 hs'),
        'telefono'  => env_valor('EMPRESA_TELEFONO', ''),
        'whatsapp'  => env_valor('EMPRESA_WHATSAPP', ''),
        'email'     => env_valor('EMPRESA_EMAIL_DEVOLUCIONES', env_valor('MAIL_FROM', '')),
    ];
}

function devolucion_texto_contacto(): string
{
    $n = devolucion_datos_negocio();
    $t = "Dirección: {$n['domicilio']}. Horario: {$n['horario']}.";
    if ($n['telefono'] !== '') { $t .= " Teléfono: {$n['telefono']}."; }
    if ($n['whatsapp'] !== '') { $t .= " WhatsApp: {$n['whatsapp']}."; }
    if ($n['email'] !== '')    { $t .= " Correo: {$n['email']}."; }
    return $t;
}

function devolucion_datos_aviso(mysqli $cx, int $id_devolucion): ?array
{
    $st = $cx->prepare(
        "SELECT d.id_devolucion, d.estado, d.rela_id_pedidos, d.monto_reembolso, d.nota_admin, d.nota_recepcion,
                d.metodo_entrega, d.empresa_transporte, d.num_seguimiento, d.vuelve_stock,
                c.nombre, c.apellido, c.rela_id_usuario AS id_usuario
         FROM devolucion d
         INNER JOIN pedidos p ON p.id_pedidos = d.rela_id_pedidos
         INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
         WHERE d.id_devolucion = ?"
    );
    $st->bind_param("i", $id_devolucion);
    $st->execute();
    return $st->get_result()->fetch_assoc() ?: null;
}

// Al aprobar: le explicamos al cliente a donde y como mandar el producto
function avisar_devolucion_aprobada(mysqli $cx, int $id_devolucion): void
{
    try {
        avisos_cargar();
        $d = devolucion_datos_aviso($cx, $id_devolucion);
        if (!$d) { return; }

        $msg = "Aprobamos tu devolución del pedido #{$d['rela_id_pedidos']}. Te corresponde un reembolso de "
             . avisos_dinero($d['monto_reembolso']) . ". "
             . "Ahora tenés que hacernos llegar el producto con su caja y accesorios, indicando el N° de devolución #{$id_devolucion}. "
             . "Podés enviarlo por correo o transporte, o traerlo al local. " . devolucion_texto_contacto()
             . " Cuando lo despaches, avisanos desde el detalle de tu pedido.";
        if (!empty($d['nota_admin'])) { $msg .= ' ' . $d['nota_admin']; }

        notificar_multicanal(
            (int) $d['id_usuario'], 'devolucion_aprobada', "Devolución #{$id_devolucion} aprobada: mandanos el producto",
            $msg, avisos_canales(['app', 'email']), 'carrito/mi_pedido.php?id=' . $d['rela_id_pedidos']
        );
    } catch (Throwable $e) {
        error_log('avisar_devolucion_aprobada: ' . $e->getMessage());
    }
}

// Cuando el cliente avisa que despacho el producto: se entera el personal y el cliente recibe la constancia
function avisar_devolucion_enviada(mysqli $cx, int $id_devolucion): void
{
    try {
        avisos_cargar();
        $d = devolucion_datos_aviso($cx, $id_devolucion);
        if (!$d) { return; }

        $cliente = trim($d['nombre'] . ' ' . $d['apellido']);
        if ($d['metodo_entrega'] === 'local') {
            $detalle = 'lo va a traer al local';
        } else {
            $detalle = 'lo despachó por ' . $d['empresa_transporte'] . ' (seguimiento ' . $d['num_seguimiento'] . ')';
        }

        notificar_staff(
            'devolucion_enviada',
            "Devolución #{$id_devolucion}: producto en camino",
            "{$cliente} avisó que {$detalle}. Pedido #{$d['rela_id_pedidos']}.",
            'views/devoluciones/verDevolucion.php?id=' . $id_devolucion
        );

        notificar_multicanal(
            (int) $d['id_usuario'], 'devolucion_enviada', "Registramos tu aviso de envío (devolución #{$id_devolucion})",
            "Anotamos que {$detalle}. Cuando recibamos el producto te avisamos.",
            avisos_canales(['app', 'email']), 'carrito/mi_pedido.php?id=' . $d['rela_id_pedidos']
        );
    } catch (Throwable $e) {
        error_log('avisar_devolucion_enviada: ' . $e->getMessage());
    }
}

// Cuando el negocio recibe y revisa el producto
function avisar_devolucion_recibida(mysqli $cx, int $id_devolucion): void
{
    try {
        avisos_cargar();
        $d = devolucion_datos_aviso($cx, $id_devolucion);
        if (!$d) { return; }

        $msg = "Recibimos y revisamos el producto de tu devolución #{$id_devolucion} (pedido #{$d['rela_id_pedidos']}). "
             . "Te corresponde un reembolso de " . avisos_dinero($d['monto_reembolso']) . ".";
        if (!empty($d['nota_recepcion'])) { $msg .= ' ' . $d['nota_recepcion']; }

        notificar_multicanal(
            (int) $d['id_usuario'], 'devolucion_recibida_ok', "Devolución #{$id_devolucion}: recibimos tu producto",
            $msg, avisos_canales(['app', 'email']), 'carrito/mi_pedido.php?id=' . $d['rela_id_pedidos']
        );
    } catch (Throwable $e) {
        error_log('avisar_devolucion_recibida: ' . $e->getMessage());
    }
}