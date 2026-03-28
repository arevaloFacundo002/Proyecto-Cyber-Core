<?php
session_start();

// Solo admin y empleados pueden ver consultas
if (!isset($_SESSION['usuario']) || 
   ($_SESSION['rol'] != "admin" && $_SESSION['rol'] != "empleado")) {
    header("Location: ../home.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Consultas SQL - CyberCore</title>

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #0a0a0a, #0f1a20);
    color: white;
}

header {
    background: #000;
    padding: 20px 50px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #00eaff55;
    box-shadow: 0 0 15px #00eaff44;
}

.logo {
    font-size: 28px;
    font-weight: bold;
    color: #00eaff;
    text-shadow: 0 0 8px #00eaff;
}

.back {
    background: #00eaff;
    padding: 10px 18px;
    border-radius: 20px;
    color: black;
    font-weight: bold;
    text-decoration: none;
}

.back:hover {
    background: #009ac0;
}

.container {
    padding: 50px;
}

h1 {
    text-align: center;
    color: #00eaff;
    text-shadow: 0 0 12px #00eaffaa;
}

.grid {
    margin-top: 40px;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 35px;
}

.card {
    background: #111;
    padding: 25px;
    border-radius: 12px;
    text-align: center;
    border: 1px solid #00eaff33;
    transition: 0.3s;
    box-shadow: 0 0 15px #000;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0 20px #00eaff66;
}

.card h2 {
    margin-bottom: 12px;
    color: #00eaff;
}

.card a {
    background: #00eaff;
    padding: 12px 20px;
    border-radius: 20px;
    color: black;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
    margin-top: 15px;
}

.card a:hover {
    background: #009ac0;
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

</body>
</html>


