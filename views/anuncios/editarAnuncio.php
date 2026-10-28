<?php
require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/admin.php';
require_once __DIR__ . '/../../models/Anuncio.php';

$anuncio = (new Anuncio())->obtener((int) ($_GET['id'] ?? 0));

if (!$anuncio || $anuncio['enviado']) {
    die('Este anuncio no existe o ya fue enviado (no se puede editar).');
}

$canales_actuales = explode(',', $anuncio['canales']);
$v = fn($campo) => htmlspecialchars((string) ($anuncio[$campo] ?? ''), ENT_QUOTES, 'UTF-8');
$sel = fn($campo, $valor) => ($anuncio[$campo] ?? '') === $valor ? 'selected' : '';
$chk = fn($canal) => in_array($canal, $canales_actuales, true) ? 'checked' : '';
$titulo_pagina = 'Editar anuncio';
$accion = 'editar';
$boton = 'Guardar cambios';

$ruta_raiz = '../../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $titulo_pagina ?> - CyberCore</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../../css/theme.css" rel="stylesheet">
<style>
body { margin: 0; font-family: 'Segoe UI', sans-serif; }
.contenedor { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
h1 { color: var(--cc-primario); text-shadow: 0 0 10px rgba(0, 234, 255, 0.4); }
</style>
</head>
<body>

<?php require __DIR__ . '/../../includes/header_admin.php'; ?>

<div class="contenedor" style="max-width: 760px; margin-left: auto; margin-right: auto;">

    <a href="listarAnuncio.php" class="btn btn-secondary mt-3">← Volver a Comunicaciones</a>

    <h1 class="mt-3">📣 <?= $titulo_pagina ?></h1>

    <form action="../../controllers/tablasMaestrasControllers/AnuncioController.php" method="POST" class="mt-3">
        <input type="hidden" name="accion" value="<?= $accion ?>">
        <input type="hidden" name="id_anuncio" value="<?= (int) $anuncio['id_anuncio'] ?>">

        <div class="mb-3">
            <label class="form-label fw-bold">Título</label>
            <input type="text" name="titulo" class="form-control" maxlength="150" value="<?= $v('titulo') ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Mensaje</label>
            <textarea name="mensaje" class="form-control" rows="4" required><?= $v('mensaje') ?></textarea>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="oferta" <?= $sel('tipo', 'oferta') ?>>Oferta</option>
                    <option value="evento" <?= $sel('tipo', 'evento') ?>>Evento</option>
                    <option value="aviso_general" <?= $sel('tipo', 'aviso_general') ?>>Aviso general</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">¿A quién le llega?</label>
                <select name="audiencia" class="form-select">
                    <option value="todos_clientes" <?= $sel('audiencia', 'todos_clientes') ?>>Todos los clientes</option>
                    <option value="todos_staff" <?= $sel('audiencia', 'todos_staff') ?>>Todo el personal (administradores y empleados)</option>
                    <option value="todos" <?= $sel('audiencia', 'todos') ?>>Todos (clientes y personal)</option>
                </select>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold d-block">¿Por dónde se envía?</label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="canales[]" id="c_app" value="app" <?= $chk('app') ?>>
                <label class="form-check-label" for="c_app">🔔 App (campanita)</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="canales[]" id="c_email" value="email" <?= $chk('email') ?>>
                <label class="form-check-label" for="c_email">✉️ Correo</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="canales[]" id="c_wa" value="whatsapp" <?= $chk('whatsapp') ?>>
                <label class="form-check-label" for="c_wa">💬 WhatsApp</label>
            </div>
        </div>

        <button type="submit" class="btn btn-info text-dark fw-bold"><?= $boton ?></button>
    </form>
</div>

<script src="../../js/theme-toggle.js"></script>
</body>
</html>