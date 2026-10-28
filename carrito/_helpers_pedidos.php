<?php
// Funciones compartidas por mis_pedidos.php y mi_pedido.php
if (!function_exists('e')) {
    function e($valor) { return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('plata')) {
    function plata($valor) { return '$' . number_format((float) $valor, 2, ',', '.'); }
}
if (!function_exists('fechaHora')) {
    function fechaHora($f) { return $f ? date('d/m/Y H:i', strtotime($f)) : ''; }
}

// Dibuja la barra de progreso del pedido
function barraProgreso(Pedido $modelo, string $estado): void
{
    if ($estado === 'Cancelado') {
        echo '<div class="mp-cancelado">🔴 Este pedido fue cancelado.</div>';
        return;
    }
    $pasos  = $modelo->secuenciaEstados();
    $actual = $modelo->posicionEstado($estado);

    echo '<div class="mp-prog">';
    foreach ($pasos as $i => $nombre) {
        $clase = $i < $actual ? 'hecho' : ($i === $actual ? 'hecho actual' : '');
        echo '<div class="mp-paso ' . $clase . '"><div class="mp-punto"></div>' . e($nombre) . '</div>';
    }
    echo '</div>';
}

// Etiqueta del estado del pago para el cliente
function etiquetaPago(?string $estado): string
{
    if ($estado === 'Aprobado') return '<span class="mp-pago ok">💳 Pago aprobado</span>';
    if ($estado === null || $estado === '') return '';
    return '<span class="mp-pago pend">⏳ Pago ' . e(strtolower($estado)) . '</span>';
}