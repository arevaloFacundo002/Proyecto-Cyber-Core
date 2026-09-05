<?php

require_once '../../auth/auth.php';

require_once '../../models/inputs/Producto.php';
require_once '../../models/tablas_maestras/Categoria.php';
require_once '../../models/tablas_maestras/Marca.php';
require_once '../../models/tablas_maestras/ModeloProducto.php';


// -----------------------------------------
// OBTENER ID DEL PRODUCTO
// -----------------------------------------

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    header("Location: listarProducto.php");
    exit();

}

$id_producto = (int) $_GET['id'];


// -----------------------------------------
// INSTANCIAR MODELOS
// -----------------------------------------

$productoModel = new Producto();
$categoria = new Categoria();
$marca = new Marca();
$modelo = new ModeloProducto();


// -----------------------------------------
// OBTENER PRODUCTO
// -----------------------------------------

$producto = $productoModel->obtenerPorId($id_producto);


// Si no existe
if (!$producto) {

    header("Location: listarProducto.php?error=producto_no_encontrado");
    exit();

}


// -----------------------------------------
// OBTENER DATOS PARA LOS SELECT
// -----------------------------------------

$categorias = $categoria->listar();
$marcas = $marca->listar();


// -----------------------------------------
// DATOS DEL PRODUCTO
// -----------------------------------------

$id_categoria_actual = $producto['rela_id_categoria'];
$id_marca_actual = $producto['rela_id_marca'];
$id_modelo_actual = $producto['rela_id_modelo_producto'];

?>
<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar Producto</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <style>

        .form-label {
            margin-bottom: 4px;
        }

        .form-control,
        .form-select {
            padding: 7px 10px;
        }

        textarea.form-control {
            resize: vertical;
        }

        .mb-3 {
            margin-bottom: 10px !important;
        }

        .card-body {
            padding: 20px !important;
        }

    </style>

</head>


<body>


<div class="container mt-3">


    <!-- VOLVER -->

    <a
        href="listarProducto.php"
        class="btn btn-secondary btn-sm mb-3">

        ← Volver

    </a>


    <!-- TITULO -->

    <div class="mb-3">

        <h2 class="fw-bold mb-0">

            Editar Producto

        </h2>

    </div>


    <!-- FORMULARIO -->

    <div class="card shadow border-0">

        <div class="card-body">


            <form
                action="../../controllers/inputsControllers/ProductoController.php?accion=editar"
                method="POST">


                <!-- ID OCULTO -->

                <input
                    type="hidden"
                    name="id_producto"
                    value="<?= $producto['id_producto'] ?>">


                <!-- CÓDIGO + NOMBRE -->

                <div class="row">

                    <div class="col-md-4 mb-3">

                        <label class="form-label fw-bold">

                            Código

                        </label>

                        <input
                            type="text"
                            name="codigo"
                            class="form-control"
                            maxlength="100"
                            value="<?= htmlspecialchars($producto['codigo'] ?? '') ?>"
                            required>

                    </div>


                    <div class="col-md-8 mb-3">

                        <label class="form-label fw-bold">

                            Nombre del producto

                        </label>

                        <input
                            type="text"
                            name="nombre"
                            class="form-control"
                            maxlength="100"
                            value="<?= htmlspecialchars($producto['nombre'] ?? '') ?>"
                            required>

                    </div>

                </div>


                <!-- DESCRIPCIÓN -->

                <div class="mb-3">

                    <label class="form-label fw-bold">

                        Descripción

                    </label>

                    <textarea
                        name="descripcion"
                        class="form-control"
                        rows="2"><?= htmlspecialchars($producto['descripcion'] ?? '') ?></textarea>

                </div>


                <!-- IMAGEN -->

                <div class="mb-3">

                    <label class="form-label fw-bold">

                        Imagen

                    </label>

                    <input
                        type="text"
                        name="imagen_url"
                        class="form-control"
                        maxlength="200"
                        value="<?= htmlspecialchars($producto['imagen_url'] ?? '') ?>"
                        placeholder="Ej: producto.png">

                </div>


                <!-- PRECIO + STOCK + PESO -->

                <div class="row">


                    <div class="col-md-4 mb-3">

                        <label class="form-label fw-bold">

                            Precio

                        </label>

                        <input
                            type="number"
                            name="precio"
                            class="form-control"
                            step="0.01"
                            min="0"
                            value="<?= htmlspecialchars($producto['precio'] ?? '') ?>"
                            required>

                    </div>


                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">
                            Stock
                        </label>

                        <input
                            type="number"
                            class="form-control bg-light"
                            value="<?= htmlspecialchars($producto['stock'] ?? '0') ?>"
                            readonly
                            disabled>
                        
                        <small class="text-muted">
                            El stock se gestiona desde el módulo de Movimientos.
                        </small>
                    </div>


                    <div class="col-md-4 mb-3">

                        <label class="form-label fw-bold">

                            Peso de envío (kg)

                        </label>

                        <input
                            type="number"
                            name="peso_envio"
                            class="form-control"
                            step="0.01"
                            min="0"
                            value="<?= htmlspecialchars($producto['peso_envio'] ?? '') ?>">

                    </div>


                </div>


                <!-- CATEGORÍA + MARCA + MODELO -->

                <div class="row">


                    <!-- CATEGORÍA -->

                    <div class="col-md-4 mb-3">

                        <label class="form-label fw-bold">

                            Categoría

                        </label>

                        <select
                            name="rela_id_categoria"
                            class="form-select"
                            required>

                            <option value="">

                                Seleccionar categoría

                            </option>


                            <?php foreach ($categorias as $cat): ?>

                                <option
                                    value="<?= $cat['id_categoria'] ?>"
                                    <?= ($cat['id_categoria'] == $id_categoria_actual) ? 'selected' : '' ?>>

                                    <?= htmlspecialchars($cat['nombre']) ?>

                                </option>

                            <?php endforeach; ?>


                        </select>

                    </div>


                    <!-- MARCA -->

                    <div class="col-md-4 mb-3">

                        <label class="form-label fw-bold">

                            Marca

                        </label>

                        <select
                            name="rela_id_marca"
                            id="marca"
                            class="form-select"
                            required>

                            <option value="">

                                Seleccionar marca

                            </option>


                            <?php foreach ($marcas as $m): ?>

                                <option
                                    value="<?= $m['id_marca'] ?>"
                                    <?= ($m['id_marca'] == $id_marca_actual) ? 'selected' : '' ?>>

                                    <?= htmlspecialchars($m['nombre_marca']) ?>

                                </option>

                            <?php endforeach; ?>


                        </select>

                    </div>


                    <!-- MODELO -->

                    <div class="col-md-4 mb-3">

                        <label class="form-label fw-bold">

                            Modelo

                        </label>

                        <select
                            name="rela_id_modelo_producto"
                            id="modelo"
                            class="form-select">


                            <option value="">

                                Seleccionar modelo

                            </option>


                        </select>

                    </div>


                </div>


                <!-- DESCONTINUADO -->

                <div class="mb-3">

                    <label class="form-label fw-bold">

                        Estado del producto

                    </label>


                    <select
                        name="es_descontinuado"
                        class="form-select">


                        <option
                            value="0"
                            <?= ($producto['es_descontinuado'] == 0) ? 'selected' : '' ?>>

                            Disponible

                        </option>


                        <option
                            value="1"
                            <?= ($producto['es_descontinuado'] == 1) ? 'selected' : '' ?>>

                            Descontinuado

                        </option>


                    </select>

                </div>


                <!-- BOTONES -->

                <div class="d-flex justify-content-between pt-1">


                    <a
                        href="listarProducto.php"
                        class="btn btn-secondary btn-sm">

                        ← Cancelar

                    </a>


                    <button
                        type="submit"
                        class="btn btn-info text-dark fw-bold px-4">

                        Guardar Cambios

                    </button>


                </div>


            </form>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
</script>


<!-- AJAX MODELOS POR MARCA -->

<script>

const marca = document.getElementById('marca');
const modelo = document.getElementById('modelo');


// ID del modelo que tenía originalmente el producto

const modeloActual = <?= $id_modelo_actual !== null
    ? (int) $id_modelo_actual
    : 'null' ?>;


// Función para cargar los modelos

function cargarModelos(idMarca, modeloSeleccionado = null) {

    // Limpiar modelos

    modelo.innerHTML = `
        <option value="">
            Seleccionar modelo
        </option>
    `;


    // Si no hay marca

    if (idMarca === '') {

        return;

    }


    fetch('../../ajax/modelosPorMarca.php?id_marca=' + idMarca)

        .then(response => response.json())

        .then(modelos => {


            modelos.forEach(item => {


                const option = document.createElement('option');


                option.value = item.id_modelo_producto;


                option.textContent = item.nombre_modelo;


                // Seleccionar modelo actual

                if (
                    modeloSeleccionado !== null &&
                    parseInt(item.id_modelo_producto) === parseInt(modeloSeleccionado)
                ) {

                    option.selected = true;

                }


                modelo.appendChild(option);


            });


        })

        .catch(error => {

            console.error(
                'Error al cargar los modelos:',
                error
            );

        });

}


// -----------------------------------------
// CAMBIO DE MARCA
// -----------------------------------------

marca.addEventListener('change', function () {

    cargarModelos(this.value);

});


// -----------------------------------------
// CARGAR MODELOS AL ABRIR LA PÁGINA
// -----------------------------------------

if (marca.value !== '') {

    cargarModelos(
        marca.value,
        modeloActual
    );

}

</script>


</body>

</html>