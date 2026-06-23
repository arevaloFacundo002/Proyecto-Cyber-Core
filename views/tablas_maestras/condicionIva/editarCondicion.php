<?php
require_once '../../../auth/auth.php';
require_once '../../../models/tablas_maestras/CondicionIva.php';
$con = new CondicionIva();

$id = $_GET['id'];
$condicion = $con->obtenerPorId($id);

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1">

    <title>Editar Condicion Iva</title>

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
                ✏️ Editar Condicion Iva
            </h2>
        </div>

        <div class="card-body p-4">
            <form action="../../../controllers/tablasMaestrasControllers/CondicionController.php?accion=editar" 
            method="POST" enctype="multipart/form-data">
                <input
                    type="hidden"
                    name="id_condicion_iva"
                    value="<?= $condicion['id_condicion_iva'] ?>">

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Nombre
                    </label>
                    <input
                        type="text"
                        name="nombre"
                        class="form-control"
                        value="<?= htmlspecialchars($condicion['nombre']) ?>"
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
                        value="<?= htmlspecialchars($condicion['descripcion']) ?>"
                        required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Porcentaje Iva
                    </label>
                    <input
                        type="text"
                        name="porcentaje_iva"
                        class="form-control"
                        value="<?= $condicion['porcentaje_iva'] ?>"
                        required>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a
                        href="listarCondicion.php" class="btn btn-secondary">
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