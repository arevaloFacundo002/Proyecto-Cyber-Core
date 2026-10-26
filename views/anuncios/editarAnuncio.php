<?php
require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/admin.php';
require_once __DIR__ . '/../../models/Anuncio.php';

$anuncioModel = new Anuncio();
$anuncio = $anuncioModel->obtener((int) ($_GET['id'] ?? 0));

if (!$anuncio || $anuncio['enviado']) {
    die('Este anuncio no existe o ya fue enviado (no se puede editar).');
}

$canales_actuales = explode(',', $anuncio['canales']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>CyberCore - Editar anuncio</title>
<style>
    body { margin:0; font-family:'Segoe UI', sans-serif; background:#f5f5f5; }
    .topbar { background:#0a0a0a; color:#00eaff; padding:18px 40px; font-size:22px; font-weight:bold; }
    .contenido { padding:30px 40px; max-width:500px; }
    label { display:block; margin-top:16px; font-weight:bold; font-size:14px; }
    input[type=text], textarea, select { width:100%; padding:10px; margin-top:6px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; }
    textarea { height:90px; resize:vertical; }
    .canales label { display:inline-block; font-weight:normal; margin-right:14px; }
    button { margin-top:20px; padding:12px 24px; background:#00eaff; border:none; border-radius:20px; font-weight:bold; cursor:pointer; }
</style>
</head>
<body>

<div class="topbar">
    📣 Editar anuncio
    <a href="listarAnuncio.php" style="color:#ccc; text-decoration:none; font-size:14px; float:right;">← Volver</a>
</div>

<div class="contenido">
    <form method="POST" action="../../../controllers/tablasMaestrasControllers/AnuncioController.php">
        <input type="hidden" name="accion" value="editar">
        <input type="hidden" name="id_anuncio" value="<?= $anuncio['id_anuncio'] ?>">

        <label>Título</label>
        <input type="text" name="titulo" value="<?= htmlspecialchars($anuncio['titulo']) ?>" required>

        <label>Mensaje</label>
        <textarea name="mensaje" required><?= htmlspecialchars($anuncio['mensaje']) ?></textarea>

        <label>Tipo</label>
        <select name="tipo">
            <option value="oferta" <?= $anuncio['tipo'] === 'oferta' ? 'selected' : '' ?>>Oferta</option>
            <option value="evento" <?= $anuncio['tipo'] === 'evento' ? 'selected' : '' ?>>Evento</option>
            <option value="aviso_general" <?= $anuncio['tipo'] === 'aviso_general' ? 'selected' : '' ?>>Aviso general</option>
        </select>

        <label>Audiencia</label>
        <select name="audiencia">
            <option value="todos_clientes" <?= $anuncio['audiencia'] === 'todos_clientes' ? 'selected' : '' ?>>Todos los clientes</option>
            <option value="todos_staff" <?= $anuncio['audiencia'] === 'todos_staff' ? 'selected' : '' ?>>Todo el staff</option>
            <option value="todos" <?= $anuncio['audiencia'] === 'todos' ? 'selected' : '' ?>>Todos</option>
        </select>

        <label>Canales</label>
        <div class="canales">
            <label><input type="checkbox" name="canales[]" value="app" <?= in_array('app', $canales_actuales) ? 'checked' : '' ?>> App</label>
            <label><input type="checkbox" name="canales[]" value="email" <?= in_array('email', $canales_actuales) ? 'checked' : '' ?>> Email</label>
            <label><input type="checkbox" name="canales[]" value="whatsapp" <?= in_array('whatsapp', $canales_actuales) ? 'checked' : '' ?>> WhatsApp</label>
        </div>

        <button type="submit">Guardar cambios</button>
    </form>
</div>

</body>
</html>