<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Tablas Maestras</title>

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
            📚 Panel de Tablas Maestras
        </h2>

        <small>
            Administración de datos base del sistema
        </small>

        </div>

        <a href="../inicio.php"
            class="btn btn-secondary">
                ← Volver
        </a><br><br>


    <div class="row g-4">

        <!-- Categorías -->
        <div class="col-md-4">
            <div class="card card-maestra shadow h-100">
                <div class="card-body text-center">

                    <div class="icono mb-3">
                        📂
                    </div>

                    <h5>Categorías</h5>

                    <p class="text-muted">
                        Gestión de categorías y subcategorías de productos.
                    </p>

                    <a href="tablas_maestras/categorias/listarCategoria.php"
                    class="btn btn-maestra">
                        Administrar
                    </a>

                </div>
            </div>
        </div>

        <!-- Conceptos -->
        <div class="col-md-4">
            <div class="card card-maestra shadow h-100">
                <div class="card-body text-center">

                    <div class="icono mb-3">
                        🔄
                    </div>

                    <h5>Conceptos de Movimiento</h5>

                    <p class="text-muted">
                        Gestión de entradas, salidas y ajustes de stock.
                    </p>

                    <a href="tablas_maestras/conceptoMovimientos/listarConcepto.php"
                    class="btn btn-maestra">
                        Administrar
                    </a>

                </div>
            </div>
        </div>

        <!-- Marcas -->
        <div class="col-md-4">
            <div class="card card-maestra shadow h-100">
                <div class="card-body text-center">

                    <div class="icono mb-3">
                        🏷️
                    </div>

                    <h5>Marcas</h5>

                    <p class="text-muted">
                        Gestión de fabricantes y marcas comerciales.
                    </p>

                    <a href="tablas_maestras/marcas/listarMarca.php"
                    class="btn btn-maestra">
                        Administrar
                    </a>

                </div>
            </div>
        </div>

        <!-- Métodos de pago -->
        <div class="col-md-4">
            <div class="card card-maestra shadow h-100">
                <div class="card-body text-center">

                    <div class="icono mb-3">
                        💳
                    </div>

                    <h5>Métodos de Pago</h5>

                    <p class="text-muted">
                        Administración de medios de pago habilitados.
                    </p>

                    <a href="tablas_maestras/metodosPago/listarMetodo.php"
                    class="btn btn-maestra">
                        Administrar
                    </a>

                </div>
            </div>
        </div>

        <!-- IVA -->
        <div class="col-md-4">
            <div class="card card-maestra shadow h-100">
                <div class="card-body text-center">

                    <div class="icono mb-3">
                        🧾
                    </div>

                    <h5>Condiciones IVA</h5>

                    <p class="text-muted">
                        Configuración de condiciones fiscales.
                    </p>

                    <a href="tablas_maestras/CondicionIva/listarCondicion.php"
                    class="btn btn-maestra">
                        Administrar
                    </a>

                </div>
            </div>
        </div>

        <!-- Modelos -->
        <div class="col-md-4">
            <div class="card card-maestra shadow h-100">
                <div class="card-body text-center">

                    <div class="icono mb-3">
                        💻
                    </div>

                    <h5>Modelos de Productos</h5>

                    <p class="text-muted">
                        Gestión de modelos asociados a las marcas.
                    </p>

                    <a href="tablas_maestras/modelosProducto/listarModelo.php"
                    class="btn btn-maestra">
                        Administrar
                    </a>

                </div>
            </div>
        </div>

        <!-- Provincias -->
        <div class="col-md-4">
            <div class="card card-maestra shadow h-100">
                <div class="card-body text-center">

                    <div class="icono mb-3">
                        🗺️
                    </div>

                    <h5>Provincias</h5>

                    <p class="text-muted">
                        Gestión de provincias argentinas.
                    </p>

                    <a href="tablas_maestras/provincias/listarProvincia.php"
                    class="btn btn-maestra">
                        Administrar
                    </a>

                </div>
            </div>
        </div>

        <!-- Localidades -->
        <div class="col-md-4">
            <div class="card card-maestra shadow h-100">
                <div class="card-body text-center">

                    <div class="icono mb-3">
                        📍
                    </div>

                    <h5>Localidades</h5>

                    <p class="text-muted">
                        Gestión de ciudades y localidades.
                    </p>

                    <a href="tablas_maestras/localidades/listarLocalidad.php"
                    class="btn btn-maestra">
                        Administrar
                    </a>

                </div>
            </div>
        </div>

        <!-- Perfiles -->
        <div class="col-md-4">
            <div class="card card-maestra shadow h-100">
                <div class="card-body text-center">

                    <div class="icono mb-3">
                        👤
                    </div>

                    <h5>Perfiles</h5>

                    <p class="text-muted">
                        Administración de roles y permisos.
                    </p>

                    <a href="tablas_maestras/perfiles/listarPerfil.php"
                    class="btn btn-maestra">
                        Administrar
                    </a>

                </div>
            </div>
        </div>

        <!-- Tipo de Contacto -->
        <div class="col-md-4">
            <div class="card card-maestra shadow h-100">
                <div class="card-body text-center">

                    <div class="icono mb-3">
                        📲
                    </div>

                    <h5>Tipos de Contactos</h5>

                    <p class="text-muted">
                        Gestion de los tipos de contactos que se tiene con los usuarios.
                    </p>

                    <a href="tablas_maestras/tiposContacto/listarTipoContacto.php"
                    class="btn btn-maestra">
                        Administrar
                    </a>

                </div>
            </div><br>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>