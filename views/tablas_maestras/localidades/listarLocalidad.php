<?php
require_once '../../../auth/auth.php';
require_once "../../../models/tablas_maestras/Ubicacion.php";
$loc = new Ubicacion();
$pro = new Ubicacion();

$provincias = $pro->listarProvincias();
$localidades = $loc->listarLocalidades();

$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Localidades</title>
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
            Gestión de Localidades
        </h2>

        <a href="crearLocalidad.php"
        class="btn btn-info text-dark fw-bold">
            + Nueva Localidad
        </a>
    </div>

    <?php if($mensaje == 'creado'){ ?>
        <div class="alert alert-success">
            Localidad creada correctamente.
        </div>
    <?php } ?>

    <?php if($mensaje == 'editado'){ ?>
        <div class="alert alert-warning">
            Localidad modificada correctamente.
        </div>
    <?php } ?>

    <?php if($mensaje == 'eliminado'){ ?>
        <div class="alert alert-danger">
            Localidad eliminada correctamente.
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
                        <th>Codigo Postal</th>
                        <th>Tipo de Zona</th>
                        <th>Provincia</th>
                        <th width="220">Acciones</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach($localidades as $l){ ?>

                    <tr>
                        <td>
                            <?= $l['id_localidad'] ?>
                        </td>

                        <td>
                            <span class="fw-bold text-dark">
                                📁 <?= htmlspecialchars($l['nombre_localidad']) ?>
                            </span>
                        </td>

                        <td>
                            <span class="badge bg-secondary">
                                <?= $l['codigo_postal'] ?>
                            </span>
                        </td>

                        <td>
                            <span class="badge bg-secondary">
                                <?= $l['tipo_zona'] ?>
                            </span>
                        </td>

                        <?php foreach($provincias as $p){
                            if($p['id_provincia'] == $l['rela_id_provincia']){?>
                            <td>
                                <?= $p['nombre_provincia'] ?>
                            </td>
                        <?php } ?>
                        <?php } ?>

                        <td>
                            <a href="editarLocalidad.php?id=<?= $l['id_localidad'] ?>"
                            class="btn btn-warning btn-sm fw-bold">
                                Editar
                            </a>

                            <a href="../../../controllers/tablasMaestrasControllers/LocalidadController.php?accion=eliminar&id=<?= $l['id_localidad'] ?>"
                            class="btn btn-danger btn-sm fw-bold" onclick="return confirm('¿Estas seguro de querer eliminar la Localidad ?')">
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