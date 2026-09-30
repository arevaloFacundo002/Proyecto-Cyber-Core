 <?php
require_once '../auth/auth.php';

$ruta_raiz = '../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Gestión de Entradas - CyberCore</title>

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
            grid-template-columns: repeat(2, 1fr);
            gap: 35px;
            max-width: 800px;
            margin: 0 auto;
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

    <h1>📦 Panel de Gestión de Entradas del Sistema</h1>
    <p class="subtitulo">Administración de productos y proveedores</p>

    <div class="grid">

        <div class="card">
            <div class="icono">👤</div>
            <h2>Proveedores</h2>
            <p>Gestión de Proveedores y contactos.</p>
            <a href="proveedores/listarProveedor.php">Administrar</a>
        </div>

        <div class="card">
            <div class="icono">💻</div>
            <h2>Productos</h2>
            <p>Gestión de entradas de productos.</p>
            <a href="productos/listarProducto.php">Administrar</a>
        </div>

    </div>

</div>

<script src="../js/theme-toggle.js"></script>

</body>
</html>