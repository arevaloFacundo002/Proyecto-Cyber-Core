<?php
require_once '../auth/auth.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Entradas</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <style>

        body{
            background-color:#f4f4f4;
        }

        .titulo-panel{
            background:#16c5d8;
            color:white;
            padding:20px;
            border-radius:12px;
            margin-bottom:30px;
        }

        .card-maestra{
            border:none;
            border-radius:15px;
            transition:0.3s;
        }

        .card-maestra:hover{
            transform:translateY(-5px);
        }

        .btn-maestra{
            background:#16c5d8;
            color:white;
            font-weight:bold;
            border:none;
        }

        .btn-maestra:hover{
            background:#11b1c2;
            color:white;
        }

        .icono{
            font-size:40px;
        }
    
        .btn-volver{
            background:#6c757d;
            color:white;
            font-weight:bold;
            border:none;
        }

        .btn-volver:hover{
            background:#5c636a;
            color:white;
        }

    </style>

</head>

<body>
<div class="container mt-5">
    <div class="titulo-panel shadow-sm">

        <h2 class="mb-0">
            📚 Panel de Gestion de Entradas del sistema
        </h2>

        <small>
            Administración de productos y proveedores
        </small>

        </div>

        <a href="../inicio.php"
            class="btn btn-secondary">
                ← Volver
        </a><br><br>


    <div class="row g-4">

        <!-- Proveedores -->
        <div class="col-md-4">
            <div class="card card-maestra shadow h-100">
                <div class="card-body text-center">

                    <div class="icono mb-3">
                        👤
                    </div>

                    <h5>Proveedores</h5>

                    <p class="text-muted">
                        Gestión de Proveedores y contactos.
                    </p>

                    <a href="proveedores/listarProveedor.php"
                    class="btn btn-maestra">
                        Administrar
                    </a>

                </div>
            </div>
        </div>

        <!-- Productos -->
        <div class="col-md-4">
            <div class="card card-maestra shadow h-100">
                <div class="card-body text-center">

                    <div class="icono mb-3">
                        💻
                    </div>

                    <h5>Productos</h5>

                    <p class="text-muted">
                        Gestión de entradas de productos.
                    </p>

                    <a href="productos/listarProducto.php"
                    class="btn btn-maestra">
                        Administrar
                    </a>

                </div>
            </div>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
