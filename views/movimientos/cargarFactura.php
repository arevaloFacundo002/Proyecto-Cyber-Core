<?php
require_once '../../auth/auth.php';
require_once '../../models/MovimientoFactura.php';

$rol = $_SESSION['rol'] ?? '';
if (!in_array($rol, ['administrador', 'empleado'], true)) {
    header("Location: ../../home.php");
    exit;
}

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['csrf_factura_mov'])) {
    $_SESSION['csrf_factura_mov'] = bin2hex(random_bytes(16));
}

$flash = $_SESSION['factura_mov_flash'] ?? null;
$old   = $_SESSION['factura_mov_old'] ?? null;
unset($_SESSION['factura_mov_flash'], $_SESSION['factura_mov_old']);

$modelo      = new MovimientoFactura();
$productos   = $modelo->productos();
$proveedores = $modelo->proveedores();

$tipo = $old['tipo'] ?? 'Compra';
$filasOld = [];
if ($old) {
    foreach ((array) ($old['producto'] ?? []) as $i => $idp) {
        $filasOld[] = ['id' => (string) $idp, 'cantidad' => (string) ($old['cantidad'][$i] ?? ''), 'precio' => (string) ($old['precio'][$i] ?? '')];
    }
}

$catalogo = [];
foreach ($productos as $p) {
    $catalogo[] = ['id' => (int) $p['id_producto'], 'codigo' => $p['codigo'], 'nombre' => $p['nombre'],
                   'precio' => (float) $p['precio'], 'stock' => (int) $p['stock']];
}

$ruta_raiz = '../../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cargar factura - CyberCore</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../../css/theme.css" rel="stylesheet">
<style>
    .tabla-items th { font-size: 13px; }
    .tabla-items td { vertical-align: middle; }
    .tabla-items input, .tabla-items .stock-info { font-size: 14px; }
    .stock-info { font-size: 12px; opacity: .75; }
    .stock-mal { color: #ff6b6b; opacity: 1; font-weight: 600; }
    .total-box { font-size: 20px; font-weight: 800; color: var(--cc-primario); }
</style>
</head>
<body>
<?php require __DIR__ . '/../../includes/header_admin.php'; ?>

<div class="container mt-3 mb-5">

    <a href="listarMovimiento.php" class="btn btn-secondary btn-sm mb-3">← Volver a Movimientos</a>

    <h3 class="fw-bold mb-1">Cargar factura</h3>
    <p class="mb-3" style="opacity:.75;">Cargá todos los productos de una factura juntos. Se guarda como <strong>un solo movimiento</strong> y el stock de cada producto se actualiza a la vez.</p>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipo'] === 'ok' ? 'success' : 'danger' ?>"><?= e($flash['texto']) ?></div>
    <?php endif; ?>

    <form action="../../controllers/inputsControllers/MovimientoFacturaController.php" method="POST" id="formFactura" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_factura_mov']) ?>">

        <div class="card shadow border-0 mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Tipo de factura</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="tipo" id="tCompra" value="Compra" <?= $tipo === 'Compra' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-info" for="tCompra">🛒 Compra (entra stock)</label>
                            <input type="radio" class="btn-check" name="tipo" id="tVenta" value="Venta" <?= $tipo === 'Venta' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-info" for="tVenta">🧾 Venta (sale stock)</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Fecha</label>
                        <input type="date" name="fecha" class="form-control" max="<?= date('Y-m-d') ?>" value="<?= e($old['fecha'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">N° de factura <span id="numOpc" style="opacity:.6;font-weight:400;"></span></label>
                        <input type="text" name="numero_comprobante" class="form-control" maxlength="50" value="<?= e($old['numero_comprobante'] ?? '') ?>" placeholder="Ej: A-0001-00001234">
                    </div>
                    <div class="col-md-6" id="bloqueProveedor">
                        <label class="form-label fw-bold">Proveedor</label>
                        <select name="id_proveedor" class="form-select">
                            <option value="">Elegí el proveedor</option>
                            <?php foreach ($proveedores as $pr): ?>
                                <option value="<?= (int) $pr['id_proveedores'] ?>" <?= ((string) ($old['id_proveedor'] ?? '') === (string) $pr['id_proveedores']) ? 'selected' : '' ?>><?= e($pr['nombre_apellido']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6" id="bloqueContraparte">
                        <label class="form-label fw-bold">Cliente (opcional)</label>
                        <input type="text" name="contraparte" class="form-control" maxlength="150" value="<?= e($old['contraparte'] ?? '') ?>" placeholder="Ej: Venta de mostrador">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Comentario (opcional)</label>
                        <input type="text" name="comentario" class="form-control" maxlength="255" value="<?= e($old['comentario'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow border-0 mb-3">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Productos de la factura</h5>
                <div class="mb-3">
                    <label class="form-label fw-bold">Escanear código de barras</label>
                    <input type="text" id="escaner" class="form-control" autocomplete="off" placeholder="Hacé clic acá y escaneá el producto (o escribí el código y Enter)">
                    <div id="escanerMsg" class="form-text"></div>
                </div>
                <datalist id="listaProductos">
                    <?php foreach ($catalogo as $p): ?>
                        <option value="<?= e($p['codigo'] . ' · ' . $p['nombre']) ?>">
                    <?php endforeach; ?>
                </datalist>

                <div class="table-responsive">
                    <table class="table tabla-items">
                        <thead>
                            <tr><th style="width:42%">Producto</th><th style="width:14%">Cantidad</th><th style="width:18%">Precio unitario</th><th style="width:18%" class="text-end">Subtotal</th><th></th></tr>
                        </thead>
                        <tbody id="filas"></tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <button type="button" id="btnAgregar" class="btn btn-outline-info fw-bold">+ Agregar producto</button>
                    <div>Total: <span class="total-box" id="total">$0,00</span></div>
                </div>
                <div class="form-text mt-2">Tip: en la última fila, apretá <strong>Enter</strong> en la cantidad para agregar otra. Si repetís un producto, se suman las cantidades.</div>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="listarMovimiento.php" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-info text-dark fw-bold px-4">Guardar factura</button>
        </div>
    </form>
</div>

<script>
var CATALOGO = <?= json_encode($catalogo, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
var FILAS_OLD = <?= json_encode($filasOld, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;

var porEtiqueta = {}, porId = {};
CATALOGO.forEach(function (p) { porEtiqueta[p.codigo + ' · ' + p.nombre] = p; porId[p.id] = p; });

var tbody = document.getElementById('filas');

function dinero(n) { return '$' + n.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
function esVenta() { return document.getElementById('tVenta').checked; }

function recalcular() {
    var total = 0;
    var cantPorProducto = {};
    tbody.querySelectorAll('tr').forEach(function (tr) {
        var id = tr.querySelector('.prod-id').value;
        var c = parseInt(tr.querySelector('.cant').value, 10) || 0;
        var pr = parseFloat((tr.querySelector('.precio').value || '0').replace(',', '.')) || 0;
        var sub = c * pr;
        tr.querySelector('.sub').textContent = dinero(sub);
        total += sub;
        if (id) cantPorProducto[id] = (cantPorProducto[id] || 0) + c;
    });
    tbody.querySelectorAll('tr').forEach(function (tr) {
        var id = tr.querySelector('.prod-id').value;
        var info = tr.querySelector('.stock-info');
        if (!id || !porId[id]) { info.textContent = ''; info.className = 'stock-info'; return; }
        var s = porId[id].stock;
        var pide = cantPorProducto[id] || 0;
        if (esVenta() && pide > s) {
            info.textContent = 'Stock actual: ' + s + ' (no alcanza)';
            info.className = 'stock-info stock-mal';
        } else {
            info.textContent = 'Stock actual: ' + s;
            info.className = 'stock-info';
        }
    });
    document.getElementById('total').textContent = dinero(total);
}

function nuevaFila(datos) {
    var tr = document.createElement('tr');
    tr.innerHTML =
        '<td><input type="text" class="form-control prod-txt" list="listaProductos" placeholder="Escribí código o nombre">' +
        '<input type="hidden" name="producto[]" class="prod-id"><div class="stock-info"></div></td>' +
        '<td><input type="number" name="cantidad[]" class="form-control cant" min="1" step="1"></td>' +
        '<td><input type="number" name="precio[]" class="form-control precio" min="0" step="0.01" value="0"></td>' +
        '<td class="text-end sub">$0,00</td>' +
        '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger quitar" title="Quitar fila">×</button></td>';
    tbody.appendChild(tr);

    var txt = tr.querySelector('.prod-txt'), hid = tr.querySelector('.prod-id');
    var cant = tr.querySelector('.cant'), precio = tr.querySelector('.precio');

    function elegirProducto() {
        var p = porEtiqueta[txt.value];
        if (p) {
            hid.value = p.id;
            if (esVenta() || parseFloat(precio.value) === 0) precio.value = p.precio;
            txt.setCustomValidity('');
        } else {
            hid.value = '';
            txt.setCustomValidity('Elegí un producto de la lista.');
        }
        recalcular();
    }
    txt.addEventListener('change', elegirProducto);
    txt.addEventListener('input', elegirProducto);
    cant.addEventListener('input', recalcular);
    precio.addEventListener('input', recalcular);
    cant.addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter') {
            ev.preventDefault();
            if (tr === tbody.lastElementChild) { nuevaFila().querySelector('.prod-txt').focus(); }
        }
    });
    tr.querySelector('.quitar').addEventListener('click', function () {
        if (tbody.children.length > 1) { tr.remove(); recalcular(); }
    });

    if (datos) {
        var p = porId[datos.id];
        if (p) { txt.value = p.codigo + ' · ' + p.nombre; hid.value = p.id; }
        cant.value = datos.cantidad;
        precio.value = datos.precio;
    }
    return tr;
}

function ajustarTipo() {
    var venta = esVenta();
    document.getElementById('bloqueProveedor').style.display = venta ? 'none' : '';
    document.getElementById('bloqueContraparte').style.display = venta ? '' : 'none';
    document.getElementById('numOpc').textContent = venta ? '(opcional)' : '(obligatorio)';
    document.querySelector('[name=numero_comprobante]').required = !venta;
    document.querySelector('[name=id_proveedor]').required = !venta;
    recalcular();
}

document.getElementById('btnAgregar').addEventListener('click', function () { nuevaFila().querySelector('.prod-txt').focus(); });
document.getElementById('tCompra').addEventListener('change', ajustarTipo);
document.getElementById('tVenta').addEventListener('change', ajustarTipo);

var porCodigo = {};
CATALOGO.forEach(function (p) { if (p.codigo) porCodigo[String(p.codigo).trim().toLowerCase()] = p; });

var escaner = document.getElementById('escaner');
var escanerMsg = document.getElementById('escanerMsg');

function avisoEscaner(texto, ok) {
    escanerMsg.textContent = texto;
    escanerMsg.style.color = ok ? '#198754' : '#dc3545';
}

escaner.addEventListener('keydown', function (ev) {
    if (ev.key !== 'Enter') return;
    ev.preventDefault();
    var codigo = escaner.value.trim().toLowerCase();
    escaner.value = '';
    if (!codigo) return;
    var p = porCodigo[codigo];
    if (!p) { avisoEscaner('No existe ningún producto con el código "' + codigo + '".', false); return; }

    var fila = null;
    tbody.querySelectorAll('tr').forEach(function (tr) {
        if (!fila && tr.querySelector('.prod-id').value === String(p.id)) fila = tr;
    });
    if (fila) {
        var c = fila.querySelector('.cant');
        c.value = (parseInt(c.value, 10) || 0) + 1;
    } else {
        tbody.querySelectorAll('tr').forEach(function (tr) {
            if (!fila && !tr.querySelector('.prod-id').value && !tr.querySelector('.cant').value) fila = tr;
        });
        if (!fila) fila = nuevaFila();
        fila.querySelector('.prod-txt').value = p.codigo + ' · ' + p.nombre;
        fila.querySelector('.prod-id').value = p.id;
        fila.querySelector('.prod-txt').setCustomValidity('');
        fila.querySelector('.cant').value = 1;
        if (esVenta() || parseFloat(fila.querySelector('.precio').value) === 0) fila.querySelector('.precio').value = p.precio;
    }
    recalcular();
    avisoEscaner('Agregado: ' + p.nombre, true);
});

if (FILAS_OLD.length) { FILAS_OLD.forEach(nuevaFila); } else { nuevaFila(); nuevaFila(); nuevaFila(); }
ajustarTipo();

document.getElementById('formFactura').addEventListener('submit', function (ev) {
    var filas = Array.prototype.filter.call(tbody.querySelectorAll('tr'), function (tr) { return tr.querySelector('.prod-id').value; });
    if (!filas.length) { ev.preventDefault(); alert('Agregá al menos un producto a la factura.'); return; }
    var incompleta = false;
    tbody.querySelectorAll('tr').forEach(function (tr) {
        var tieneProd = tr.querySelector('.prod-id').value;
        var tieneCant = tr.querySelector('.cant').value;
        if (!tieneProd && !tieneCant) { tr.remove(); }
        else if (!tieneProd || !tieneCant) { incompleta = true; }
    });
    if (incompleta) { ev.preventDefault(); alert('Hay una fila incompleta: completá el producto y la cantidad, o quitala con la ×.'); }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../js/theme-toggle.js"></script>
</body>
</html>