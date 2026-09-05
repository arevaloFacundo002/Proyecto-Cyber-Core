<?php

require_once '../../auth/auth.php';
require_once '../../models/inputs/Producto.php';
require_once '../../models/tablas_maestras/ConceptoMovimiento.php';

$productoModel = new Producto();
$conceptoModel = new ConceptoMovimiento();

$productos = $productoModel->listar("", "activos", 1000, 0);
$conceptos = $conceptoModel->listar();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Registrar Movimiento</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

</head>

<body>

<div class="container mt-3">

    <!-- VOLVER -->
    <a
        href="listarMovimiento.php"
        class="btn btn-secondary btn-sm mb-3">

        ← Volver

    </a>


    <!-- TITULO -->
    <div class="d-flex justify-content-between align-items-center mb-3">

        <h3 class="fw-bold mb-0">
            Registrar Movimiento de Stock
        </h3>

    </div>


    <!-- FORMULARIO -->
    <div class="card shadow border-0">

        <div class="card-body p-3">

            <form
                action="../../controllers/inputsControllers/MovimientoStockController.php?accion=registrar"
                method="POST">


                <!-- PRODUCTO Y CONCEPTO -->
                <div class="row">

                    <!-- PRODUCTO -->
                    <div class="col-md-7 mb-2">

                        <label class="form-label fw-bold mb-1">
                            Producto
                        </label>

                        <select
                            name="id_producto"
                            class="form-select form-select-sm"
                            required>

                            <option value="">
                                Seleccionar producto
                            </option>

                            <?php foreach ($productos as $p): ?>

                                <option
                                    value="<?= $p['id_producto'] ?>">

                                    <?= htmlspecialchars($p['codigo']) ?>
                                    -
                                    <?= htmlspecialchars($p['nombre']) ?>
                                    (Stock: <?= $p['stock'] ?>)

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- CONCEPTO -->
                    <div class="col-md-5 mb-2">

                        <label class="form-label fw-bold mb-1">
                            Tipo de movimiento
                        </label>

                        <select
                            name="id_concepto"
                            class="form-select form-select-sm"
                            required>

                            <option value="">
                                Seleccionar movimiento
                            </option>

                            <?php foreach ($conceptos as $c): ?>

                                <option
                                    value="<?= $c['id_concepto'] ?>">

                                    <?= htmlspecialchars($c['descripcion']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>


                <!-- CANTIDAD Y FECHA -->
                <div class="row">

                    <!-- CANTIDAD -->
                    <div class="col-md-6 mb-2">

                        <label class="form-label fw-bold mb-1">
                            Cantidad
                        </label>

                        <input
                            type="number"
                            name="cantidad"
                            class="form-control form-control-sm"
                            min="1"
                            required>

                        <div class="form-text">
                            Ingrese siempre una cantidad positiva.
                        </div>

                    </div>


                    <!-- FECHA -->
                    <div class="col-md-6 mb-2">

                        <label class="form-label fw-bold mb-1">
                            Fecha del movimiento
                        </label>

                        <input
                            type="date"
                            name="fecha_movimiento"
                            class="form-control form-control-sm"
                            value="<?= date('Y-m-d') ?>"
                            required>

                    </div>

                </div>


                <!-- REFERENCIA -->
                <div class="mb-2">

                    <label class="form-label fw-bold mb-1">
                        Referencia externa
                    </label>

                    <input
                        type="text"
                        name="referencia_ext"
                        class="form-control form-control-sm"
                        maxlength="50"
                        placeholder="Ej: COMP-001, PED-001">

                </div>


                <!-- COMENTARIO -->
                <div class="mb-3">

                    <label class="form-label fw-bold mb-1">
                        Comentario
                    </label>

                    <textarea
                        name="comentario"
                        class="form-control form-control-sm"
                        rows="2"
                        placeholder="Observaciones del movimiento"></textarea>

                </div>


                <!-- BOTONES -->
                <div class="d-flex justify-content-between">

                    <a
                        href="listarMovimiento.php"
                        class="btn btn-secondary btn-sm">

                        ← Cancelar

                    </a>

                    <button
                        type="submit"
                        class="btn btn-info text-dark fw-bold btn-sm px-4">

                        Registrar Movimiento

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>