<?php
require_once '../../../auth/auth.php';

$opciones_autorizacion = [1 => 'SI',0 => 'NO'];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Crear Metodo de Pago</title>

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
                📁 Nueva Metodo de Pago
            </h2>
        </div>

        <div class="card-body p-4">
            <form 
            action="../../../controllers/tablasMaestrasControllers/MetodoController.php?accion=crear"
            method="post">

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Nombre  
                    </label>
                    <input
                        type="text"
                        name="nombre"
                        class="form-control"
                        placeholder="Ingrese el nombre del nuevo Metodo de Pago"
                        required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Descripcion
                    </label>
                    <input
                        type="text"
                        name="descripcion"
                        class="form-control"
                        placeholder="Ingrese la descripcion del nuevo Metodo de Pago"
                        required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Requiere Autorizacion
                    </label>

                    <!--Para que se vea SI-NO en el select y se mande 0-1 a la base de datos-->
                    <select name="requiere_autorizacion" class="form-select">
                        <?php foreach($opciones_autorizacion as $valorBD => $textoVisual) {?>
                            <option value="<?= $valorBD ?>">
                                <?= $textoVisual ?>
                            </option>
                        <?php } ?> 
                    </select> 
                </div>

                <div class="d-flex justify-content-between">
                    <a href="listarMetodo.php" class="btn btn-secondary">
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