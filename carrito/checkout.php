<?php
session_start();
require_once '../models/Pedido.php';

// Hay que estar logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php?msg=Debes iniciar sesión");
    exit;
}

$rol = $_SESSION['rol'] ?? null;

// Admin y empleados no compran desde la tienda
if ($rol === 'administrador' || $rol === 'empleado') {
    header("Location: ../inicio.php");
    exit;
}

$carrito = $_SESSION['carrito'] ?? [];
if (empty($carrito)) {
    header("Location: carrito.php");
    exit;
}

$pedidos = new Pedido();
$datos   = $pedidos->prepararCheckout((int) $_SESSION['id_usuario'], $carrito);

// Token para evitar envíos falsos del formulario
if (empty($_SESSION['csrf_checkout'])) {
    $_SESSION['csrf_checkout'] = bin2hex(random_bytes(16));
}

// Mensaje de error y datos ya escritos que deja procesar_pedido.php (si hubo algún problema)
$errorPedido = $_SESSION['checkout_error'] ?? null;
$old         = $_SESSION['checkout_old'] ?? [];
unset($_SESSION['checkout_error'], $_SESSION['checkout_old']);

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function plata($valor)
{
    return '$' . number_format((float) $valor, 2, ',', '.');
}

// Devuelve lo que la persona había escrito antes de que el formulario volviera con un error.
function old($campo, $porDefecto = '')
{
    global $old;
    return $old[$campo] ?? $porDefecto;
}

$hayError  = isset($datos['error']);
$esNuevo   = !$hayError && !empty($datos['es_nuevo']);

$puedeConfirmar = !$hayError
    && empty($datos['problemas'])
    && !empty($datos['metodos']);

// Sugerencias de provincia/localidad (solo si hay que pedir la dirección)
$ubicaciones = $esNuevo ? $pedidos->listarUbicaciones() : ['provincias' => [], 'localidades' => []];

$recibeQuien = old('recibe_quien', 'yo');
$tipoLugar   = old('tipo_lugar', 'casa');
$metodoSel   = (int) old('metodo_pago', !empty($datos['metodos']) ? $datos['metodos'][0]['id_metodo_pago'] : 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Finalizar compra - CyberCore</title>

<link href="../css/theme.css" rel="stylesheet">
<link href="../css/tienda.css" rel="stylesheet">
</head>
<body>

<header class="tienda-header">
    <div class="logo">CYBERCORE</div>
    <nav>
        <a href="../home.php">Inicio</a>
        <a href="carrito.php">🛒 Carrito</a>
        <a class="logout" href="../logout.php">Cerrar sesión</a>
    </nav>
</header>

<div class="tienda-container">

    <h1>Finalizar compra</h1>

    <?php if ($errorPedido): ?>
        <div class="aviso"><?= e($errorPedido) ?></div>
    <?php endif; ?>

    <?php if ($hayError): ?>

        <div class="caja centrado">
            <p class="texto-suave">No hay productos válidos en tu carrito.</p>
            <a class="btn-secundario" href="carrito.php">← Volver al carrito</a>
        </div>

    <?php else: ?>

        <?php foreach ($datos['problemas'] as $problema): ?>
            <div class="aviso"><?= e($problema) ?></div>
        <?php endforeach; ?>

        <?php if (!empty($datos['problemas'])): ?>
            <p class="centrado">
                <a class="btn-secundario" href="carrito.php">← Ajustar el carrito</a>
            </p>
        <?php endif; ?>

        <!-- PRODUCTOS -->
        <div class="caja">
            <h2>Tu pedido</h2>
            <table class="tabla-compra">
                <tr>
                    <th>Producto</th>
                    <th class="num">Precio</th>
                    <th class="num">Cant.</th>
                    <th class="num">Subtotal</th>
                </tr>
                <?php foreach ($datos['lineas'] as $l): ?>
                <tr>
                    <td>
                        <img src="../img/<?= e($l['imagen']) ?>" alt="">
                        <?= e($l['nombre']) ?>
                    </td>
                    <td class="num"><?= plata($l['precio']) ?></td>
                    <td class="num"><?= (int) $l['cantidad'] ?></td>
                    <td class="num"><?= plata($l['subtotal']) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3" class="num texto-suave">Subtotal</td>
                    <td class="num"><?= plata($datos['subtotal']) ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="num texto-suave">
                        Envío<?= $esNuevo ? ' estimado' : '' ?>
                        <?php if ($datos['tarifa']): ?>
                            (<?= e($datos['tarifa']['nombre_servicio']) ?> · <?= e($datos['tarifa']['proveedor_servicio']) ?>)
                        <?php endif; ?>
                        <?php if ($esNuevo): ?>
                            <br><small>El costo final se ajusta según tu localidad.</small>
                        <?php endif; ?>
                    </td>
                    <td class="num"><?= plata($datos['envio']) ?></td>
                </tr>
                <tr class="fila-total">
                    <td colspan="3" class="num">Total</td>
                    <td class="num"><?= plata($datos['total']) ?></td>
                </tr>
            </table>
        </div>

        <form action="procesar_pedido.php" method="POST" id="form-checkout">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_checkout']) ?>">

            <?php if ($esNuevo): ?>

                <!-- DATOS PERSONALES (solo la primera compra) -->
                <div class="caja">
                    <h2>Tus datos</h2>
                    <p class="texto-suave">Los pedimos una sola vez. La próxima compra ya quedan guardados.</p>

                    <div class="fila-campos">
                        <div>
                            <label class="texto-suave">Nombre</label>
                            <input type="text" name="nombre" class="campo-texto" maxlength="100"
                                   value="<?= e(old('nombre')) ?>" placeholder="Ej: Ana" required>
                        </div>
                        <div>
                            <label class="texto-suave">Apellido</label>
                            <input type="text" name="apellido" class="campo-texto" maxlength="200"
                                   value="<?= e(old('apellido')) ?>" placeholder="Ej: Gómez" required>
                        </div>
                    </div>

                    <div class="fila-campos">
                        <div>
                            <label class="texto-suave">Teléfono de contacto</label>
                            <input type="text" name="telefono" class="campo-texto" maxlength="100"
                                   value="<?= e(old('telefono')) ?>" placeholder="Ej: 3704-123456" required>
                        </div>
                        <div>
                            <label class="texto-suave">CUIL / CUIT</label>
                            <input type="text" name="cuil" class="campo-texto" maxlength="20"
                                   value="<?= e(old('cuil')) ?>" placeholder="Con o sin guiones" required>
                        </div>
                    </div>
                </div>

                <!-- DIRECCIÓN (solo la primera compra) -->
                <div class="caja">
                    <h2>¿Dónde lo recibís?</h2>

                    <div class="fila-campos fila-campos-calle">
                        <div>
                            <label class="texto-suave">Calle</label>
                            <input type="text" name="calle" class="campo-texto" maxlength="150"
                                   value="<?= e(old('calle')) ?>" placeholder="Ej: San Martín" required>
                        </div>
                        <div>
                            <label class="texto-suave">Número</label>
                            <input type="text" name="numero" class="campo-texto" maxlength="10"
                                   value="<?= e(old('numero')) ?>" placeholder="Ej: 123 o S/N" required>
                        </div>
                    </div>

                    <label class="texto-suave" style="display:block; margin-top: 10px;">Barrio (opcional)</label>
                    <input type="text" name="barrio" class="campo-texto" maxlength="100"
                           value="<?= e(old('barrio')) ?>" placeholder="Ej: Centro">

                    <div class="fila-campos">
                        <div>
                            <label class="texto-suave">Provincia</label>
                            <input type="text" name="provincia" id="provincia" class="campo-texto"
                                   list="lista-provincias" maxlength="150" autocomplete="off"
                                   value="<?= e(old('provincia')) ?>" placeholder="Escribila o elegila" required>
                            <datalist id="lista-provincias">
                                <?php foreach ($ubicaciones['provincias'] as $prov): ?>
                                    <option value="<?= e($prov) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div>
                            <label class="texto-suave">Localidad</label>
                            <input type="text" name="localidad" id="localidad" class="campo-texto"
                                   list="lista-localidades" maxlength="150" autocomplete="off"
                                   value="<?= e(old('localidad')) ?>" placeholder="Escribila o elegila" required>
                            <datalist id="lista-localidades"></datalist>
                        </div>
                    </div>

                    <div class="fila-campos">
                        <div>
                            <label class="texto-suave">Código postal (opcional)</label>
                            <input type="text" name="codigo_postal" class="campo-texto" maxlength="10"
                                   value="<?= e(old('codigo_postal')) ?>" placeholder="Ej: 3600">
                        </div>
                        <div></div>
                    </div>

                    <p class="texto-suave" style="margin-bottom:0;">
                        Si tu provincia o localidad no aparece en la lista, <strong>escribila igual</strong>:
                        la agregamos automáticamente.
                    </p>
                </div>

            <?php else: ?>

                <!-- ENTREGA (cliente que ya compró antes) -->
                <div class="caja">
                    <h2>Entrega</h2>
                    <p>
                        <strong><?= e($datos['cliente']['nombre'] . ' ' . $datos['cliente']['apellido']) ?></strong><br>
                        <?= e($datos['direccion']) ?>
                    </p>
                    <p class="texto-suave">
                        Llegada estimada: aproximadamente <?= (int) $datos['dias'] ?> día(s) desde la confirmación.
                    </p>
                </div>

            <?php endif; ?>

            <!-- QUIÉN RECIBE -->
            <div class="caja">
                <h2>¿Quién recibe el pedido?</h2>

                <label class="opcion-pago">
                    <input type="radio" name="recibe_quien" value="yo" <?= $recibeQuien !== 'otra_persona' ? 'checked' : '' ?>>
                    <strong>Yo</strong>
                </label>

                <label class="opcion-pago">
                    <input type="radio" name="recibe_quien" value="otra_persona" <?= $recibeQuien === 'otra_persona' ? 'checked' : '' ?>>
                    <strong>Otra persona</strong>
                </label>

                <div id="bloque-otra-persona" style="display:none; margin-top: 14px;">
                    <label class="texto-suave">Nombre y apellido de quién recibe</label>
                    <input type="text" name="recibe_nombre" class="campo-texto"
                           value="<?= e(old('recibe_nombre')) ?>" placeholder="Ej: María Pérez">

                    <label class="texto-suave" style="margin-top: 10px; display:block;">Teléfono de contacto</label>
                    <input type="text" name="recibe_telefono" class="campo-texto"
                           value="<?= e(old('recibe_telefono')) ?>" placeholder="Ej: 3704-1234567">
                </div>
            </div>

            <!-- TIPO DE LUGAR -->
            <div class="caja">
                <h2>¿Qué tipo de lugar es?</h2>

                <?php
                $lugares = [
                    'casa'         => '🏠 Casa',
                    'departamento' => '🏢 Departamento',
                    'trabajo'      => '💼 Trabajo',
                    'local'        => '🏪 Local comercial',
                    'otro'         => '📍 Otro',
                ];
                foreach ($lugares as $valor => $texto): ?>
                    <label class="opcion-pago">
                        <input type="radio" name="tipo_lugar" value="<?= e($valor) ?>" <?= $tipoLugar === $valor ? 'checked' : '' ?>>
                        <strong><?= e($texto) ?></strong>
                    </label>
                <?php endforeach; ?>

                <div id="bloque-piso" style="display:none; margin-top: 14px;">
                    <label class="texto-suave">Piso / Depto / Torre</label>
                    <input type="text" name="piso_depto_torre" class="campo-texto"
                           value="<?= e(old('piso_depto_torre')) ?>" placeholder="Ej: Piso 3, Depto B, Torre 2">
                </div>

                <label class="texto-suave" style="margin-top: 14px; display:block;">
                    Referencia para encontrar el lugar (opcional)
                </label>
                <textarea name="referencia" class="campo-texto" rows="2"
                          placeholder="Ej: Casa con portón negro, al lado del kiosco"><?= e(old('referencia')) ?></textarea>
            </div>

            <!-- PAGO -->
            <div class="caja">
                <h2>Método de pago</h2>

                <?php if (empty($datos['metodos'])): ?>
                    <p class="texto-suave">No hay métodos de pago disponibles por el momento.</p>
                <?php else: ?>
                    <?php foreach ($datos['metodos'] as $m): ?>
                        <label class="opcion-pago">
                            <input type="radio" name="metodo_pago"
                                   value="<?= (int) $m['id_metodo_pago'] ?>"
                                   <?= $metodoSel === (int) $m['id_metodo_pago'] ? 'checked' : '' ?> required>
                            <strong><?= e($m['nombre']) ?></strong>
                            <small><?= e($m['descripcion']) ?></small>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>

                <p class="texto-suave">
                    Tu pedido queda registrado y el pago figura como <strong>pendiente</strong>
                    hasta que lo confirmemos.
                </p>
            </div>

            <div class="acciones">
                <a class="btn-secundario" href="carrito.php">← Volver al carrito</a>
                <button type="submit" class="btn-principal" <?= $puedeConfirmar ? '' : 'disabled' ?>>
                    Confirmar pedido
                </button>
            </div>
        </form>

    <?php endif; ?>

</div>

<script>
// Muestra u oculta los campos extra según lo que esté elegido
function sincronizar() {
    var quien = document.querySelector('input[name="recibe_quien"]:checked');
    var lugar = document.querySelector('input[name="tipo_lugar"]:checked');

    var bloqueOtra = document.getElementById('bloque-otra-persona');
    var bloquePiso = document.getElementById('bloque-piso');

    if (bloqueOtra) bloqueOtra.style.display = (quien && quien.value === 'otra_persona') ? 'block' : 'none';
    if (bloquePiso) bloquePiso.style.display = (lugar && lugar.value === 'departamento') ? 'block' : 'none';
}

document.querySelectorAll('input[name="recibe_quien"], input[name="tipo_lugar"]').forEach(function (r) {
    r.addEventListener('change', sincronizar);
});
sincronizar();

<?php if ($esNuevo): ?>
// Sugerencias de localidad según la provincia que se esté escribiendo (solo sugerencias: se puede escribir cualquiera)
var LOCALIDADES = <?= json_encode($ubicaciones['localidades'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

function normalizar(t) {
    return (t || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, ' ').trim();
}

function cargarLocalidades() {
    var lista = document.getElementById('lista-localidades');
    lista.innerHTML = '';

    var buscada = normalizar(document.getElementById('provincia').value);

    Object.keys(LOCALIDADES).forEach(function (prov) {
        if (normalizar(prov) !== buscada) return;
        LOCALIDADES[prov].forEach(function (loc) {
            var op = document.createElement('option');
            op.value = loc;
            lista.appendChild(op);
        });
    });
}

document.getElementById('provincia').addEventListener('input', cargarLocalidades);
cargarLocalidades();
<?php endif; ?>
</script>

<script src="../js/theme-toggle.js"></script>

</body>
</html>