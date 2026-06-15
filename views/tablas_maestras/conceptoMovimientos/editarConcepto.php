<?php
require_once '../../../models/tablas_maestras/ConceptoMovimiento.php';
$con = new ConceptoMovimiento();

$id = $_GET['id'] ?? '';

$conceptos = $con->obtenerPorId($id);

$concep = $con->listar();
$tipos = ['A','S','E'];

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1">

    <title>Editar Concepto-Movimiento</title>

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
                ✏️ Editar Conceptos de Movimientos
            </h2>
        </div>

        <div class="card-body p-4">
            <form action="../../../controllers/tablasMaestrasControllers/ConceptoController.php?accion=editar" method="POST">
                <input
                    type="hidden"
                    name="id_concepto"
                    value="<?= $conceptos['id_concepto'] ?>">

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Descripcion del Concepto-Movimiento
                    </label>
                    <input
                        type="text"
                        name="descripcion"
                        class="form-control"
                        value="<?= htmlspecialchars($conceptos['descripcion']) ?>"
                        required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">
                        Tipo de Movimiento
                    </label>

                    <select name="tipo_movimiento" class="form-select">
                        <?php foreach($tipos as $tipo){ ?>

                            <option
                                value="<?= $tipo ?>"
                                <?= ($tipo == $conceptos['tipo_movimiento']) ? 'selected' : '' ?>>

                                <?= $tipo ?>
                            </option>

                        <?php } ?>
                    </select>
                </div>

                <div class="d-flex justify-content-between">
                    <a
                        href="listarConcepto.php" class="btn btn-secondary">
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
