<?php
$_vb_url   = $volver_url   ?? 'menu_consultas.php';
$_vb_texto = $volver_texto ?? '⬅ Volver a Consultas y Reportes';
?>
<style>
    .cc-barra-volver { background: var(--cc-superficie-2); padding: 14px 50px; display: flex; justify-content: flex-start;
        align-items: center; border-bottom: 1px solid var(--cc-borde); }
    .cc-barra-volver a { background: var(--cc-primario); padding: 9px 18px; border-radius: 20px; color: var(--cc-texto-sobre-primario);
        font-weight: bold; text-decoration: none; font-size: 14px; transition: 0.2s; }
    .cc-barra-volver a:hover { background: var(--cc-primario-hover); }
</style>
<div class="cc-barra-volver"><a href="<?= htmlspecialchars($_vb_url) ?>"><?= htmlspecialchars($_vb_texto) ?></a></div>