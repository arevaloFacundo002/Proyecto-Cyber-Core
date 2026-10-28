<?php
require_once '../../auth/auth.php';
require_once '../../models/Pedido.php';

// Solo administrador y empleados
$rol = $_SESSION['rol'] ?? '';
if (!in_array($rol, ['administrador', 'empleado'], true)) {
    header("Location: ../../home.php");
    exit;
}

$id_pedido = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id_pedido <= 0) {
    header("Location: listarPedidos.php");
    exit;
}

$pedidosModelo = new Pedido();
$pedido = $pedidosModelo->obtenerPedidoAdmin($id_pedido);

if (!$pedido) {
    die("Pedido no encontrado.");
}

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function plata($valor)
{
    return '$' . number_format((float) $valor, 2, ',', '.');
}

function fechaHora($f)
{
    return $f ? date('d/m/Y H:i', strtotime($f)) : '';
}

// Token del formulario de gestión
if (empty($_SESSION['csrf_admin_pedidos'])) {
    $_SESSION['csrf_admin_pedidos'] = bin2hex(random_bytes(16));
}

// Mensaje que deja el controlador (ok / error)
$flash = $_SESSION['pedido_flash'] ?? null;
unset($_SESSION['pedido_flash']);

$estadoActual = $pedido['estado'];
$infoActual   = Pedido::ESTADOS[$estadoActual] ?? ['emoji' => '⚪', 'color' => '#999999'];

$permitidos = $pedidosModelo->estadosPermitidos($estadoActual);
$siguiente  = $pedidosModelo->siguienteEstado($estadoActual);
$puedeCancelar = in_array('Cancelado', $permitidos, true);
$otros = array_values(array_diff($permitidos, ['Cancelado']));
$finalizado = empty($permitidos);

$etiquetasBoton = [
    'Confirmado'           => '✅ Confirmar pedido',
    'Preparando pedido'    => '📦 Pasar a preparación',
    'Listo para despachar' => '🏷️ Listo para despachar',
    'En camino'            => '🚚 Despachar (en camino)',
    'Entregado'            => '🏁 Marcar como entregado',
];

// Barra de progreso
$estaCancelado = ($estadoActual === 'Cancelado');
$secuencia     = $pedidosModelo->secuenciaEstados();
$posActual     = $pedidosModelo->posicionEstado($estadoActual);

$etiquetasLugar = [
    'casa'         => '🏠 Casa',
    'departamento' => '🏢 Departamento',
    'trabajo'      => '💼 Trabajo',
    'local'        => '🏪 Local comercial',
    'otro'         => '📍 Otro',
];
$tipoLugarTexto = $etiquetasLugar[$pedido['tipo_lugar'] ?? ''] ?? 'N/A';

$transportistas = $pedidosModelo->listarTransportistas();

$ruta_raiz = '../../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedido #<?= (int) $pedido['id_pedidos'] ?> - CyberCore</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../../css/theme.css" rel="stylesheet">

<style>
body { margin: 0; font-family: 'Segoe UI', sans-serif; }

.contenedor { max-width: 960px; margin: 30px auto 60px auto; padding: 0 20px; }

h1 { color: var(--cc-primario); text-shadow: 0 0 10px rgba(0, 234, 255, 0.4); }

.caja {
    background: var(--cc-superficie);
    border: 1px solid var(--cc-borde);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 22px;
    color: var(--cc-texto);
}

.caja h2 { color: var(--cc-primario); font-size: 19px; margin-top: 0; margin-bottom: 14px; }

.caja table.datos { width: 100%; }
.caja table.datos td { padding: 6px 0; color: var(--cc-texto); vertical-align: top; }
.caja table.datos td:first-child { width: 190px; font-weight: bold; }

.badge-estado {
    padding: 6px 14px; border-radius: 14px; font-weight: bold;
    color: #0B1220; display: inline-block; font-size: 14px;
}

/* Barra de progreso */
.progreso { display: flex; justify-content: space-between; gap: 6px; flex-wrap: wrap; }
.paso { flex: 1; min-width: 95px; text-align: center; opacity: .4; }
.paso.completo { opacity: 1; }
.paso .punto {
    width: 38px; height: 38px; line-height: 36px; margin: 0 auto 6px auto;
    border-radius: 50%; background: var(--cc-superficie-2); border: 2px solid var(--cc-borde); font-size: 17px;
}
.paso.completo .punto { border-color: var(--cc-primario); }
.paso.actual .punto { background: var(--cc-primario); box-shadow: 0 0 14px rgba(0, 234, 255, .7); }
.paso .texto { font-size: 12px; color: var(--cc-texto-secundario); }
.paso.completo .texto { color: var(--cc-texto); font-weight: bold; }

/* Formulario de gestión */
.acciones-rapidas { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px; }

/* Historial */
.linea { position: relative; padding-left: 26px; }
.linea::before {
    content: ""; position: absolute; left: 8px; top: 6px; bottom: 6px;
    width: 2px; background: var(--cc-borde);
}
.evento { position: relative; margin-bottom: 18px; }
.evento .bolita {
    position: absolute; left: -26px; top: 4px; width: 18px; height: 18px;
    border-radius: 50%; border: 3px solid var(--cc-superficie);
}
.evento .titulo { font-weight: bold; color: var(--cc-texto); }
.evento .meta { font-size: 12px; color: var(--cc-texto-secundario); }
.evento .nota {
    margin-top: 6px; padding: 8px 12px; border-radius: 8px; font-size: 14px;
    background: var(--cc-superficie-2); color: var(--cc-texto);
}
</style>
</head>

<body>

<?php require __DIR__ . '/../../includes/header_admin.php'; ?>

<div class="contenedor">

    <a href="listarPedidos.php" class="btn btn-secondary">← Volver a Pedidos</a>

    <div class="d-flex flex-wrap align-items-center gap-3 mt-3 mb-3">
        <h1 class="mb-0">Pedido #<?= (int) $pedido['id_pedidos'] ?></h1>
        <span class="badge-estado" style="background: <?= e($infoActual['color']) ?>;">
            <?= $infoActual['emoji'] ?> <?= e($estadoActual) ?>
        </span>
        <span class="text-muted">Realizado el <?= e(date('d/m/Y', strtotime($pedido['fecha_pedidos']))) ?></span>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipo'] === 'ok' ? 'success' : 'danger' ?>"><?= e($flash['texto']) ?></div>
    <?php endif; ?>

    <!-- PROGRESO -->
    <div class="caja">
        <?php if ($estaCancelado): ?>
            <div class="alert alert-danger mb-0">🔴 Este pedido fue <strong>cancelado</strong>.</div>
        <?php else: ?>
            <div class="progreso">
                <?php foreach ($secuencia as $i => $est): ?>
                    <div class="paso <?= $i <= $posActual ? 'completo' : '' ?> <?= $i === $posActual ? 'actual' : '' ?>">
                        <div class="punto"><?= Pedido::ESTADOS[$est]['emoji'] ?></div>
                        <div class="texto"><?= e($est) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- GESTIONAR -->
    <div class="caja">
        <h2>Gestionar pedido</h2>

        <?php if ($finalizado): ?>

            <p class="mb-0 text-muted">
                Este pedido está <strong><?= e($estadoActual) ?></strong> y ya no admite cambios de estado.
            </p>

        <?php else: ?>

            <form action="../../controllers/pedidosControllers/PedidoController.php?accion=cambiarEstado" method="POST">
                <input type="hidden" name="id_pedido" value="<?= (int) $pedido['id_pedidos'] ?>">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_admin_pedidos']) ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Transportista</label>
                        <input type="text" name="transportista" class="form-control" list="lista-transportistas"
                               maxlength="100" value="<?= e($pedido['empresa_transporte'] ?? '') ?>"
                               placeholder="Ej: Andreani">
                        <datalist id="lista-transportistas">
                            <?php foreach ($transportistas as $t): ?>
                                <option value="<?= e($t) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">N° de seguimiento</label>
                        <input type="text" name="tracking" class="form-control" maxlength="100"
                               value="<?= e($pedido['num_seguimiento'] ?? '') ?>" placeholder="Código de seguimiento">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Mensaje para el cliente (opcional)</label>
                        <textarea name="nota" class="form-control" rows="2" maxlength="1000"
                                  placeholder="Si lo dejás vacío se usa un mensaje automático según el estado."></textarea>
                    </div>
                </div>

                <div class="acciones-rapidas">
                    <?php if ($siguiente): ?>
                        <button type="submit" name="nuevo_estado" value="<?= e($siguiente) ?>" class="btn btn-info text-dark fw-bold">
                            <?= e($etiquetasBoton[$siguiente] ?? ('Pasar a ' . $siguiente)) ?>
                        </button>
                    <?php endif; ?>

                    <?php if ($estadoActual === 'En camino'): ?>
                        <button type="submit" name="nuevo_estado" value="En camino" class="btn btn-secondary fw-bold">
                            💾 Guardar seguimiento / nota
                        </button>
                    <?php endif; ?>

                    <?php if ($puedeCancelar): ?>
                        <button type="submit" name="nuevo_estado" value="Cancelado" class="btn btn-danger fw-bold"
                                onclick="return confirm('¿Cancelar este pedido? Se repone el stock de los productos.')">
                            ✖ Cancelar pedido
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (count($otros) > 1): ?>
                    <hr>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label">O elegir otro estado (se pueden saltar pasos):</label>
                            <select id="otro-estado" class="form-select">
                                <?php foreach ($otros as $est): ?>
                                    <option value="<?= e($est) ?>"><?= Pedido::ESTADOS[$est]['emoji'] ?> <?= e($est) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" name="nuevo_estado" id="btn-otro" value="<?= e($otros[0]) ?>"
                                    class="btn btn-outline-info fw-bold w-100">Actualizar estado</button>
                        </div>
                    </div>
                    <script>
                    document.getElementById('otro-estado').addEventListener('change', function () {
                        document.getElementById('btn-otro').value = this.value;
                    });
                    </script>
                <?php endif; ?>
            </form>

        <?php endif; ?>
    </div>

    <!-- HISTORIAL -->
    <div class="caja">
        <h2>Historial de cambios</h2>
        <div class="linea">
            <?php foreach (array_reverse($pedido['historial']) as $ev): ?>
                <?php $inf = Pedido::ESTADOS[$ev['estado']] ?? ['emoji' => '⚪', 'color' => '#999999']; ?>
                <div class="evento">
                    <span class="bolita" style="background: <?= e($inf['color']) ?>;"></span>
                    <div class="titulo"><?= $inf['emoji'] ?> <?= e($ev['estado']) ?></div>
                    <div class="meta"><?= e(fechaHora($ev['fecha_evento'])) ?> · <?= e($ev['autor']) ?></div>
                    <?php if (!empty($ev['nota'])): ?>
                        <div class="nota"><?= nl2br(e($ev['nota'])) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- CLIENTE -->
    <div class="caja">
        <h2>Cliente</h2>
        <table class="datos">
            <tr><td>Nombre</td><td><?= e($pedido['cliente_nombre'] . ' ' . $pedido['cliente_apellido']) ?></td></tr>
            <tr><td>Correo</td><td><?= e($pedido['cliente_correo'] ?? 'N/A') ?></td></tr>
            <tr><td>Teléfono</td><td><?= e($pedido['cliente_telefono'] ?? 'N/A') ?></td></tr>
        </table>
    </div>

    <!-- ENTREGA -->
    <div class="caja">
        <h2>Entrega</h2>
        <table class="datos">
            <tr><td>Dirección</td><td><?= e($pedido['direccion_entrega']) ?></td></tr>
            <tr><td>Tipo de lugar</td><td><?= e($tipoLugarTexto) ?></td></tr>
            <?php if (!empty($pedido['piso_depto_torre'])): ?>
                <tr><td>Piso / Depto / Torre</td><td><?= e($pedido['piso_depto_torre']) ?></td></tr>
            <?php endif; ?>
            <?php if (!empty($pedido['referencia'])): ?>
                <tr><td>Referencia</td><td><?= e($pedido['referencia']) ?></td></tr>
            <?php endif; ?>
            <tr>
                <td>Recibe</td>
                <td>
                    <?php if (($pedido['recibe_quien'] ?? 'yo') === 'otra_persona'): ?>
                        <?= e($pedido['recibe_nombre']) ?> — Tel: <?= e($pedido['recibe_telefono']) ?>
                    <?php else: ?>
                        El/la cliente (<?= e($pedido['cliente_nombre']) ?>)
                    <?php endif; ?>
                </td>
            </tr>
            <tr><td>Transportista</td><td><?= e($pedido['empresa_transporte'] ?: 'Sin asignar') ?></td></tr>
            <tr><td>N° de seguimiento</td><td><?= e($pedido['num_seguimiento'] ?: 'Sin cargar') ?></td></tr>
            <?php if (!empty($pedido['fecha_envio'])): ?>
                <tr><td>Fecha de envío</td><td><?= e(date('d/m/Y', strtotime($pedido['fecha_envio']))) ?></td></tr>
            <?php endif; ?>
            <?php if (!empty($pedido['fecha_estimada_entrega'])): ?>
                <tr><td>Llegada estimada</td><td><?= e(date('d/m/Y', strtotime($pedido['fecha_estimada_entrega']))) ?></td></tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- PAGO -->
    <div class="caja">
        <h2>Pago</h2>
        <table class="datos">
            <tr><td>Método</td><td><?= e($pedido['metodo_pago'] ?? 'N/A') ?></td></tr>
            <tr><td>Estado del pago</td><td><?= e($pedido['estado_pagos'] ?? 'N/A') ?></td></tr>
            <tr><td>Referencia</td><td><?= e($pedido['referencia_transaccion'] ?? 'N/A') ?></td></tr>
            <tr><td>Factura</td><td>
                <?php if (!empty($pedido['id_factura'])): ?>
                    <a href="../../consultas/factura_ver.php?id=<?= (int) $pedido['id_factura'] ?>"><?= e($pedido['numero_factura']) ?> · ver</a>
                <?php else: ?>
                    Sin emitir
                <?php endif; ?>
            </td></tr>
        </table>

        <?php if (($pedido['estado_pagos'] ?? '') !== 'Aprobado' && $pedido['estado'] !== 'Cancelado' && !empty($pedido['metodo_pago'])): ?>
            <form method="POST" action="../../controllers/pedidosControllers/PedidoController.php?accion=aprobarPago" style="margin-top:12px;"
                  onsubmit="return confirm('¿Marcar el pago como aprobado? Se emitirá la factura automáticamente.');">
                <input type="hidden" name="id_pedido" value="<?= (int) $pedido['id_pedidos'] ?>">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_admin_pedidos']) ?>">
                <button type="submit" class="btn btn-success fw-bold">✅ Marcar pago como aprobado</button>
                <div style="font-size:12px;opacity:.7;margin-top:6px;">Al aprobarlo se emite la factura del cliente.</div>
            </form>
        <?php endif; ?>
    </div>

    <!-- PRODUCTOS -->
    <div class="caja">
        <h2>Productos</h2>
        <table class="table table-hover">
            <thead>
                <tr><th>Producto</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr>
            </thead>
            <tbody>
                <?php foreach ($pedido['items'] as $it): ?>
                <tr>
                    <td><?= e($it['nombre']) ?></td>
                    <td><?= (int) $it['cantidad'] ?></td>
                    <td><?= plata($it['precio_unitario_base']) ?></td>
                    <td><?= plata($it['subtotal_final']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="text-end fw-bold mb-0" style="color: var(--cc-primario);">
            Total: <?= plata($pedido['monto_total']) ?>
        </p>
    </div>

</div>

<script src="../../js/theme-toggle.js"></script>

</body>
</html>