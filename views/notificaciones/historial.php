<?php
require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../models/Notificacion.php';

$notificacion = new Notificacion();
$lista = $notificacion->listar_todas((int) $_SESSION['id_usuario']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CyberCore - Historial de notificaciones</title>
<style>
    body {
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
        background: #f5f5f5;
        color: #111;
    }
    .topbar {
        background: #0a0a0a;
        color: #00eaff;
        padding: 18px 40px;
        font-size: 22px;
        font-weight: bold;
    }
    .contenido {
        padding: 30px 40px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        box-shadow: 0 0 10px rgba(0,0,0,0.08);
    }
    th, td {
        text-align: left;
        padding: 12px 14px;
        border-bottom: 1px solid #eee;
        font-size: 14px;
    }
    th {
        background: #0a0a0a;
        color: white;
    }
    tr:hover {
        background: #f9f9f9;
    }
    .badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
        margin-right: 4px;
    }
    .badge-leida { background: #e0e0e0; color: #555; }
    .badge-no-leida { background: #00eaff; color: #000; }
    .canal { font-size: 12px; color: #555; }
    .canal.enviado { color: #0a8a2f; }
    .canal.fallido { color: #c0392b; }
    .vacio { text-align: center; padding: 40px; color: #777; }
</style>
</head>
<body>

<div class="topbar">🔔 Historial de notificaciones</div>

<div class="contenido">
<?php if (empty($lista)): ?>
    <p class="vacio">Todavía no tenés notificaciones.</p>
<?php else: ?>
    <table>
        <tr>
            <th>Estado</th>
            <th>Tipo</th>
            <th>Título</th>
            <th>Mensaje</th>
            <th>Canales externos</th>
            <th>Fecha</th>
        </tr>
        <?php foreach ($lista as $n): ?>
        <tr>
            <td>
                <span class="badge <?= $n['leida'] ? 'badge-leida' : 'badge-no-leida' ?>">
                    <?= $n['leida'] ? 'Leída' : 'Nueva' ?>
                </span>
            </td>
            <td><?= htmlspecialchars($n['tipo']) ?></td>
            <td><strong><?= htmlspecialchars($n['titulo']) ?></strong></td>
            <td><?= htmlspecialchars($n['mensaje']) ?></td>
            <td>
                <?php if ($n['canales_externos']): ?>
                    <?php foreach (explode(', ', $n['canales_externos']) as $c):
                        [$canal, $estado] = explode(':', $c); ?>
                        <div class="canal <?= $estado ?>">
                            <?= ucfirst($canal) ?>: <?= ucfirst($estado) ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="canal">solo app</span>
                <?php endif; ?>
            </td>
            <td><?= date('d/m/Y H:i', strtotime($n['fecha_creacion'])) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
</div>

</body>
</html>