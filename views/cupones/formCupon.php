<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'administrador') {
    header("Location: ../../home.php");
    exit;
}

require_once __DIR__ . '/../../models/Cupon.php';

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['csrf_cupones'])) {
    $_SESSION['csrf_cupones'] = bin2hex(random_bytes(16));
}

$id    = (int) ($_GET['id'] ?? 0);
$cupon = $id > 0 ? (new Cupon())->obtener($id) : null;
if ($id > 0 && !$cupon) {
    header("Location: listarCupones.php");
    exit;
}

$flash = $_SESSION['cupon_flash'] ?? null;
$old   = $_SESSION['cupon_old'] ?? null;
unset($_SESSION['cupon_flash'], $_SESSION['cupon_old']);

$v = [
    'codigo'       => $old['codigo']       ?? ($cupon['codigo'] ?? ''),
    'descripcion'  => $old['descripcion']  ?? ($cupon['descripcion'] ?? ''),
    'tipo'         => $old['tipo']         ?? ($cupon['tipo'] ?? 'Porcentaje'),
    'valor'        => $old['valor']        ?? ($cupon ? (float) $cupon['valor'] : ''),
    'minimo'       => $old['minimo']       ?? ($cupon ? (float) $cupon['compra_minima'] : '0'),
    'fecha_inicio' => $old['fecha_inicio'] ?? ($cupon && $cupon['fecha_inicio'] ? substr($cupon['fecha_inicio'], 0, 10) : ''),
    'usos_maximos' => $old['usos_maximos'] ?? ($cupon && $cupon['usos_maximos'] !== null ? (int) $cupon['usos_maximos'] : ''),
    'fecha_fin'    => $old['fecha_fin']    ?? ($cupon && $cupon['fecha_fin'] ? substr($cupon['fecha_fin'], 0, 10) : ''),
];
$titulo_pagina = $cupon ? 'Editar cupón' : 'Nuevo cupón';
$ruta_raiz = '../../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titulo_pagina) ?> - CyberCore</title>
<link href="../../css/theme.css" rel="stylesheet">
<?php require __DIR__ . '/../../consultas/_estilo_consultas.php'; ?>
<style>
    .container { max-width: 760px; }
    .campo-form { margin-bottom: 16px; }
    .campo-form label { display: block; font-size: 13px; font-weight: 600; color: var(--cc-texto-secundario); margin-bottom: 5px; }
    .campo-form input, .campo-form select { width: 100%; box-sizing: border-box; background: var(--cc-superficie-2); color: var(--cc-texto);
        border: 1px solid var(--cc-borde); border-radius: 8px; padding: 10px; font-size: 14px; font-family: inherit; }
    .ayuda { font-size: 12px; color: var(--cc-texto-secundario); margin-top: 4px; }
    .fila2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    @media (max-width: 600px) { .fila2 { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<?php require __DIR__ . '/../../includes/header_admin.php'; ?>

<div class="container">
    <h1>🎟 <?= e($titulo_pagina) ?></h1>

    <?php if ($flash): ?><div class="aviso <?= e($flash['tipo']) ?>"><?= e($flash['texto']) ?></div><?php endif; ?>

    <form method="POST" action="../../controllers/tablasMaestrasControllers/CuponController.php">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_cupones']) ?>">
        <input type="hidden" name="accion" value="<?= $cupon ? 'editar' : 'crear' ?>">
        <input type="hidden" name="id_cupon" value="<?= $id ?>">

        <div class="fila2">
            <div class="campo-form">
                <label>Código</label>
                <input type="text" name="codigo" maxlength="40" required value="<?= e($v['codigo']) ?>" placeholder="Ej: BIENVENIDA10" style="text-transform:uppercase;">
                <div class="ayuda">Solo letras y números. Se guarda en mayúsculas y el cliente puede escribirlo como quiera.</div>
            </div>
            <div class="campo-form">
                <label>Descripción (opcional)</label>
                <input type="text" name="descripcion" maxlength="200" value="<?= e($v['descripcion']) ?>" placeholder="Ej: 10% para clientes nuevos">
                <div class="ayuda">Es solo para vos: el cliente no la ve.</div>
            </div>
        </div>

        <div class="fila2">
            <div class="campo-form">
                <label>Tipo de descuento</label>
                <select name="tipo">
                    <option value="Porcentaje" <?= $v['tipo'] === 'Porcentaje' ? 'selected' : '' ?>>Porcentaje (%)</option>
                    <option value="Fijo" <?= $v['tipo'] === 'Fijo' ? 'selected' : '' ?>>Monto fijo ($)</option>
                </select>
            </div>
            <div class="campo-form">
                <label>Valor</label>
                <input type="number" name="valor" step="0.01" min="0.01" required value="<?= e($v['valor']) ?>" placeholder="10">
                <div class="ayuda">Si es porcentaje, de 1 a 90. Si es fijo, el monto en pesos.</div>
            </div>
        </div>

        <div class="campo-form">
            <label>Compra mínima ($)</label>
            <input type="number" name="minimo" step="0.01" min="0" value="<?= e($v['minimo']) ?>">
            <div class="ayuda">El cupón se puede usar si los productos, ya con la promoción aplicada, suman al menos este monto (sin contar el envío). 0 = sin mínimo.</div>
        </div>

        <div class="fila2">
            <div class="campo-form">
                <label>Desde (opcional)</label>
                <input type="date" name="fecha_inicio" value="<?= e($v['fecha_inicio']) ?>">
            </div>
            <div class="campo-form">
                <label>Hasta (opcional, inclusive)</label>
                <input type="date" name="fecha_fin" value="<?= e($v['fecha_fin']) ?>">
            </div>
        </div>

        <div class="campo-form">
            <label>Límite de usos (opcional)</label>
            <input type="number" name="usos_maximos" step="1" min="1" value="<?= e($v['usos_maximos']) ?>" placeholder="Sin límite">
            <div class="ayuda">Cantidad total de veces que se puede usar entre todos los clientes. Cada cliente puede usarlo una sola vez.</div>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button class="btn-cc" type="submit">Guardar</button>
            <a class="btn-cc btn-sec" href="listarCupones.php">Cancelar</a>
        </div>
    </form>
</div>
<script src="../../js/theme-toggle.js"></script>
</body>
</html>