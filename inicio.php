<?php
// Si NO hay usuario logueado → al login
require_once "auth.php";

// Si el usuario NO es admin ni empleado → a la tienda
if ($_SESSION['rol'] != "administrador" && $_SESSION['rol'] != "empleado") {
    header("Location: home.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>CyberCore - Panel</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Cache-Control" content="no-store" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #0a0a0a, #0f1a20);
    color: white;
}

/* HEADER */
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
    letter-spacing: 2px;
    text-shadow: 0 0 8px #00eaff;
}

.user {
    font-size: 18px;
    color: #fff;
}

.logout {
    color: #ff4b4b;
    margin-left: 10px;
    text-decoration: none;
    font-weight: bold;
}
.logout:hover {
    text-decoration: underline;
}

/* CONTENIDO */
.container {
    padding: 50px;
}

h1 {
    text-align: center;
    margin-bottom: 40px;
    color: #00eaff;
    text-shadow: 0 0 12px #00eaffaa;
}

/* GRID */
.grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 35px;
    margin-top: 40px;
}

.card {
    background: #111;
    padding: 25px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 0 15px #000;
    border: 1px solid #00eaff33;
    transition: 0.3s;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0 20px #00eaff66;
}

.card h2 {
    margin-bottom: 15px;
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
    transition: 0.3s;
}

.card a:hover {
    background: #009ac0;
}

/* RESPONSIVE */
@media (max-width: 900px) {
    .grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<header>
    <div class="logo">CYBERCORE PANEL</div>
    <div class="user">
        Hola, <b><?php echo $_SESSION['usuario']; ?></b>
        <a class="logout" href="logout.php">Cerrar sesión</a>
    </div>
</header>

<div class="container">
    <h1>Panel del Administrador</h1>

    <div class="grid">

        <div class="card">
            <h2>ABM Usuarios</h2>
            <p>Alta, baja y modificación del sistema.</p>
            <a href="usuarios/listar.php">Gestionar</a>
        </div>

        <div class="card">
            <h2>Consultas</h2>
            <p>Consultas SQL del sistema.</p>
            <a href="consultas/menu_consultas.php">Ver consultas</a>
        </div>

        <div class="card">
            <h2>Reporte de Usuarios</h2>
            <p>Generar archivo PDF con información del sistema.</p>
            <a href="reportes/reporte_usuarios.php">Ver reporte</a>
        </div>

        <div class="card">
            <h2>Volver a la tienda</h2>
            <p>Ir al catálogo de productos.</p>
            <a href="home.php">Ir</a>
        </div>

    </div>
</div>

</body>
</html>
