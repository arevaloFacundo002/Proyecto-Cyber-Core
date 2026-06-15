<?php
require_once "../../../models/tablas_maestras/Categoria.php";
$cat = new Categoria();

$categorias = $cat->listar();

$padres = [];
$hijas = [];

foreach ($categorias as $cate) {
    if ($cate['rela_id_categoria_padre'] == null) {
        $padres[] = $cate;
    } else {
        $hijas[$cate['rela_id_categoria_padre']][] = $cate;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
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
            Gestión de Categorías
        </h2>

        <a href="crearCategoria.php"
        class="btn btn-info text-dark fw-bold">
            + Nueva Categoría
        </a>
    </div>

    <div class="card shadow border-0">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th width="220">Acciones</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach($padres as $padre){ ?>
                    <!-- CATEGORIA PADRE -->
                    <tr>
                        <td>
                            <?= $padre['id_categoria'] ?>
                        </td>

                        <td>
                            <span class="fw-bold text-dark">
                                📁 <?= htmlspecialchars($padre['nombre']) ?>
                            </span>
                        </td>

                        <td>
                            <span class="badge bg-primary">
                                Principal
                            </span>
                        </td>

                        <td>
                            <a href="editarCategoria.php?id=<?= $padre['id_categoria'] ?>"
                            class="btn btn-warning btn-sm fw-bold">
                                Editar
                            </a>

                            <a href="../../../controllers/tablasMaestrasControllers/CategoriaController.php?accion=eliminar&id=<?= $padre['id_categoria'] ?>"
                            class="btn btn-danger btn-sm fw-bold" onclick="return confirm('¿Estas seguro de querer eliminar la categoria?')">
                                Baja
                            </a>
                        </td>
                    </tr>

                <!-- SUBCATEGORIAS -->

                <?php if(isset($hijas[$padre['id_categoria']])){ ?>

                <?php foreach($hijas[$padre['id_categoria']] as $hija){ ?>

                    <tr class="table-light">

                        <td>
                            <?= $hija['id_categoria'] ?>
                        </td>

                        <td class="ps-5">
                            ↳
                            <?= htmlspecialchars($hija['nombre']) ?>
                        </td>

                        <td>
                            <span class="badge bg-secondary">
                                Subcategoría
                            </span>
                        </td>

                        <td>
                            <a href="editarCategoria.php?id=<?= $hija['id_categoria'] ?>"
                            class="btn btn-warning btn-sm fw-bold">
                                Editar
                            </a>

                            <a 
                            href="../../../controllers/tablasMaestrasControllers/CategoriaController.php?accion=eliminar&id=<?= $hija['id_categoria'] ?>"
                            class="btn btn-danger btn-sm fw-bold" onclick="return confirm('¿Estas seguro de querer eliminar la subcategoria?')">
                                Baja
                            </a>

                        </td>

                    </tr>

                    <?php } ?>

                    <?php } ?>

                <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>
</html>