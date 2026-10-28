<?php
require_once __DIR__ . '/../../auth/auth.php';
require_once __DIR__ . '/../../auth/admin.php';
require_once __DIR__ . '/../../models/Anuncio.php';
require_once __DIR__ . '/../../models/Notificacion.php';

$anuncioModel = new Anuncio();
$notifModel   = new Notificacion();
$anuncios     = $anuncioModel->listar();

$etiquetas_audiencia = [
    'todos_clientes' => 'clientes',
    'todos_staff'    => 'administradores/empleados',
    'todos'          => 'usuarios (clientes + staff)',
];
$etiquetas_canal = ['app' => 'App', 'email' => 'Email', 'whatsapp' => 'WhatsApp'];

$mensajes = [
    'creado'    => 'Anuncio creado correctamente. Queda como borrador.',
    'editado'   => 'Anuncio editado correctamente.',
    'eliminado' => 'Borrador eliminado correctamente.',
];

function e($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }

$ruta_raiz = '../../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Comunicaciones - CyberCore</title>
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

<div class="contenedor">

    <a href="../../inicio.php" class="btn btn-secondary mt-3">← Volver al Panel</a>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
        <h1 class="mb-0">📣 Comunicaciones</h1>
        <a href="crearAnuncio.php" class="btn btn-info text-dark fw-bold">+ Nuevo anuncio</a>
    </div>

    <?php if (isset($_GET['mensaje'])): ?>
        <div class="alert alert-success mt-3">
            <?php if ($_GET['mensaje'] === 'enviado'): ?>
                Anuncio enviado correctamente a <?= (int) ($_GET['cantidad'] ?? 0) ?> usuarios.
            <?php elseif ($_GET['mensaje'] === 'probado'): ?>
                Anuncio de prueba enviado a tu propia cuenta. Revisá tu campanita 🔔 y tus canales.
            <?php else: ?>
                <?= e($mensajes[$_GET['mensaje']] ?? 'Operación realizada correctamente.') ?>
            <?php endif; ?>
        </div>
    <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger mt-3">Ocurrió un error. Revisá los datos e intentá de nuevo.</div>
    <?php endif; ?>

    <div class="table-responsive mt-3">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Estado</th>
                    <th>Título</th>
                    <th>Tipo</th>
                    <th>Audiencia</th>
                    <th>Canales</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($anuncios)): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">Todavía no hay anuncios. Creá el primero con "Nuevo anuncio".</td></tr>
            <?php endif; ?>
            <?php foreach ($anuncios as $a): ?>
                <tr>
                    <td>
                        <?php if ($a['enviado']): ?>
                            <span class="badge bg-success">Enviado</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Borrador</span>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= e($a['titulo']) ?></strong></td>
                    <td><?= e($a['tipo']) ?></td>
                    <td><?= e($etiquetas_audiencia[$a['audiencia']] ?? $a['audiencia']) ?></td>
                    <td><?= e(implode(', ', array_map(fn($c) => $etiquetas_canal[$c] ?? $c, explode(',', $a['canales'])))) ?></td>
                    <td><?= e(date('d/m/Y H:i', strtotime($a['enviado'] ? $a['fecha_envio'] : $a['fecha_creacion']))) ?></td>
                    <td>
                        <?php if (!$a['enviado']):
                            $cantidad = $notifModel->contar_audiencia($a['audiencia']);
                            $audiencia_txt = $etiquetas_audiencia[$a['audiencia']] ?? $a['audiencia'];
                            $canales_txt = implode(', ', array_map(fn($c) => $etiquetas_canal[$c] ?? $c, explode(',', $a['canales'])));
                            $confirm_msg = "Vas a enviar este mensaje a {$cantidad} {$audiencia_txt} por {$canales_txt}. Esta acción no se puede deshacer. ¿Confirmar?";
                        ?>
                            <a class="btn btn-info btn-sm text-dark fw-bold"
                               href="../../controllers/tablasMaestrasControllers/AnuncioController.php?accion=enviar&id=<?= (int) $a['id_anuncio'] ?>"
                               onclick="return confirm('<?= e(addslashes($confirm_msg)) ?>')">🚀 Enviar</a>
                            <a class="btn btn-outline-info btn-sm"
                               href="../../controllers/tablasMaestrasControllers/AnuncioController.php?accion=probar&id=<?= (int) $a['id_anuncio'] ?>">Probar (a mí)</a>
                            <a class="btn btn-outline-secondary btn-sm" href="editarAnuncio.php?id=<?= (int) $a['id_anuncio'] ?>">Editar</a>
                            <a class="btn btn-outline-danger btn-sm"
                               href="../../controllers/tablasMaestrasControllers/AnuncioController.php?accion=eliminar&id=<?= (int) $a['id_anuncio'] ?>"
                               onclick="return confirm('¿Eliminar este borrador?')">Eliminar</a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="../../js/theme-toggle.js"></script>
</body>
</html>