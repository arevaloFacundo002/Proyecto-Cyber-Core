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

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: #0a0a0a;
    color: white;
}

header {
    background: #0a0a0a;
    padding: 18px 50px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #00eaff55;
}

.logo {
    font-size: 26px;
    color: #00eaff;
    font-weight: bold;
}

nav a {
    color: white;
    margin: 0 15px;
    text-decoration: none;
}

nav a:hover {
    color: #00eaff;
}

.container {
    max-width: 900px;
    margin: 40px auto;
    background: #111;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 0 20px #00eaff33;
}

h2 {
    text-align: center;
    color: #00eaff;
}

.envio-box {
    background: #1a1a1a;
    padding: 18px;
    border-radius: 10px;
    margin-bottom: 18px;
    border-left: 4px solid #00eaff;
}

.btn-next {
    background: #00eaff;
    padding: 12px 22px;
    border-radius: 20px;
    color: black;
    font-weight: bold;
    text-decoration: none;
    float: right;
    margin-top: 20px;
}

.btn-next:hover {
    background: #00c8d6;
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
</body>
</html>
