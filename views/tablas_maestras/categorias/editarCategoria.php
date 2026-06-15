<?php
require_once "../../../models/tablas_maestras/Categoria.php";

$cat = new Categoria();

$id = $_GET['id'] ?? '';

$categoria = $cat->obtenerPorId($id);
$categorias = $cat->listar();

$categoriasPadre = [];

foreach($categorias as $c){
    if(
        $c['rela_id_categoria_padre'] == NULL && $c['id_categoria'] != $id){
        $categoriasPadre[] = $c;
    }
}
?>

<!doctype html>
<html lang="es">

<head>

    <meta charset="utf-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1">

    <title>Editar Categoría</title>

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
                ✏️ Editar Categoría
            </h2>
        </div>

        <div class="card-body p-4">
            <form action="../../../controllers/tablasMaestrasControllers/CategoriaController.php?accion=editar" method="POST">
                <input
                    type="hidden"
                    name="id_categoria"
                    value="<?= $categoria['id_categoria'] ?>">

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Nombre
                    </label>
                    <input
                        type="text"
                        name="nombre"
                        class="form-control"
                        value="<?= htmlspecialchars($categoria['nombre']) ?>"
                        required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Descripción
                    </label>
                    <textarea
                        name="descripcion"
                        class="form-control"
                        rows="4"><?= htmlspecialchars($categoria['descripcion']) ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">
                        Categoría Padre
                    </label>

                    <select
                        name="categoria_padre"class="form-select">

                        <option value="">
                            Ninguna (Categoría Principal)
                        </option>

                        <?php foreach($categoriasPadre as $cate){ ?>
                            <option
                                value="<?= $cate['id_categoria'] ?>"

                                <?= ($categoria['rela_id_categoria_padre'] == $cate['id_categoria'])
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= htmlspecialchars($cate['nombre']) ?>
                            </option>
                        <?php } ?>
                    </select>

                    <div class="form-text">
                        Puede convertir esta categoría en una subcategoría seleccionando una categoría padre.
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a
                        href="listarCategoria.php" class="btn btn-secondary">
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