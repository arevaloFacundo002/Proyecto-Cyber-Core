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

// Mensaje de error que deja procesar_pedido.php (si lo hubo)
$errorPedido = $_SESSION['checkout_error'] ?? null;
unset($_SESSION['checkout_error']);

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function plata($valor)
{
    return '$' . number_format((float) $valor, 2, ',', '.');
}

$puedeConfirmar = !isset($datos['error'])
    && empty($datos['problemas'])
    && !empty($datos['metodos']);
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

    <?php if (isset($datos['error']) && $datos['error'] === 'sin_cliente'): ?>

        <div class="caja centrado">
            <h2>Falta completar tus datos</h2>
            <p class="texto-suave">
                Tu cuenta todavía no tiene datos de cliente (nombre, CUIL y dirección de entrega),
                y los necesitamos para armar el pedido y el envío.
                Pedile a un administrador que los complete.
            </p>
            <a class="btn-secundario" href="carrito.php">← Volver al carrito</a>
        </div>

    <?php elseif (isset($datos['error'])): ?>

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
                        Envío
                        <?php if ($datos['tarifa']): ?>
                            (<?= e($datos['tarifa']['nombre_servicio']) ?> · <?= e($datos['tarifa']['proveedor_servicio']) ?>)
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

        <!-- ENTREGA -->
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

        <!-- PAGO + CONFIRMAR -->
        <form action="procesar_pedido.php" method="POST">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_checkout']) ?>">

            <div class="caja">
                <h2>Método de pago</h2>

                <?php if (empty($datos['metodos'])): ?>
                    <p class="texto-suave">No hay métodos de pago disponibles por el momento.</p>
                <?php else: ?>
                    <?php foreach ($datos['metodos'] as $i => $m): ?>
                        <label class="opcion-pago">
                            <input type="radio" name="metodo_pago"
                                   value="<?= (int) $m['id_metodo_pago'] ?>"
                                   <?= $i === 0 ? 'checked' : '' ?> required>
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

<script src="../js/theme-toggle.js"></script>

</body>
</html>