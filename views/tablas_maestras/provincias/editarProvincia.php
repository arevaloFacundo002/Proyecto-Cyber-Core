<?php
require_once '../../../models/tablas_maestras/Ubicacion.php';
$ub = new Ubicacion();

$id = $_GET['id'];
$provincia = $ub->obtenerProvinciaPorId($id);

$zonas_disponibles = [
    'ZONA1' => 'Zona 1 (Local / Cercano)',
    'ZONA2' => 'Zona 2 (Regional)',
    'ZONA3' => 'Zona 3 (Nacional Distante)',
    'ZONA4' => 'Zona 4 (Zonas Extremas / Patagonia)'
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1">

    <title>Editar Provincias</title>

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
                ✏️ Editar Provincia
            </h2>
        </div>

        <div class="card-body p-4">
            <form action="../../../controllers/tablasMaestrasControllers/ProvinciaController.php?accion=editar" 
            method="POST" enctype="multipart/form-data">
                <input
                    type="hidden"
                    name="id_provincia"
                    value="<?= $provincia['id_provincia'] ?>">

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Nombre de la Provincia
                    </label>
                    <input
                        type="text"
                        name="nombre_provincia"
                        class="form-control"
                        value="<?= htmlspecialchars($provincia['nombre_provincia']) ?>"
                        required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Codigo ISO 
                    </label>
                    <input
                        type="text"
                        name="codigo_iso"
                        class="form-control"
                        value="<?= htmlspecialchars($provincia['codigo_iso']) ?>"
                        pattern="^AR-[A-Z]$"
                        title="Debe tener el formato oficial, ej: AR-B (AR en mayúscula, guion medio y la letra de la provincia)"
                        required>
                    <small class="text-muted">Formato requerido: AR-X (Siempre en mayúsculas)</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Zona Tarifa 
                    </label>
                    <select name="zona_tarifa" class="form-select" required>
                        <?php foreach($zonas_disponibles as $valorBD => $textoVisual) { 
                            // Comparamos la zona actual de la BD con la opción del bucle
                            $selected = ($provincia['zona_tarifa'] == $valorBD) ? 'selected' : '';
                        ?>
                            <option value="<?= $valorBD ?>" <?= $selected ?>>
                                <?= $textoVisual ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Dias Transitos Base 
                    </label>
                    <input
                        type="number"
                        name="dias_transitos_base"
                        class="form-control"
                        min="1"
                        max="15"
                        value="<?= $provincia['dias_transitos_base'] ?>"
                        required>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a
                        href="listarProvincia.php" class="btn btn-secondary">
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