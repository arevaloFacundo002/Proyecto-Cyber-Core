 <?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Solo admin y empleados pueden ver consultas
if (!isset($_SESSION['usuario']) ||
   ($_SESSION['rol'] != "administrador" && $_SESSION['rol'] != "empleado")) {
    header("Location: ../home.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consultas SQL - CyberCore</title>

<link href="../css/theme.css" rel="stylesheet">

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
}

header {
    background: var(--cc-superficie-2);
    padding: 20px 50px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--cc-borde);
}

.logo {
    font-size: 28px;
    font-weight: bold;
    color: var(--cc-primario);
    text-shadow: 0 0 8px rgba(0, 234, 255, 0.5);
}

.back {
    background: var(--cc-primario);
    padding: 10px 18px;
    border-radius: 20px;
    color: var(--cc-texto-sobre-primario);
    font-weight: bold;
    text-decoration: none;
    transition: 0.2s;
}

.back:hover {
    background: var(--cc-primario-hover);
}

.container {
    padding: 50px;
}

h1 {
    text-align: center;
    color: var(--cc-primario);
    text-shadow: 0 0 12px rgba(0, 234, 255, 0.4);
}

.grid {
    margin-top: 40px;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 35px;
}

.card {
    background: var(--cc-superficie);
    padding: 25px;
    border-radius: 12px;
    text-align: center;
    border: 1px solid var(--cc-borde);
    transition: 0.3s;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.25);
}

.card:hover {
    transform: translateY(-5px);
    border-color: var(--cc-primario);
    box-shadow: 0 0 20px rgba(0, 234, 255, 0.35);
}

.card h2 {
    margin-bottom: 12px;
    color: var(--cc-primario);
}

.card p {
    color: var(--cc-texto-secundario);
}

.card a {
    background: var(--cc-primario);
    padding: 12px 20px;
    border-radius: 20px;
    color: var(--cc-texto-sobre-primario);
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
    margin-top: 15px;
    transition: 0.2s;
}

.card a:hover {
    background: var(--cc-primario-hover);
}

@media (max-width: 900px) {
    .grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<header>
    <div class="logo">CYBERCORE - CONSULTAS</div>
    <a href="../inicio.php" class="back">⬅ Volver al Panel</a>
</header>

<div class="container">
    <h1>Consultas del Sistema</h1>

    <div class="grid">

        <div class="card">
            <h2>Consulta 1</h2>
            <p>Producto y proveedor con el ultimo precio de compra</p>
            <a href="consulta1.php">Ver consulta</a>
        </div>

        <div class="card">
            <h2>Consulta 2</h2>
            <p>Pedidos detallados por cliente y envío.</p>
            <a href="consulta2.php">Ver consulta</a>
        </div>

        <div class="card">
            <h2>Consulta 3</h2>
            <p>Top 5 productos más vendidos.</p>
            <a href="consulta3.php">Ver consulta</a>
        </div>

        <div class="card">
            <h2>Consulta 4</h2>
            <p>Clientes con más pedidos realizados.</p>
            <a href="consulta4.php">Ver consulta</a>
        </div>

        <div class="card">
            <h2>Consulta 5</h2>
            <p>Marcas con mayor cantidad de productos.</p>
            <a href="consulta5.php">Ver consulta</a>
        </div>

    </div>
</div>

<script src="../js/theme-toggle.js"></script>

</body>
</html>