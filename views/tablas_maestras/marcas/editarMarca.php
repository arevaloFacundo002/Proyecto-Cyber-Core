<?php
require_once '../../../models/tablas_maestras/Marca.php';
$mar = new Marca();

$id = $_GET['id'];
$marca = $mar->obtenerPorId($id);

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1">

    <title>Editar Marcas</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <style>

        body{
            background:#f4f4f4;
        }

        .card-form{
            max-width:800px;
            margin:auto;
            margin-top:40px;
            border:none;
            border-radius:15px;
            overflow:hidden;
        }

        .card-header-custom{
            background:#16c5d8;
            color:white;
            padding:20px;
        }

        .card-header-custom h2{
            margin:0;
            font-weight:bold;
        }

        .btn-sistema{
            background:#16c5d8;
            border:none;
            font-weight:bold;
        }

        .btn-sistema:hover{
            background:#12b1c1;
        }

        .form-control,
        .form-select{
            border-radius:10px;
        }

    </style>

</head>

<body>

<div class="container">
    <div class="card card-form shadow">
        <div class="card-header-custom">
            <h2>
                ✏️ Editar Marcas
            </h2>
        </div>

        <div class="card-body p-4">
            <form action="../../../controllers/tablasMaestrasControllers/MarcaController.php?accion=editar" 
            method="POST" enctype="multipart/form-data">
                <input
                    type="hidden"
                    name="id_marca"
                    value="<?= $marca['id_marca'] ?>">

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Nombre de la marca
                    </label>
                    <input
                        type="text"
                        name="nombre_marca"
                        class="form-control"
                        value="<?= htmlspecialchars($marca['nombre_marca']) ?>"
                        required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Nombre corto 
                    </label>
                    <input
                        type="text"
                        name="nombre_corto"
                        class="form-control"
                        value="<?= htmlspecialchars($marca['nombre_corto']) ?>"
                        required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Logo 
                    </label>
                    <input
                        type="text"
                        name="logo_url"
                        class="form-control"
                        value="<?= $marca['logo_url'] ?>"
                        >
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Sitio Web
                    </label>
                    <input
                        type="text"
                        name="sitio_web"
                        class="form-control"
                        value="<?= $marca['sitio_web'] ?>"
                        required>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a
                        href="listarMarca.php" class="btn btn-secondary">
                        ← Volver
                    </a>

                    <button type="submit" class="btn btn-sistema text-white px-4">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>