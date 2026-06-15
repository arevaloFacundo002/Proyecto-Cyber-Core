<?php
require_once "../../../models/tablas_maestras/Marca.php";

$mar = new Marca();
$marcas = $mar->listar();

$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Marcas</title>
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
            Gestión de Marcas
        </h2>

        <a href="crearMarca.php"
        class="btn btn-info text-dark fw-bold">
            + Nueva Marca
        </a>
    </div>

    <?php if($mensaje == 'creado'){ ?>
        <div class="alert alert-success">
            Marca creado correctamente.
        </div>
    <?php } ?>

    <?php if($mensaje == 'editado'){ ?>
        <div class="alert alert-warning">
            Marca modificado correctamente.
        </div>
    <?php } ?>

    <?php if($mensaje == 'eliminado'){ ?>
        <div class="alert alert-danger">
            Marca eliminado correctamente.
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
                        <th>Nombre corto</th>
                        <th>Logo URL</th>
                        <th>Sitio web</th>
                        <th width="220">Acciones</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach($marcas as $marca){ ?>

                    <tr>
                        <td>
                            <?= $marca['id_marca'] ?>
                        </td>

                        <td>
                            <span class="fw-bold text-dark">
                                📁 <?= htmlspecialchars($marca['nombre_marca']) ?>
                            </span>
                        </td>

                        <td>
                            <span class="badge bg-secondary">
                                <?= $marca['nombre_corto'] ?>
                            </span>
                        </td>

                        <td>
                            <?= $marca['logo_url'] ?>
                        </td>

                        <td>
                            <?= $marca['sitio_web'] ?>
                        </td>

                        <td>
                            <a href="editarMarca.php?id=<?= $marca['id_marca'] ?>""
                            class="btn btn-warning btn-sm fw-bold">
                                Editar
                            </a>

                            <a href="../../../controllers/tablasMaestrasControllers/MarcaController.php?accion=eliminar&id=<?= $marca['id_marca'] ?>"
                            class="btn btn-danger btn-sm fw-bold" onclick="return confirm('¿Estas seguro de querer eliminar la marca?')">
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