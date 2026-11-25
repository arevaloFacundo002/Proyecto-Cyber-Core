<?php
session_start();
include "../conexion.php";

// --------------------------------------
// VALIDAR QUE EL USUARIO ESTÉ LOGUEADO
// --------------------------------------
if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php?msg=Debes iniciar sesión");
    exit;
}

// --------------------------------------
// VALIDAR CARRITO NO VACÍO
// --------------------------------------
if (!isset($_SESSION['carrito']) || empty($_SESSION['carrito'])) {
    header("Location: ../carrito/carrito.php?msg=carrito_vacio");
    exit;
}

// --------------------------------------
// CONSULTAR MÉTODOS DE ENVÍO (tarifa_envios)
// --------------------------------------
$sql = "SELECT id_tarifa_envio, descripcion, empresa_transporte, costo_base, costo_extra FROM tarifa_envios";
$res = mysqli_query($conexion, $sql);

// --------------------------------------
// SI EL CLIENTE YA SELECCIONÓ UN MÉTODO
// --------------------------------------
$seleccion_previa = isset($_SESSION['metodo_envio']) ? $_SESSION['metodo_envio'] : null;

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Método de Envío - CyberCore</title>

<link href="../css/theme.css" rel="stylesheet">
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: var(--cc-fondo);
    color: var(--cc-texto);
}

header {
    background: var(--cc-superficie);
    padding: 18px 50px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--cc-borde);
}

.logo {
    font-size: 26px;
    color: var(--cc-primario);
    font-weight: bold;
}

nav a {
    color: var(--cc-texto);
    margin: 0 15px;
    text-decoration: none;
}

nav a:hover {
    color: var(--cc-primario);
}

.container {
    max-width: 900px;
    margin: 40px auto;
    background: var(--cc-superficie);
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 0 20px rgba(0,0,0,.25);
}

h2 {
    text-align: center;
    color: var(--cc-primario);
}

.envio-box {
    background: var(--cc-superficie-2);
    padding: 18px;
    border-radius: 10px;
    margin-bottom: 18px;
    border-left: 4px solid var(--cc-primario);
}

.btn-next {
    background: var(--cc-primario);
    padding: 12px 22px;
    border-radius: 20px;
    color: var(--cc-texto-sobre-primario);
    font-weight: bold;
    text-decoration: none;
    float: right;
    margin-top: 20px;
}

.btn-next:hover {
    background: var(--cc-primario-hover);
}

input[type=radio] {
    transform: scale(1.3);
    margin-right: 10px;
}
</style>

</head>
<body>

<header>
    <div class="logo">CYBERCORE</div>
    <nav>
        <a href="../home.php">Inicio</a>
        <a href="../carrito/carrito.php">Carrito</a>
        <a href="../logout.php">Cerrar sesión</a>
    </nav>
</header>

<div class="container">

<h2>🚚 Seleccionar método de envío</h2>

<form method="POST" action="procesar_envio.php">

<?php while ($e = mysqli_fetch_assoc($res)): ?>

    <label class="envio-box">
        <input type="radio" name="metodo_envio" value="<?php echo $e['id_tarifa_envio']; ?>"
            <?php if ($seleccion_previa == $e['id_tarifa_envio']) echo "checked"; ?> >
        
        <strong><?php echo $e['descripcion']; ?></strong><br>
        Empresa: <?php echo $e['empresa_transporte']; ?><br>
        Costo base: $<?php echo number_format($e['costo_base'], 2); ?><br>
        Costo extra: $<?php echo number_format($e['costo_extra'], 2); ?>
    </label>

<?php endwhile; ?>

<button type="submit" class="btn-next">Continuar a método de pago →</button>

</form>

</div>
<script src="../js/theme-toggle.js"></script>
</body>
</html>