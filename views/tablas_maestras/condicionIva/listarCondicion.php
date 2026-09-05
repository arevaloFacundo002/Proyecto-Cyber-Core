<?php
require_once '../../../auth/auth.php';
require_once "../../../models/tablas_maestras/CondicionIva.php";

$condi = new CondicionIva();
$condiciones = $condi->listar();

$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Condiones Iva</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" 
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" 
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

<div class="container mt-4">

    <a href="../../panel_tablas.php"
        class="btn btn-secondary">
            ← Volver
    </a><br>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">
            Gestión de Condiciones Iva
        </h2>

        <a href="crearCondicion.php"
        class="btn btn-info text-dark fw-bold">
            + Nueva Condicion Iva
        </a>
    </div>

    <?php if($mensaje == 'creado'){ ?>
        <div class="alert alert-success">
            Condicion Iva creado correctamente.
        </div>
    <?php } ?>

    <?php if($mensaje == 'editado'){ ?>
        <div class="alert alert-warning">
            Condicion Iva modificado correctamente.
        </div>
    <?php } ?>

    <?php if($mensaje == 'eliminado'){ ?>
        <div class="alert alert-danger">
            Condicion Iva eliminado correctamente.
        </div>
    <?php } ?>

    <?php if($error){ ?>
        <div class="alert alert-danger">
            Ocurrió un error al realizar la operación.
        </div>
    <?php } ?>

    <div class="card shadow border-0">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripcion</th>
                        <th>Porcentaje Iva</th>
                        <th width="220">Acciones</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach($condiciones as $c){ ?>

                    <tr>
                        <td>
                            <?= $c['id_condicion_iva'] ?>
                        </td>

                        <td>
                            <span class="fw-bold text-dark">
                                📁 <?= htmlspecialchars($c['nombre']) ?>
                            </span>
                        </td>

                        <td>
                            <span class="badge bg-secondary">
                                <?= $c['descripcion'] ?>
                            </span>
                        </td>

                        <td>
                            <?= $c['porcentaje_iva'] ?>
                        </td>

                        <td>
                            <a href="editarCondicion.php?id=<?= $c['id_condicion_iva'] ?>""
                            class="btn btn-warning btn-sm fw-bold">
                                Editar
                            </a>

                            <a href="../../../controllers/tablasMaestrasControllers/CondicionController.php?accion=eliminar&id=<?= $c['id_condicion_iva'] ?>"
                            class="btn btn-danger btn-sm fw-bold" onclick="return confirm('¿Estas seguro de querer eliminar la Condicion Iva?')">
                                Baja
                            </a>
                        </td>
                    </tr>

                <?php } ?>

                </tbody>
            </table>
        </div>
    </div>
</div>
    
</body>
</html>