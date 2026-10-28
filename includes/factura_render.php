<?php
// Dibuja una factura (la usan la pantalla del administrador y la del cliente).
// $f = resultado de Pedido::obtenerFactura()
function renderFactura(array $f): void
{
    $e     = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $plata = fn($v) => '$' . number_format((float) $v, 2, ',', '.');

    $subtotal = array_sum(array_map(fn($x) => (float) $x['subtotal_final'], $f['items']));
    $envio    = round((float) $f['total_pedido'] - $subtotal, 2);
    $direccion = trim(implode(', ', array_filter([
        trim(($f['calle'] ?? '') . ' ' . ($f['numero_exterior'] ?? '')),
        $f['barrio_colonia'] ?? '', $f['nombre_localidad'] ?? '', $f['nombre_provincia'] ?? ''])));
    $esA = ($f['tipo_factura'] === 'A');
    $difiere = abs((float) $f['monto_total'] - (float) $f['total_pedido']) > 0.01;
    ?>
<style>
    .factura { background: var(--cc-superficie); border: 1px solid var(--cc-borde); border-radius: 12px; padding: 28px; color: var(--cc-texto); }
    .fact-head { display: flex; justify-content: space-between; gap: 20px; flex-wrap: wrap; border-bottom: 1px solid var(--cc-borde); padding-bottom: 16px; }
    .fact-head h2 { margin: 0; color: var(--cc-primario); }
    .fact-tipo { font-size: 42px; font-weight: 800; border: 2px solid var(--cc-primario); color: var(--cc-primario);
        width: 70px; height: 70px; display: flex; align-items: center; justify-content: center; border-radius: 10px; }
    .fact-bloque { margin-top: 18px; }
    .fact-bloque h3 { margin: 0 0 6px; font-size: 14px; color: var(--cc-primario); text-transform: uppercase; letter-spacing: .5px; }
    .fact-bloque p { margin: 2px 0; }
    .fact-tabla { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .fact-tabla th { background: rgba(0,234,255,.12); color: var(--cc-primario); padding: 8px 10px; text-align: left; border-bottom: 1px solid var(--cc-borde); }
    .fact-tabla td { padding: 8px 10px; border-bottom: 1px solid var(--cc-borde); }
    .fact-tabla .num { text-align: right; }
    .fact-totales { margin-left: auto; width: 340px; max-width: 100%; margin-top: 16px; }
    .fact-totales div { display: flex; justify-content: space-between; padding: 5px 0; color: var(--cc-texto-secundario); }
    .fact-totales .gran { color: var(--cc-texto); font-size: 20px; font-weight: 800; border-top: 2px solid var(--cc-primario); margin-top: 6px; padding-top: 10px; }
    .fact-nota { font-size: 12px; color: var(--cc-texto-secundario); margin-top: 12px; }
    @media print {
        body { background: #fff !important; }
        .cc-header-admin, .tienda-header, #cc-theme-toggle, .no-imprimir { display: none !important; }
        .tienda-container, .container { margin: 0 auto !important; padding: 0 !important; box-shadow: none !important; border: none !important; background: #fff !important; }
        .factura { border: none; background: #fff; }
        .factura * { color: #000 !important; border-color: #999 !important; text-shadow: none !important; }
        .fact-tabla th { background: #eee !important; }
    }
</style>

<div class="factura">
    <div class="fact-head">
        <div>
            <h2>CYBERCORE</h2>
            <p class="fact-nota" style="margin:4px 0 0;">Factura <?= $e($f['numero_factura']) ?><br>
               Fecha de emisión: <?= $f['fecha_emicion'] ? $e(date('d/m/Y H:i', strtotime($f['fecha_emicion']))) : '-' ?></p>
        </div>
        <div class="fact-tipo" title="Tipo de factura"><?= $e($f['tipo_factura']) ?></div>
    </div>

    <div class="fact-bloque">
        <h3>Cliente</h3>
        <p><strong><?= $e($f['nombre'] . ' ' . $f['apellido']) ?></strong></p>
        <p>CUIL/CUIT: <?= $e($f['cuil_cuit']) ?> · Condición frente al IVA: <?= $e($f['condicion_iva'] ?: 'Consumidor final') ?></p>
        <?php if ($direccion): ?><p>Domicilio: <?= $e($direccion) ?></p><?php endif; ?>
    </div>

    <div class="fact-bloque">
        <h3>Pedido #<?= (int) $f['rela_id_pedidos'] ?> · <?= $e(date('d/m/Y', strtotime($f['fecha_pedidos']))) ?></h3>
        <p>
            <?php if (!empty($f['metodo_pago'])): ?>Pago: <?= $e($f['metodo_pago']) ?> (<?= $e($f['estado_pagos']) ?>)<?php endif; ?>
            <?php if (!empty($f['direccion_entrega'])): ?><br>Entrega en: <?= $e($f['direccion_entrega']) ?><?php endif; ?>
            <?php if (!empty($f['recibe_nombre'])): ?> · Recibe: <?= $e($f['recibe_nombre']) ?><?php endif; ?>
        </p>
        <table class="fact-tabla">
            <tr><th>Producto</th><th class="num">Precio</th><th class="num">Cant.</th><th class="num">Subtotal</th></tr>
            <?php foreach ($f['items'] as $it): ?>
            <tr><td><?= $e($it['nombre']) ?></td><td class="num"><?= $plata($it['precio_unitario_base']) ?></td>
                <td class="num"><?= (int) $it['cantidad'] ?></td><td class="num"><?= $plata($it['subtotal_final']) ?></td></tr>
            <?php endforeach; ?>
            <?php if ($envio > 0): ?>
            <tr><td colspan="3" class="num">Envío<?= !empty($f['empresa_transporte']) ? ' (' . $e($f['empresa_transporte']) . ')' : '' ?></td><td class="num"><?= $plata($envio) ?></td></tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="fact-totales">
        <?php if ($esA): ?>
            <div><span>Importe neto gravado</span><span><?= $plata($f['monto_total_bruto']) ?></span></div>
            <div><span>IVA</span><span><?= $plata($f['monto_total_impuesto']) ?></span></div>
            <div class="gran"><span>TOTAL</span><span><?= $plata($f['monto_total']) ?></span></div>
        <?php else: ?>
            <div class="gran"><span>TOTAL</span><span><?= $plata($f['monto_total']) ?></span></div>
            <div><span>IVA contenido</span><span><?= $plata($f['monto_total_impuesto']) ?></span></div>
        <?php endif; ?>
    </div>

    <?php if ($difiere): ?>
        <p class="fact-nota">Nota: el total de esta factura (<?= $plata($f['monto_total']) ?>) difiere del total actual del pedido (<?= $plata($f['total_pedido']) ?>). Se muestra el importe registrado al emitirla.</p>
    <?php endif; ?>
</div>
<?php
}