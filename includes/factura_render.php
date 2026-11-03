<?php
// Dibuja una factura (la usan la pantalla del administrador y la del cliente).
// $f = resultado de Pedido::obtenerFactura()
function renderFactura(array $f): void
{
    $e     = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $plata = fn($v) => '$' . number_format((float) $v, 2, ',', '.');

    $subtotal = array_sum(array_map(fn($x) => (float) $x['subtotal_final'], $f['items']));
    $base     = array_sum(array_map(fn($x) => (float) ($x['subtotal_base'] ?? $x['subtotal_final']), $f['items']));
    $descuento = max(0, round($base - $subtotal, 2));
    $promoNombre = '';
    foreach ($f['items'] as $x) {
        if (in_array($x['promo_tipo'] ?? '', ['Porcentaje', 'Fijo'], true)) { $promoNombre = $x['promo_nombre']; }
    }
    $cuponDesc = (float) ($f['cupon_descuento'] ?? 0);
    $promoDesc = max(0, round($descuento - $cuponDesc, 2));
    $envio    = round((float) $f['total_pedido'] - $subtotal, 2);
    $direccion = trim(implode(', ', array_filter([
        trim(($f['calle'] ?? '') . ' ' . ($f['numero_exterior'] ?? '')),
        $f['barrio_colonia'] ?? '', $f['nombre_localidad'] ?? '', $f['nombre_provincia'] ?? ''])));
    $emisorDom    = (function_exists('env_valor') ? env_valor('EMPRESA_DOMICILIO', '') : '') ?: 'Domicilio de la empresa';
    $emisorFiscal = (function_exists('env_valor') ? env_valor('EMPRESA_CUIT', '') : '') ?: 'CUIT de la empresa';
    $esA = ($f['tipo_factura'] === 'A');
    $difiere = abs((float) $f['monto_total'] - (float) $f['total_pedido']) > 0.01;
    ?>
<style>
    .factura { background: var(--cc-superficie); border: 1px solid var(--cc-borde); border-radius: 12px; padding: 32px; color: var(--cc-texto); max-width: 860px; margin: 0 auto; }
    .fact-cab { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; flex-wrap: wrap; padding-bottom: 14px; border-bottom: 2px solid var(--cc-primario); }
    .fact-emisor p { margin: 2px 0; color: var(--cc-texto-secundario); }
    .fact-emisor strong { font-size: 20px; color: var(--cc-texto); }
    .fact-titulo { text-align: right; }
    .fact-titulo h2 { margin: 0; font-size: 40px; letter-spacing: 1px; color: var(--cc-primario); }
    .fact-titulo span { display: inline-block; margin-top: 4px; font-weight: 700; border: 2px solid var(--cc-primario); color: var(--cc-primario); border-radius: 6px; padding: 0 10px; }
    .fact-datos { display: flex; justify-content: space-between; gap: 30px; flex-wrap: wrap; margin-top: 18px; }
    .fact-datos table { border-collapse: collapse; width: auto; background: none; }
    .fact-datos th, .fact-datos td { background: none !important; border: none !important; }
    .fact-datos th { color: var(--cc-primario); text-align: left; padding: 3px 18px 3px 0; text-transform: uppercase; font-size: 13px; letter-spacing: .4px; white-space: nowrap; }
    .fact-datos td { padding: 3px 0; color: var(--cc-texto); }
    .fact-tabla { width: 100%; border-collapse: collapse; margin-top: 22px; }
    .fact-tabla th { background: rgba(0,234,255,.14); color: var(--cc-primario); padding: 9px 10px; text-transform: uppercase; font-size: 13px; letter-spacing: .4px; text-align: center; }
    .fact-tabla td { padding: 9px 10px; border-bottom: 1px solid var(--cc-borde); }
    .fact-tabla .num { text-align: right; white-space: nowrap; }
    .fact-tabla .cen { text-align: center; }
    .fact-tabla .vacia td { height: 14px; }
    .fact-tot { margin-left: auto; width: 360px; max-width: 100%; margin-top: 18px; border-collapse: collapse; }
    .fact-tot td { padding: 7px 12px; }
    .fact-tot td:first-child { text-align: right; color: var(--cc-primario); font-weight: 700; text-transform: uppercase; font-size: 13px; }
    .fact-tot td:last-child { text-align: right; background: rgba(0,234,255,.14); font-weight: 700; white-space: nowrap; }
    .fact-tot .gran td { font-size: 17px; padding: 10px 12px; }
    .fact-pie { margin-top: 18px; font-size: 12px; color: var(--cc-texto-secundario); }
    @media print {
        body { background: #fff !important; }
        .cc-header-admin, .tienda-header, #cc-theme-toggle, .no-imprimir { display: none !important; }
        .tienda-container, .container { margin: 0 auto !important; padding: 0 !important; box-shadow: none !important; border: none !important; background: #fff !important; }
        .factura { border: none; background: #fff; padding: 0; }
        .factura * { color: #000 !important; border-color: #999 !important; text-shadow: none !important; }
        .fact-tabla th, .fact-tot td:last-child { background: #e8eef8 !important; }
    }
</style>

<div class="factura">
    <div class="fact-cab">
        <div class="fact-emisor">
            <p><strong>CYBERCORE</strong></p>
            <p><?= $e($emisorDom) ?></p>
            <p><?= $e($emisorFiscal) ?></p>
        </div>
        <div class="fact-titulo">
            <h2>FACTURA</h2>
            <span title="Tipo de factura">Tipo <?= $e($f['tipo_factura']) ?></span>
        </div>
    </div>

    <div class="fact-datos">
        <table>
            <tr><th>Cliente</th><td><?= $e($f['nombre'] . ' ' . $f['apellido']) ?></td></tr>
            <tr><th>Domicilio</th><td><?= $direccion ? $e($direccion) : '-' ?></td></tr>
            <tr><th>CUIL/CUIT</th><td><?= $e($f['cuil_cuit'] ?: '-') ?></td></tr>
            <tr><th>Cond. IVA</th><td><?= $e($f['condicion_iva'] ?: 'Consumidor final') ?></td></tr>
        </table>
        <table>
            <tr><th>Nº Factura</th><td><?= $e($f['numero_factura']) ?></td></tr>
            <tr><th>Fecha</th><td><?= $f['fecha_emicion'] ? $e(date('d/m/Y', strtotime($f['fecha_emicion']))) : '-' ?></td></tr>
            <tr><th>Pedido</th><td>#<?= (int) $f['rela_id_pedidos'] ?></td></tr>
            <?php if (!empty($f['metodo_pago'])): ?>
            <tr><th>Pago</th><td><?= $e($f['metodo_pago']) ?></td></tr>
            <?php endif; ?>
        </table>
    </div>

    <table class="fact-tabla">
        <tr><th>Código</th><th>Artículo</th><th>Cantidad</th><th>Precio unitario</th><th>Total</th></tr>
        <?php foreach ($f['items'] as $it): ?>
        <tr>
            <td class="cen"><?= $e($it['codigo'] ?? '') ?></td>
            <td><?= $e($it['nombre']) ?></td>
            <td class="cen"><?= (int) $it['cantidad'] ?></td>
            <td class="num"><?= $plata($it['precio_unitario_base']) ?></td>
            <td class="num"><?= $plata($it['subtotal_base'] ?? $it['subtotal_final']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php for ($i = count($f['items']); $i < 4; $i++): ?>
        <tr class="vacia"><td></td><td></td><td></td><td></td><td></td></tr>
        <?php endfor; ?>
    </table>

    <table class="fact-tot">
        <?php if ($descuento > 0): ?>
        <tr><td>Subtotal productos</td><td><?= $plata($base) ?></td></tr>
        <?php if ($promoDesc > 0): ?>
        <tr><td>Descuento<?= $promoNombre ? ' (' . $e($promoNombre) . ')' : '' ?></td><td>− <?= $plata($promoDesc) ?></td></tr>
        <?php endif; ?>
        <?php if ($cuponDesc > 0): ?>
        <tr><td>Cupón <?= $e($f['cupon_codigo']) ?></td><td>− <?= $plata($cuponDesc) ?></td></tr>
        <?php endif; ?>
        <?php endif; ?>
        <?php if ($envio > 0): ?>
        <tr><td>Envío<?= !empty($f['empresa_transporte']) ? ' (' . $e($f['empresa_transporte']) . ')' : '' ?></td><td><?= $plata($envio) ?></td></tr>
        <?php endif; ?>
        <?php if ($esA): ?>
        <tr><td>Subtotal (neto)</td><td><?= $plata($f['monto_total_bruto']) ?></td></tr>
        <tr><td>IVA 21%</td><td><?= $plata($f['monto_total_impuesto']) ?></td></tr>
        <tr class="gran"><td>Total factura</td><td><?= $plata($f['monto_total']) ?></td></tr>
        <?php else: ?>
        <tr><td>IVA incluido 21%</td><td><?= $plata($f['monto_total_impuesto']) ?></td></tr>
        <tr class="gran"><td>Total factura</td><td><?= $plata($f['monto_total']) ?></td></tr>
        <?php endif; ?>
    </table>

    <?php if ($difiere): ?>
        <p class="fact-pie">Nota: el total de esta factura (<?= $plata($f['monto_total']) ?>) difiere del total actual del pedido (<?= $plata($f['total_pedido']) ?>). Se muestra el importe registrado al emitirla.</p>
    <?php endif; ?>
    <p class="fact-pie">Comprobante interno de CyberCore, sin validez fiscal.</p>
</div>
<?php
}