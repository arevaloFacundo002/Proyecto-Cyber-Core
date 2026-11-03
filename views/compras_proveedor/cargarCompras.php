<?php
require_once __DIR__ . '/../../auth/staff.php';
require_once __DIR__ . '/../../models/inputs/CompraProveedor.php';

$modelo = new CompraProveedor();
$proveedores = $modelo->proveedoresActivos();

$id_compra = (int) ($_GET['id'] ?? 0);
$cab = ['id_proveedor' => 0, 'comprobante' => '', 'fecha' => date('Y-m-d'), 'bonificacion' => 0];
$lineas = [];

if (isset($_SESSION['compra_old'])) {
    // Hubo un error al guardar: se vuelve a mostrar exactamente lo que había cargado
    $old = $_SESSION['compra_old'];
    unset($_SESSION['compra_old']);
    $cab = $old['cab'];
    $lineas = $old['lineas'];
    $id_compra = (int) $old['id_compra'];

} elseif ($id_compra > 0) {
    // Edición de un borrador existente
    $compra = $modelo->obtener($id_compra);

    if (!$compra || $compra['estado'] !== 'borrador') {
        $_SESSION['flash'] = ['tipo' => 'danger', 'texto' => 'Solo se pueden editar compras en borrador.'];
        header('Location: listarCompra.php');
        exit();
    }

    $cab = [
        'id_proveedor' => (int) $compra['rela_id_proveedores'],
        'comprobante' => $compra['comprobante_compra'],
        'fecha' => $compra['fecha_compra'],
        'bonificacion' => (float) $compra['bonificacion'],
    ];
    foreach ($compra['lineas'] as $l) {
        $lineas[] = [
            'id_producto' => (int) $l['rela_id_productos'],
            'cantidad' => (int) $l['cantidad'],
            'costo' => (float) $l['costo_unitario'],
        ];
    }
}

$lineasJs = $modelo->enriquecerLineas($lineas);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id_compra ? 'Editar borrador de compra' : 'Cargar compra' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-3 mb-5">

    <a href="listarCompra.php" class="btn btn-secondary btn-sm mb-3">← Volver</a>

    <h3 class="fw-bold mb-3">
        <?= $id_compra ? 'Editar borrador de compra #' . $id_compra : 'Cargar compra a proveedor' ?>
    </h3>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['tipo']) ?>">
            <?= htmlspecialchars($flash['texto']) ?>
        </div>
    <?php endif; ?>

    <form id="form-compra" method="POST" action="../../controllers/inputsControllers/CompraProveedorController.php">
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="id_compra" value="<?= $id_compra ?>">

        <!-- CABECERA DE LA FACTURA -->
        <div class="card shadow border-0 mb-3">
            <div class="card-header fw-bold">1. Datos de la factura</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Proveedor</label>
                        <select name="id_proveedor" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($proveedores as $p): ?>
                                <option value="<?= $p['id_proveedor'] ?>"
                                    <?= (int) $cab['id_proveedor'] === (int) $p['id_proveedor'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['razon_social']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">N° de comprobante</label>
                        <input type="text" name="comprobante" class="form-control" maxlength="100" required
                               placeholder="Ej: FAC-A-0001-00004521"
                               value="<?= htmlspecialchars($cab['comprobante']) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Fecha</label>
                        <input type="date" name="fecha" class="form-control" required
                               value="<?= htmlspecialchars($cab['fecha']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Bonificación ($)</label>
                        <input type="number" id="bonificacion" name="bonificacion" class="form-control"
                               min="0" step="0.01" value="<?= htmlspecialchars((string) $cab['bonificacion']) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- PRODUCTOS -->
        <div class="card shadow border-0 mb-3">
            <div class="card-header fw-bold">2. Productos de la factura</div>
            <div class="card-body">

                <label class="form-label fw-semibold">
                    Agregar producto: escaneá el código de barras o escribí el nombre y apretá Enter
                </label>
                <div class="position-relative mb-1">
                    <input type="text" id="buscador" class="form-control form-control-lg"
                           placeholder="Código o nombre del producto..." autocomplete="off" autofocus>
                    <div id="sugerencias" class="list-group position-absolute w-100 shadow"
                         style="z-index: 1000; display: none;"></div>
                </div>
                <div id="aviso-busqueda" class="small text-danger mb-3" style="min-height: 1.3em;"></div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th class="text-center">Stock actual</th>
                                <th style="width:110px;">Cantidad</th>
                                <th style="width:140px;">Costo unit. ($)</th>
                                <th class="text-end" style="width:130px;">Subtotal</th>
                                <th style="width:50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="lineas"></tbody>
                    </table>
                </div>

                <div class="row justify-content-end">
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between"><span>Subtotal</span><span id="t-subtotal">$ 0,00</span></div>
                        <div class="d-flex justify-content-between"><span>Bonificación</span><span id="t-boni">- $ 0,00</span></div>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between fw-bold fs-5"><span>Total</span><span id="t-total">$ 0,00</span></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACCIONES -->
        <div class="d-flex gap-2 justify-content-end">
            <button type="submit" name="guardar" value="borrador" class="btn btn-secondary">
                Guardar borrador
            </button>
            <button type="submit" name="guardar" value="confirmar" class="btn btn-success">
                Confirmar compra
            </button>
        </div>
        <p class="text-muted small text-end mt-2 mb-0">
            El borrador no modifica el stock. Al confirmar, cada producto ingresa al stock y queda registrado en Movimientos.
        </p>
    </form>
</div>

<script>
    // Líneas iniciales (vacías si es una compra nueva)
    const lineas = <?= json_encode($lineasJs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    const form = document.getElementById('form-compra');
    const tbody = document.getElementById('lineas');
    const buscador = document.getElementById('buscador');
    const sugerencias = document.getElementById('sugerencias');
    const aviso = document.getElementById('aviso-busqueda');
    const inputBoni = document.getElementById('bonificacion');

    const dinero = n => '$ ' + n.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function esc(texto) {
        const d = document.createElement('div');
        d.textContent = texto ?? '';
        return d.innerHTML;
    }

    function subtotalLinea(l) {
        return (parseFloat(l.cantidad) || 0) * (parseFloat(l.costo) || 0);
    }

    function calcularTotales() {
        const subtotal = lineas.reduce((acc, l) => acc + subtotalLinea(l), 0);
        const boni = parseFloat(inputBoni.value) || 0;
        const total = subtotal - boni;

        document.getElementById('t-subtotal').textContent = dinero(subtotal);
        document.getElementById('t-boni').textContent = '- ' + dinero(boni);
        const t = document.getElementById('t-total');
        t.textContent = dinero(total);
        t.className = total < 0 ? 'text-danger' : '';
        return total;
    }

    function render() {
        if (lineas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">' +
                'Todavía no agregaste productos. Escaneá o buscá un código arriba.</td></tr>';
        } else {
            tbody.innerHTML = lineas.map((l, i) => `
                <tr>
                    <td>${esc(l.codigo)}</td>
                    <td>${esc(l.nombre)}<input type="hidden" name="producto_id[]" value="${Number(l.id_producto)}"></td>
                    <td class="text-center">${Number(l.stock)}</td>
                    <td><input type="number" name="cantidad[]" class="form-control form-control-sm"
                               min="1" step="1" required value="${esc(String(l.cantidad))}"
                               data-i="${i}" data-campo="cantidad"></td>
                    <td><input type="number" name="costo[]" class="form-control form-control-sm"
                               min="0" step="0.01" required value="${esc(String(l.costo))}"
                               data-i="${i}" data-campo="costo"></td>
                    <td class="text-end" id="sub-${i}">${dinero(subtotalLinea(l))}</td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger" data-quitar="${i}"
                                title="Quitar">✕</button></td>
                </tr>`).join('');
        }
        calcularTotales();
    }

    // Editar cantidad/costo: se actualiza el subtotal sin redibujar (así no se pierde el foco)
    tbody.addEventListener('input', e => {
        const i = e.target.dataset.i;
        if (i === undefined) return;
        lineas[i][e.target.dataset.campo] = e.target.value;
        document.getElementById('sub-' + i).textContent = dinero(subtotalLinea(lineas[i]));
        calcularTotales();
    });

    tbody.addEventListener('click', e => {
        const q = e.target.dataset.quitar;
        if (q === undefined) return;
        lineas.splice(Number(q), 1);
        render();
        buscador.focus();
    });

    inputBoni.addEventListener('input', calcularTotales);

    // ---- Agregar productos ----
    function agregar(p) {
        const id = Number(p.id_producto);
        const existente = lineas.find(l => Number(l.id_producto) === id);

        if (existente) {
            // Escanear dos veces el mismo producto suma una unidad
            existente.cantidad = (parseInt(existente.cantidad) || 0) + 1;
        } else {
            lineas.push({
                id_producto: id,
                codigo: p.codigo,
                nombre: p.nombre,
                stock: p.stock,
                cantidad: 1,
                costo: p.ultimo_costo !== null && p.ultimo_costo !== undefined ? p.ultimo_costo : ''
            });
        }

        render();
        buscador.value = '';
        ocultarSugerencias();
        aviso.textContent = '';
        buscador.focus();
    }

    async function buscar(q) {
        const r = await fetch('../../ajax/buscarProducto.php?q=' + encodeURIComponent(q));
        if (!r.ok) throw new Error('No se pudo buscar (¿sesión vencida?).');
        return r.json();
    }

    function ocultarSugerencias() {
        sugerencias.style.display = 'none';
        sugerencias.innerHTML = '';
    }

    function mostrarSugerencias(lista) {
        sugerencias.innerHTML = '';
        lista.forEach(p => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'list-group-item list-group-item-action';
            b.innerHTML = `<strong>${esc(p.codigo)}</strong> — ${esc(p.nombre)} <span class="text-muted">(stock ${Number(p.stock)})</span>`;
            b.addEventListener('click', () => agregar(p));
            sugerencias.appendChild(b);
        });
        sugerencias.style.display = lista.length ? 'block' : 'none';
    }

    let temporizador;
    buscador.addEventListener('input', () => {
        clearTimeout(temporizador);
        aviso.textContent = '';
        const q = buscador.value.trim();
        if (q.length < 2) { ocultarSugerencias(); return; }

        temporizador = setTimeout(async () => {
            try { mostrarSugerencias(await buscar(q)); }
            catch (err) { aviso.textContent = err.message; }
        }, 250);
    });

    buscador.addEventListener('keydown', async e => {
        if (e.key !== 'Enter') return;
        e.preventDefault();          // Enter acá NUNCA envía el formulario
        clearTimeout(temporizador);

        const q = buscador.value.trim();
        if (!q) return;

        try {
            const r = await buscar(q);
            if (r.length === 0) {
                aviso.textContent = 'No existe ningún producto activo con ese código o nombre.';
                ocultarSugerencias();
            } else if (r.length === 1 || r[0].exacto) {
                agregar(r[0]);       // código exacto (lector) o único resultado: se agrega solo
            } else {
                mostrarSugerencias(r);
            }
        } catch (err) {
            aviso.textContent = err.message;
        }
    });

    document.addEventListener('click', e => {
        if (!sugerencias.contains(e.target) && e.target !== buscador) ocultarSugerencias();
    });

    // ---- Envío del formulario ----
    // Enter en cualquier campo no debe guardar nada por accidente
    form.addEventListener('keydown', e => {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && e.target.type !== 'submit') {
            e.preventDefault();
        }
    });

    form.addEventListener('submit', e => {
        if (lineas.length === 0) {
            e.preventDefault();
            aviso.textContent = 'Agregá al menos un producto antes de guardar.';
            buscador.focus();
            return;
        }

        if (calcularTotales() < 0) {
            e.preventDefault();
            alert('La bonificación no puede ser mayor al subtotal de la factura.');
            return;
        }

        if (e.submitter && e.submitter.value === 'confirmar') {
            const unidades = lineas.reduce((acc, l) => acc + (parseInt(l.cantidad) || 0), 0);
            const msg = `Vas a ingresar ${lineas.length} producto(s) (${unidades} unidades) al stock. ` +
                        `Se generará un movimiento por cada línea. ¿Confirmar compra?`;
            if (!confirm(msg)) e.preventDefault();
        }
    });

    render();
</script>

</body>
</html>