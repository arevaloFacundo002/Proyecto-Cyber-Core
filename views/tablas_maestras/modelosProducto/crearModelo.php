<?php
require_once "../../../models/tablas_maestras/Marca.php";

$mar = new Marca();
$marcas = $mar->listar();

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Crear Modelo de Producto</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <style>

        body{
            background-color:#f4f4f4;
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
            background:#11b1c2;
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
                📁 Nuevo Modelo de Producto
            </h2>
        </div>

        <div class="card-body p-4">
            <form 
            action="../../../controllers/tablasMaestrasControllers/ModeloController.php?accion=crear"
            method="post">

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Nombre  
                    </label>
                    <input
                        type="text"
                        name="nombre_modelo"
                        class="form-control"
                        placeholder="Ingrese el nombre del nuevo Modelo de Producto"
                        required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Elija la Marca del Modelo
                    </label>

                    <select name="rela_id_marca" class="form-select" placeholder="Seleccione una Marca" required>
                        <?php foreach($marcas as $marca) {?>
                            <option value="<?= $marca['id_marca'] ?>">
                                <?= $marca['nombre_marca'] ?>
                            </option>

                        <?php } ?> 
                    </select> 
                </div>

                <div class="d-flex justify-content-between">
                    <a href="listarModelo.php" class="btn btn-secondary">
                        ← Volver
                    </a>

                    <button
                        type="submit" class="btn btn-sistema text-white px-4">
                        Guardar Categoría
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>