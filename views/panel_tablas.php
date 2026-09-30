 <?php
require_once '../auth/auth.php';

$ruta_raiz = '../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Tablas Maestras - CyberCore</title>

    <link href="../css/theme.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
        }

        .subheader {
            background: var(--cc-superficie-2);
            padding: 16px 50px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            border-bottom: 1px solid var(--cc-borde);
        }

        .subheader a {
            background: var(--cc-primario);
            padding: 10px 18px;
            border-radius: 20px;
            color: var(--cc-texto-sobre-primario);
            font-weight: bold;
            text-decoration: none;
            transition: 0.2s;
        }

        .subheader a:hover {
            background: var(--cc-primario-hover);
        }

        .container {
            padding: 40px 50px;
        }

        h1 {
            text-align: center;
            color: var(--cc-primario);
            text-shadow: 0 0 12px rgba(0, 234, 255, 0.4);
            margin-bottom: 4px;
        }

        .subtitulo {
            text-align: center;
            color: var(--cc-texto-secundario);
            margin-bottom: 40px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
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

        .card .icono {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .card h2 {
            font-size: 19px;
            margin-bottom: 10px;
        }

        .card p {
            color: var(--cc-texto-secundario);
            font-size: 14px;
            min-height: 42px;
        }

        .card a {
            background: var(--cc-primario);
            padding: 10px 20px;
            border-radius: 20px;
            color: var(--cc-texto-sobre-primario);
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            margin-top: 12px;
            transition: 0.2s;
        }

        .card a:hover {
            background: var(--cc-primario-hover);
        }

        @media (max-width: 1000px) {
            .grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 650px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

</head>

<body>

<?php require __DIR__ . '/../includes/header_admin.php'; ?>

<div class="subheader">
    <a href="../inicio.php">← Volver al Panel</a>
</div>

<div class="container">

    <h1>📚 Panel de Tablas Maestras</h1>
    <p class="subtitulo">Administración de datos base del sistema</p>

    <div class="grid">

        <div class="card">
            <div class="icono">📂</div>
            <h2>Categorías</h2>
            <p>Gestión de categorías y subcategorías de productos.</p>
            <a href="tablas_maestras/categorias/listarCategoria.php">Administrar</a>
        </div>

        <div class="card">
            <div class="icono">🔄</div>
            <h2>Conceptos de Movimiento</h2>
            <p>Gestión de entradas, salidas y ajustes de stock.</p>
            <a href="tablas_maestras/conceptoMovimientos/listarConcepto.php">Administrar</a>
        </div>

        <div class="card">
            <div class="icono">🏷️</div>
            <h2>Marcas</h2>
            <p>Gestión de fabricantes y marcas comerciales.</p>
            <a href="tablas_maestras/marcas/listarMarca.php">Administrar</a>
        </div>

        <div class="card">
            <div class="icono">💳</div>
            <h2>Métodos de Pago</h2>
            <p>Administración de medios de pago habilitados.</p>
            <a href="tablas_maestras/metodosPago/listarMetodo.php">Administrar</a>
        </div>

        <div class="card">
            <div class="icono">🧾</div>
            <h2>Condiciones IVA</h2>
            <p>Configuración de condiciones fiscales.</p>
            <a href="tablas_maestras/CondicionIva/listarCondicion.php">Administrar</a>
        </div>

        <div class="card">
            <div class="icono">💻</div>
            <h2>Modelos de Productos</h2>
            <p>Gestión de modelos asociados a las marcas.</p>
            <a href="tablas_maestras/modelosProducto/listarModelo.php">Administrar</a>
        </div>

        <div class="card">
            <div class="icono">🗺️</div>
            <h2>Provincias</h2>
            <p>Gestión de provincias argentinas.</p>
            <a href="tablas_maestras/provincias/listarProvincia.php">Administrar</a>
        </div>

        <div class="card">
            <div class="icono">📍</div>
            <h2>Localidades</h2>
            <p>Gestión de ciudades y localidades.</p>
            <a href="tablas_maestras/localidades/listarLocalidad.php">Administrar</a>
        </div>

        <div class="card">
            <div class="icono">👤</div>
            <h2>Perfiles</h2>
            <p>Administración de roles y permisos.</p>
            <a href="tablas_maestras/perfiles/listarPerfil.php">Administrar</a>
        </div>

        <div class="card">
            <div class="icono">📲</div>
            <h2>Tipos de Contactos</h2>
            <p>Gestión de los tipos de contacto que se tiene con los usuarios.</p>
            <a href="tablas_maestras/tiposContacto/listarTipoContacto.php">Administrar</a>
        </div>

    </div>

</div>

<script src="../js/theme-toggle.js"></script>

</body>
</html>