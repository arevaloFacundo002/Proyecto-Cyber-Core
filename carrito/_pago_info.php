<?php

function tipoMetodoPago(string $nombre): string
{
    $n = mb_strtolower($nombre, 'UTF-8');
    if (strpos($n, 'transferencia') !== false) return 'transferencia';
    if (strpos($n, 'efectivo') !== false) return 'efectivo';
    return 'pasarela';
}

function cuentaBancariaHtml(?array $cuenta): string
{
    $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

    if (!$cuenta) {
        return '<p class="texto-suave">Todavía no hay una cuenta cargada. Consultá con el negocio.</p>';
    }

    $filas = [
        'Titular' => $cuenta['titular'],
        'Banco'   => $cuenta['banco'],
        'CBU'     => $cuenta['cbu'],
        'Alias'   => $cuenta['alias'] ?? '',
    ];

    $html = '<table class="cuenta-banco">';
    foreach ($filas as $etq => $val) {
        if ($val === '' || $val === null) continue;
        $copiable = in_array($etq, ['CBU', 'Alias'], true);
        $html .= '<tr><td>' . $e($etq) . '</td><td><strong>' . $e($val) . '</strong>';
        if ($copiable) {
            $html .= ' <button type="button" class="btn-copiar" onclick="copiarDato(this, \'' . $e($val) . '\')">Copiar</button>';
        }
        $html .= '</td></tr>';
    }
    return $html . '</table>';
}

function pagoInfoEstilosYScript(): string
{
    static $ya = false;
    if ($ya) return '';
    $ya = true;

    return <<<'HTML'
<style>
    .cuenta-banco { border-collapse: collapse; margin: 8px 0; }
    .cuenta-banco td { padding: 4px 14px 4px 0; vertical-align: middle; }
    .cuenta-banco td:first-child { color: var(--cc-texto-secundario); font-size: 13px; }
    .btn-copiar { margin-left: 6px; padding: 2px 10px; font-size: 12px; border-radius: 6px; cursor: pointer;
        border: 1px solid var(--cc-primario); background: transparent; color: var(--cc-primario); }
    .info-pago { margin: 12px 0; padding: 12px 14px; border: 1px dashed var(--cc-borde); border-radius: 10px; }
    .info-pago p { margin: 4px 0; }
</style>
<script>
function copiarDato(btn, texto) {
    var listo = function () { var t = btn.textContent; btn.textContent = '¡Copiado!'; setTimeout(function () { btn.textContent = t; }, 1500); };
    if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(texto).then(listo); return; }
    var a = document.createElement('textarea'); a.value = texto; document.body.appendChild(a); a.select();
    try { document.execCommand('copy'); listo(); } catch (e) {} document.body.removeChild(a);
}
</script>
HTML;
}

function formComprobanteHtml(int $id_pedido, string $csrf, bool $reemplazo): string
{
    $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

    return '<form action="subir_comprobante.php" method="POST" enctype="multipart/form-data" style="margin-top:12px;">'
         . '<input type="hidden" name="csrf" value="' . $e($csrf) . '">'
         . '<input type="hidden" name="id_pedido" value="' . $id_pedido . '">'
         . '<p><input type="file" name="comprobante" accept="image/jpeg,image/png,image/webp,application/pdf" required></p>'
         . '<p class="texto-suave">Imagen (JPG, PNG, WEBP) o PDF, de hasta 5 MB.</p>'
         . '<button type="submit" class="btn-principal">' . ($reemplazo ? 'Subir otro comprobante' : 'Subir comprobante') . '</button>'
         . '</form>';
}