<?php

function factura_email_html(array $f, string $saludo = ''): string
{
    $e     = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $plata = fn($v) => '$' . number_format((float) $v, 2, ',', '.');

    $subtotal = array_sum(array_map(fn($x) => (float) $x['subtotal_final'], $f['items']));
    $base     = array_sum(array_map(fn($x) => (float) ($x['subtotal_base'] ?? $x['subtotal_final']), $f['items']));
    $descuento = max(0, round($base - $subtotal, 2));
    $cuponDesc = (float) ($f['cupon_descuento'] ?? 0);
    $promoDesc = max(0, round($descuento - $cuponDesc, 2));
    $promoNombre = '';
    foreach ($f['items'] as $x) {
        if (in_array($x['promo_tipo'] ?? '', ['Porcentaje', 'Fijo'], true)) { $promoNombre = $x['promo_nombre']; }
    }
    $envio = round((float) $f['total_pedido'] - $subtotal, 2);

    $direccion = trim(implode(', ', array_filter([
        trim(($f['calle'] ?? '') . ' ' . ($f['numero_exterior'] ?? '')),
        $f['barrio_colonia'] ?? '', $f['nombre_localidad'] ?? '', $f['nombre_provincia'] ?? ''])));

    $emisorDom    = (function_exists('env_valor') ? env_valor('EMPRESA_DOMICILIO', '') : '') ?: 'Domicilio de la empresa';
    $emisorFiscal = (function_exists('env_valor') ? env_valor('EMPRESA_CUIT', '') : '') ?: 'CUIT de la empresa';
    $esA = ($f['tipo_factura'] === 'A');

    if ($f['tipo_factura'] === 'T') {
        $lin = function (string $a, string $b, bool $fuerte = false) {
            $est = $fuerte ? 'font-size:17px;font-weight:bold;border-top:1px dashed #b9c2cf;padding-top:8px;' : '';
            return '<tr><td style="padding:3px 0;' . $est . '">' . $a . '</td><td style="padding:3px 0;text-align:right;white-space:nowrap;' . $est . '">' . $b . '</td></tr>';
        };

        $h  = '<div style="background:#f4f6f9;padding:20px;font-family:Segoe UI,Arial,sans-serif;color:#1b2330;">';
        if ($saludo !== '') {
            $h .= '<p style="max-width:380px;margin:0 auto 14px;font-size:15px;">' . $e($saludo) . '</p>';
        }
        $h .= '<div style="max-width:380px;margin:0 auto;background:#ffffff;border:1px dashed #b9c2cf;border-radius:8px;padding:24px;font-family:Courier New,Consolas,monospace;font-size:14px;">';
        $h .= '<div style="text-align:center;"><div style="font-size:21px;font-weight:bold;letter-spacing:2px;">CYBERCORE</div>'
            . '<div style="color:#5a6577;font-size:12px;">' . $e($emisorDom) . '</div></div>';
        $h .= '<hr style="border:none;border-top:1px dashed #b9c2cf;margin:12px 0;">';
        $h .= '<div style="text-align:center;"><strong>TICKET ' . $e($f['numero_factura']) . '</strong><br>'
            . '<span style="color:#5a6577;font-size:12px;">' . ($f['fecha_emicion'] ? $e(date('d/m/Y H:i', strtotime($f['fecha_emicion']))) : '-') . ' · Pedido #' . (int) $f['rela_id_pedidos'] . '</span></div>';
        $h .= '<hr style="border:none;border-top:1px dashed #b9c2cf;margin:12px 0;"><table width="100%" cellspacing="0" cellpadding="0">';
        foreach ($f['items'] as $it) {
            $h .= $lin($e($it['nombre']), $plata($it['subtotal_base'] ?? $it['subtotal_final']));
            $h .= '<tr><td colspan="2" style="color:#5a6577;font-size:12px;padding:0 0 6px;">' . (int) $it['cantidad'] . ' x ' . $plata($it['precio_unitario_base']) . '</td></tr>';
        }
        $h .= '</table><hr style="border:none;border-top:1px dashed #b9c2cf;margin:12px 0;"><table width="100%" cellspacing="0" cellpadding="0">';
        if ($promoDesc > 0) {
            $h .= $lin('Descuento' . ($promoNombre ? ' (' . $e($promoNombre) . ')' : ''), '− ' . $plata($promoDesc));
        }
        if ($cuponDesc > 0) {
            $h .= $lin('Cupón ' . $e($f['cupon_codigo'] ?? ''), '− ' . $plata($cuponDesc));
        }
        if ($envio > 0) {
            $h .= $lin('Envío' . (!empty($f['empresa_transporte']) ? ' (' . $e($f['empresa_transporte']) . ')' : ''), $plata($envio));
        }
        $h .= $lin('TOTAL', $plata($f['monto_total']), true) . '</table>';
        if (!empty($f['metodo_pago'])) {
            $h .= '<p style="color:#5a6577;font-size:12px;margin:10px 0 0;">Pago: ' . $e($f['metodo_pago']) . '</p>';
        }
        $h .= '<hr style="border:none;border-top:1px dashed #b9c2cf;margin:12px 0;">'
            . '<p style="text-align:center;color:#5a6577;font-size:12px;margin:0;">¡Gracias por tu compra!<br>Guardá este correo como respaldo. Comprobante interno, sin validez fiscal.</p>';
        $h .= '</div></div>';

        return $h;
    }

    $celda  = 'padding:8px 10px;border-bottom:1px solid #e3e8ef;';
    $filas  = '';
    foreach ($f['items'] as $it) {
        $filas .= '<tr>'
            . '<td style="' . $celda . 'text-align:center;">' . $e($it['codigo'] ?? '') . '</td>'
            . '<td style="' . $celda . '">' . $e($it['nombre']) . '</td>'
            . '<td style="' . $celda . 'text-align:center;">' . (int) $it['cantidad'] . '</td>'
            . '<td style="' . $celda . 'text-align:right;">' . $plata($it['precio_unitario_base']) . '</td>'
            . '<td style="' . $celda . 'text-align:right;">' . $plata($it['subtotal_base'] ?? $it['subtotal_final']) . '</td>'
            . '</tr>';
    }

    $tot = function (string $etq, string $valor, bool $fuerte = false) {
        $est = $fuerte ? 'font-size:16px;font-weight:bold;' : '';
        return '<tr><td style="padding:6px 10px;text-align:right;color:#0a6e86;font-size:12px;font-weight:bold;text-transform:uppercase;' . $est . '">' . $etq . '</td>'
             . '<td style="padding:6px 10px;text-align:right;background:#e8f6fa;font-weight:bold;white-space:nowrap;' . $est . '">' . $valor . '</td></tr>';
    };

    $totales = '';
    if ($descuento > 0) {
        $totales .= $tot('Subtotal productos', $plata($base));
        if ($promoDesc > 0) {
            $totales .= $tot('Descuento' . ($promoNombre ? ' (' . $e($promoNombre) . ')' : ''), '− ' . $plata($promoDesc));
        }
        if ($cuponDesc > 0) {
            $totales .= $tot('Cupón ' . $e($f['cupon_codigo'] ?? ''), '− ' . $plata($cuponDesc));
        }
    }
    if ($envio > 0) {
        $totales .= $tot('Envío' . (!empty($f['empresa_transporte']) ? ' (' . $e($f['empresa_transporte']) . ')' : ''), $plata($envio));
    }
    if ($esA) {
        $totales .= $tot('Subtotal (neto)', $plata($f['monto_total_bruto']));
        $totales .= $tot('IVA 21%', $plata($f['monto_total_impuesto']));
        $totales .= $tot('Total factura', $plata($f['monto_total']), true);
    } else {
        $totales .= $tot('IVA incluido 21%', $plata($f['monto_total_impuesto']));
        $totales .= $tot('Total factura', $plata($f['monto_total']), true);
    }

    $datoFila = fn($k, $v) => '<tr><td style="padding:2px 14px 2px 0;color:#0a6e86;font-size:12px;font-weight:bold;text-transform:uppercase;white-space:nowrap;">' . $k . '</td><td style="padding:2px 0;">' . $v . '</td></tr>';

    $html  = '<div style="background:#f4f6f9;padding:20px;font-family:Segoe UI,Arial,sans-serif;color:#1b2330;">';
    $html .= '<div style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #dde3ec;border-radius:10px;padding:26px;">';
    if ($saludo !== '') {
        $html .= '<p style="margin:0 0 18px;font-size:15px;">' . $e($saludo) . '</p>';
    }
    $html .= '<table width="100%" cellspacing="0" cellpadding="0" style="border-bottom:2px solid #0a6e86;padding-bottom:12px;"><tr>'
          . '<td valign="top"><div style="font-size:20px;font-weight:bold;">CYBERCORE</div>'
          . '<div style="color:#5a6577;font-size:13px;">' . $e($emisorDom) . '<br>' . $e($emisorFiscal) . '</div></td>'
          . '<td valign="top" style="text-align:right;"><div style="font-size:30px;font-weight:bold;color:#0a6e86;">FACTURA</div>'
          . '<div style="display:inline-block;border:2px solid #0a6e86;color:#0a6e86;border-radius:6px;padding:0 10px;font-weight:bold;">Tipo ' . $e($f['tipo_factura']) . '</div></td>'
          . '</tr></table>';

    $html .= '<table width="100%" cellspacing="0" cellpadding="0" style="margin-top:16px;font-size:14px;"><tr><td valign="top"><table cellspacing="0" cellpadding="0">'
          . $datoFila('Cliente', $e($f['nombre'] . ' ' . $f['apellido']))
          . $datoFila('Domicilio', $direccion !== '' ? $e($direccion) : '-')
          . $datoFila('CUIL/CUIT', $e($f['cuil_cuit'] ?: '-'))
          . $datoFila('Cond. IVA', $e($f['condicion_iva'] ?: 'Consumidor final'))
          . '</table></td><td valign="top" style="text-align:right;"><table cellspacing="0" cellpadding="0" align="right">'
          . $datoFila('Nº Factura', $e($f['numero_factura']))
          . $datoFila('Fecha', $f['fecha_emicion'] ? $e(date('d/m/Y', strtotime($f['fecha_emicion']))) : '-')
          . $datoFila('Pedido', '#' . (int) $f['rela_id_pedidos'])
          . (!empty($f['metodo_pago']) ? $datoFila('Pago', $e($f['metodo_pago'])) : '')
          . '</table></td></tr></table>';

    $th = 'background:#e8f6fa;color:#0a6e86;padding:9px 10px;font-size:12px;text-transform:uppercase;text-align:center;';
    $html .= '<table width="100%" cellspacing="0" cellpadding="0" style="margin-top:20px;font-size:14px;border-collapse:collapse;">'
          . '<tr><th style="' . $th . '">Código</th><th style="' . $th . '">Artículo</th><th style="' . $th . '">Cant.</th><th style="' . $th . '">Precio unit.</th><th style="' . $th . '">Total</th></tr>'
          . $filas . '</table>';

    $html .= '<table cellspacing="0" cellpadding="0" align="right" style="margin-top:16px;font-size:14px;min-width:300px;">' . $totales . '</table>';
    $html .= '<div style="clear:both;"></div>';
    $html .= '<p style="margin:26px 0 0;font-size:12px;color:#7a8596;border-top:1px solid #e3e8ef;padding-top:12px;">'
          . 'Guardá este correo como respaldo de tu compra. Comprobante interno de CyberCore, sin validez fiscal. '
          . 'También podés verlo e imprimirlo desde Mis pedidos.</p>';
    $html .= '</div></div>';

    return $html;
}

// Arma el HTML de la factura de un pedido (o null si el pedido todavía no tiene factura).
function factura_email_de_pedido(int $id_pedido, string $saludo = ''): ?string
{
    require_once __DIR__ . '/../models/Pedido.php';

    $db = new DataBase();
    $cx = $db->getConexion();

    $st = $cx->prepare("SELECT id_facturas FROM facturas WHERE rela_id_pedidos = ? LIMIT 1");
    $st->bind_param("i", $id_pedido);
    $st->execute();
    $fila = $st->get_result()->fetch_assoc();
    if (!$fila) {
        return null;
    }

    $f = (new Pedido())->obtenerFactura((int) $fila['id_facturas']);
    return $f ? factura_email_html($f, $saludo) : null;
}