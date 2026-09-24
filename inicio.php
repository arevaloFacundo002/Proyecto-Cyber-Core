 <?php
// Si NO hay usuario logueado → al login
require_once "auth/auth.php";

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

<link href="css/theme.css" rel="stylesheet">

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
}

/* HEADER */
header {
    background: var(--cc-superficie-2);
    padding: 20px 50px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid rgba(0, 234, 255, 0.3);
    box-shadow: 0 0 15px rgba(0, 234, 255, 0.2);
}

.logo {
    font-size: 28px;
    font-weight: bold;
    color: var(--cc-primario);
    letter-spacing: 2px;
    text-shadow: 0 0 8px rgba(0, 234, 255, 0.6);
}

.user {
    font-size: 18px;
    color: var(--cc-texto);
}

.logout {
    color: var(--cc-peligro);
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
    color: var(--cc-primario);
    text-shadow: 0 0 12px rgba(0, 234, 255, 0.5);
}

/* GRID */
.grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 35px;
    margin-top: 40px;
}

.card {
    background: var(--cc-superficie);
    border: 1px solid var(--cc-borde);
    padding: 25px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 0 15px rgba(0,0,0,0.35);
    transition: 0.3s;
    color: var(--cc-texto);
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0 20px rgba(0, 234, 255, 0.35);
    border-color: var(--cc-primario);
}

.card h2 {
    margin-bottom: 15px;
    color: var(--cc-texto);
}

.card a {
    background: var(--cc-primario);
    color: var(--cc-texto-sobre-primario);
    padding: 12px 20px;
    border-radius: 20px;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
    margin-top: 15px;
    transition: 0.3s;
}

.card a:hover {
    background: var(--cc-primario-hover);
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
            <h2>Tablas maestras</h2>
            <p>Altas, bajas y modificaciones.</p>
            <a href="views/panel_tablas.php">Gestionar</a>
        </div>

        <div class="card">
            <h2>Productos y Proveedores</h2>
            <p>Entradas del sistema.</p>
            <a href="views/panel_inputs.php">Gestionar</a>
        </div>

        <div class="card">
            <h2>Movimientos</h2>
            <p>Historial de movimientos de productos.</p>
            <a href="views/movimientos/listarMovimiento.php">Ver</a>
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

<script src="js/theme-toggle.js"></script>

</body>
</html>