<?php
require_once "../../../models/tablas_maestras/MetodoPago.php";

$meto = new MetodoPago();
$metodos = $meto->listar();

$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Metodos de Pago</title>
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
            Gestión de Metodos de pago
        </h2>

        <a href="crearMetodo.php"
        class="btn btn-info text-dark fw-bold">
            + Nuevo Metodo de Pago
        </a>
    </div>

    <?php if($mensaje == 'creado'){ ?>
        <div class="alert alert-success">
            Metodo de pago creado correctamente.
        </div>
    <?php } ?>

    <?php if($mensaje == 'editado'){ ?>
        <div class="alert alert-warning">
            Metodo de pago modificado correctamente.
        </div>
    <?php } ?>

    <?php if($mensaje == 'eliminado'){ ?>
        <div class="alert alert-danger">
            Metodo de pago eliminado correctamente.
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
                        <th>Requiere Autorizacion</th>
                        <th width="220">Acciones</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach($metodos as $m){ ?>

                    <tr>
                        <td>
                            <?= $m['id_metodo_pago'] ?>
                        </td>

                        <td>
                            <span class="fw-bold text-dark">
                                📁 <?= htmlspecialchars($m['nombre']) ?>
                            </span>
                        </td>

                        <td>
                            <span class="badge bg-secondary">
                                <?= $m['descripcion'] ?>
                            </span>
                        </td>

                        <td>
                            <?php if ($m['requiere_autorizacion'] == 1) {
                                echo 'SI';
                            } else {
                                echo 'NO';
                            } ?>
                        </td>

                        <td>
                            <a href="editarMetodo.php?id=<?= $m['id_metodo_pago'] ?>""
                            class="btn btn-warning btn-sm fw-bold">
                                Editar
                            </a>

                            <a href="../../../controllers/tablasMaestrasControllers/MetodoController.php?accion=eliminar&id=<?= $m['id_metodo_pago'] ?>"
                            class="btn btn-danger btn-sm fw-bold" onclick="return confirm('¿Estas seguro de querer eliminar el Metodo de Pago?')">
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