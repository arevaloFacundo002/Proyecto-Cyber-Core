<?php
// Estilos compartidos por consulta2.php, facturas.php y factura_ver.php (usa theme.css)
?>
<style>
    body { margin: 0; font-family: 'Segoe UI', sans-serif; }
    .container { width: 92%; max-width: 1150px; margin: 40px auto; background: var(--cc-superficie);
        padding: 30px; border-radius: 12px; border: 1px solid var(--cc-borde); box-shadow: 0 0 15px rgba(0,0,0,.25); }
    h1 { text-align: center; color: var(--cc-primario); text-shadow: 0 0 12px rgba(0,234,255,.4); margin-bottom: 8px; }
    .subtitulo { text-align: center; color: var(--cc-texto-secundario); margin-bottom: 18px; font-size: 14px; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th { background: rgba(0,234,255,.12); color: var(--cc-primario); padding: 10px 12px; text-align: left;
        border-bottom: 1px solid var(--cc-borde); font-weight: 700; }
    td { padding: 10px 12px; border-bottom: 1px solid var(--cc-borde); color: var(--cc-texto); }
    td.num, th.num { text-align: right; }
    tr:hover td { background-color: rgba(0,234,255,.05); }
    .volver, .btn-cc { display: inline-block; background: var(--cc-primario); padding: 10px 18px;
        color: var(--cc-texto-sobre-primario); font-weight: bold; text-decoration: none; border-radius: 10px;
        border: none; cursor: pointer; font-size: 14px; transition: .2s; }
    .volver:hover, .btn-cc:hover { background: var(--cc-primario-hover); }
    .btn-sec { background: transparent; color: var(--cc-primario); border: 1px solid var(--cc-primario); }
    .btn-sec:hover { background: rgba(0,234,255,.12); }
    .empty { text-align: center; padding: 18px; color: var(--cc-texto-secundario); }
    .filtros { display: flex; flex-wrap: wrap; gap: 12px; align-items: end; background: var(--cc-superficie-2);
        border: 1px solid var(--cc-borde); border-radius: 12px; padding: 16px; margin-bottom: 20px; }
    .filtros label { display: block; font-size: 12px; color: var(--cc-texto-secundario); margin-bottom: 4px; font-weight: 600; }
    .filtros input, .filtros select { background: var(--cc-superficie); color: var(--cc-texto);
        border: 1px solid var(--cc-borde); border-radius: 8px; padding: 9px 10px; font-size: 14px; }
    .badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 700; color: #0B1220; display: inline-block; }
    .aviso { padding: 10px 14px; border-radius: 10px; margin-bottom: 16px; font-weight: 600; }
    .aviso.ok { border: 1px solid var(--cc-exito); color: var(--cc-exito); }
    .aviso.error { border: 1px solid var(--cc-peligro); color: var(--cc-peligro); }
</style>