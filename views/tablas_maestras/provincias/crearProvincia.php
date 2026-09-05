<?php
require_once '../../../auth/auth.php';

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Crear Provincia</title>

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
                📁 Nueva Provincia
            </h2>
        </div>

        <div class="card-body p-4">
            <form 
            action="../../../controllers/tablasMaestrasControllers/ProvinciaController.php?accion=crear"
            method="post">

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Nombre de la Provincia
                    </label>
                    <input
                        type="text"
                        name="nombre_provincia"
                        class="form-control"
                        placeholder="Ingrese el nombre de la provincia nueva"
                        required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Código ISO</label>
                    <input type="text" 
                        name="codigo_iso" 
                        class="form-control" 
                        placeholder="Ingrese el codigo ISO. Ej: AR-P" 
                        pattern="^AR-[A-Z]$" 
                        title="Debe tener el formato oficial, ej: AR-P (AR en mayúscula, guion medio y la letra de la provincia)" 
                        required>
                    <small class="text-muted">Formato requerido: AR-X (Siempre en mayúsculas)</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Zona de Tarifa</label>
                    <select name="zona_tarifa" class="form-select" required>
                        <option value="">-- Seleccione una Zona --</option>
                        <option value="ZONA1">Zona 1 (Local / Cercano)</option>
                        <option value="ZONA2">Zona 2 (Regional)</option>
                        <option value="ZONA3">Zona 3 (Nacional Distante)</option>
                        <option value="ZONA4">Zona 4 (Zonas Extremas / Patagonia)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Dias Transitos Base
                    </label>
                    <input
                        type="text"
                        name="dias_transitos_base"
                        class="form-control"
                        placeholder="Ingrese los dias transitos base de la nueva Provincia"
                        required>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="listarProvincia.php" class="btn btn-secondary">
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