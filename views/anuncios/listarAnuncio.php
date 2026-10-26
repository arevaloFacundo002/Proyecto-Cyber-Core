<?php
require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/admin.php';
require_once __DIR__ . '/../../models/Anuncio.php';
require_once __DIR__ . '/../../models/Notificacion.php';

$anuncioModel = new Anuncio();
$notifModel = new Notificacion();
$anuncios = $anuncioModel->listar();

$etiquetas_audiencia = [
    'todos_clientes' => 'clientes',
    'todos_staff' => 'administradores/empleados',
    'todos' => 'usuarios (clientes + staff)'
];
$etiquetas_canal = ['app' => 'App', 'email' => 'Email', 'whatsapp' => 'WhatsApp'];

$mensajes = [
    'creado' => 'Anuncio creado correctamente. Queda como borrador.',
    'editado' => 'Anuncio editado correctamente.',
    'eliminado' => 'Borrador eliminado correctamente.',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>CyberCore - Anuncios</title>
<style>
    body { margin:0; font-family:'Segoe UI', sans-serif; background:#f5f5f5; }
    .topbar { background:#0a0a0a; color:#00eaff; padding:18px 40px; font-size:22px; font-weight:bold; display:flex; justify-content:space-between; align-items:center; }
    .topbar-derecha { display:flex; gap:12px; align-items:center; }
    .topbar a.btn-nuevo { background:#00eaff; color:#000; padding:8px 16px; border-radius:16px; text-decoration:none; font-size:14px; font-weight:bold; }
    .volver { color:#ccc; text-decoration:none; font-size:14px; }
    .volver:hover { color:#00eaff; }
    .contenido { padding:30px 40px; }
    table { width:100%; border-collapse:collapse; background:white; box-shadow:0 0 10px rgba(0,0,0,0.08); }
    th, td { text-align:left; padding:12px 14px; border-bottom:1px solid #eee; font-size:14px; vertical-align: middle; }
    th { background:#0a0a0a; color:white; }
    .badge { display:inline-block; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:bold; }
    .badge-borrador { background:#fff3cd; color:#8a6d00; }
    .badge-enviado { background:#d4edda; color:#0a8a2f; }
    .acciones { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .acciones-secundarias { display:flex; gap:12px; }
    .acciones-secundarias a { font-size:13px; font-weight:bold; text-decoration:none; }
    .editar { color:#00717a; }
    .eliminar { color:#c0392b; }
    .btn-enviar {
        background:#0a8a2f;
        color:white;
        padding:7px 16px;
        border-radius:16px;
        text-decoration:none;
        font-size:13px;
        font-weight:bold;
    }
    .btn-enviar:hover { background:#076a24; }
    .btn-probar {
        background:#eee;
        color:#333;
        padding:7px 14px;
        border-radius:16px;
        text-decoration:none;
        font-size:13px;
        font-weight:bold;
    }
    .btn-probar:hover { background:#ddd; }
    .aviso { padding:10px 16px; border-radius:6px; margin-bottom:16px; font-weight:bold; }
    .aviso-ok { background:#d4edda; color:#0a8a2f; }
    .aviso-error { background:#f8d7da; color:#c0392b; }
</style>
</head>
<body>

<div class="topbar">
    📣 Anuncios
    <div class="topbar-derecha">
        <a href="../../inicio.php" class="volver">← Volver</a>
        <a href="crearAnuncio.php" class="btn-nuevo">+ Nuevo anuncio</a>
    </div>
</div>

<div class="contenido">
    <?php if (isset($_GET['mensaje'])): ?>
        <div class="aviso aviso-ok">
            <?php if ($_GET['mensaje'] === 'enviado'): ?>
                Anuncio enviado correctamente a <?= (int)($_GET['cantidad'] ?? 0) ?> usuarios.
            <?php elseif ($_GET['mensaje'] === 'probado'): ?>
                Anuncio de prueba enviado a tu propia cuenta. Revisá tus canales.
            <?php else: ?>
                <?= $mensajes[$_GET['mensaje']] ?? 'Operación realizada correctamente.' ?>
            <?php endif; ?>
        </div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="aviso aviso-error">Ocurrió un error. Intentá de nuevo.</div>
    <?php endif; ?>

    <table>
        <tr>
            <th>Estado</th>
            <th>Título</th>
            <th>Tipo</th>
            <th>Audiencia</th>
            <th>Canales</th>
            <th>Fecha</th>
            <th>Acciones</th>
        </tr>
        <?php foreach ($anuncios as $a): ?>
        <tr>
            <td>
                <span class="badge <?= $a['enviado'] ? 'badge-enviado' : 'badge-borrador' ?>">
                    <?= $a['enviado'] ? 'Enviado' : 'Borrador' ?>
                </span>
            </td>
            <td><strong><?= htmlspecialchars($a['titulo']) ?></strong></td>
            <td><?= htmlspecialchars($a['tipo']) ?></td>
            <td><?= htmlspecialchars($etiquetas_audiencia[$a['audiencia']] ?? $a['audiencia']) ?></td>
            <td><?= htmlspecialchars($a['canales']) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($a['enviado'] ? $a['fecha_envio'] : $a['fecha_creacion'])) ?></td>
            <td class="acciones">
                <?php if (!$a['enviado']):
                    $cantidad = $notifModel->contar_audiencia($a['audiencia']);
                    $audiencia_txt = $etiquetas_audiencia[$a['audiencia']] ?? $a['audiencia'];
                    $canales_arr = array_map(fn($c) => $etiquetas_canal[$c] ?? $c, explode(',', $a['canales']));
                    $canales_txt = implode(', ', $canales_arr);
                    $confirm_msg = "Vas a enviar este mensaje a {$cantidad} {$audiencia_txt} por {$canales_txt}. Esta acción no se puede deshacer. ¿Confirmar?";
                ?>
                    <a class="btn-enviar"
                       href="../../controllers/tablasMaestrasControllers/AnuncioController.php?accion=enviar&id=<?= $a['id_anuncio'] ?>"
                       onclick="return confirm('<?= htmlspecialchars($confirm_msg, ENT_QUOTES) ?>')">
                        🚀 Enviar
                    </a>
                    <a class="btn-probar"
                       href=".../../controllers/tablasMaestrasControllers/AnuncioController.php?accion=probar&id=<?= $a['id_anuncio'] ?>">
                        Probar (a mí)
                    </a>
                    <div class="acciones-secundarias">
                        <a class="editar" href="editarAnuncio.php?id=<?= $a['id_anuncio'] ?>">Editar</a>
                        <a class="eliminar"
                           href="../../controllers/tablasMaestrasControllers/AnuncioController.php?accion=eliminar&id=<?= $a['id_anuncio'] ?>"
                           onclick="return confirm('¿Eliminar este borrador?')">Eliminar</a>
                    </div>
                <?php else: ?>
                    <span style="color:#999;">—</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>