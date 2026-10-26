<?php
require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/admin.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>CyberCore - Nuevo anuncio</title>
<style>
    body { margin:0; font-family:'Segoe UI', sans-serif; background:#f5f5f5; }
    .topbar { background:#0a0a0a; color:#00eaff; padding:18px 40px; font-size:22px; font-weight:bold; }
    .contenido { padding:30px 40px; max-width:500px; }
    label { display:block; margin-top:16px; font-weight:bold; font-size:14px; }
    input[type=text], textarea, select { width:100%; padding:10px; margin-top:6px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; }
    textarea { height:90px; resize:vertical; }
    .canales label { display:inline-block; font-weight:normal; margin-right:14px; }
    button { margin-top:20px; padding:12px 24px; background:#00eaff; border:none; border-radius:20px; font-weight:bold; cursor:pointer; }
    .nota { margin-top:10px; font-size:13px; color:#777; }
</style>
</head>
<body>

<div class="topbar">📣 Nuevo anuncio</div>

<div class="contenido">
    <p class="nota">Esto se guarda como <strong>borrador</strong>. No se envía nada hasta que lo confirmes desde el listado.</p>

    <form method="POST" action="../../controllers/tablasMaestrasControllers/AnuncioController.php">
        <input type="hidden" name="accion" value="crear">

        <label>Título</label>
        <input type="text" name="titulo" required>

        <label>Mensaje</label>
        <textarea name="mensaje" required></textarea>

        <label>Tipo</label>
        <select name="tipo">
            <option value="oferta">Oferta</option>
            <option value="evento">Evento</option>
            <option value="aviso_general">Aviso general</option>
        </select>

        <label>Audiencia</label>
        <select name="audiencia">
            <option value="todos_clientes">Todos los clientes</option>
            <option value="todos_staff">Todo el staff (admin/empleado)</option>
            <option value="todos">Todos</option>
        </select>

        <label>Canales</label>
        <div class="canales">
            <label><input type="checkbox" name="canales[]" value="app" checked> App</label>
            <label><input type="checkbox" name="canales[]" value="email"> Email</label>
            <label><input type="checkbox" name="canales[]" value="whatsapp"> WhatsApp</label>
        </div>

        <button type="submit">Guardar borrador</button>
    </form>
</div>

</body>
</html>