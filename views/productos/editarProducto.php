<?php

require_once '../../auth/auth.php';

require_once '../../models/inputs/Producto.php';
require_once '../../models/ProductoImagen.php';
require_once '../../includes/imagen_helper.php';
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

// Galería de imágenes del producto
$imagenes = (new ProductoImagen())->listar($id_producto);
$avisoImagenes = $_GET['aviso'] ?? '';

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
    <link href="../../css/theme.css" rel="stylesheet">

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

<?php
$ruta_raiz = '../../';
require __DIR__ . '/../../includes/header_admin.php';
?>



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
                method="POST"
                enctype="multipart/form-data">


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


                <!-- IMÁGENES -->
                <div class="mb-3">

                    <label class="form-label fw-bold">
                        Imágenes del producto
                    </label>

                    <?php if ($avisoImagenes !== ''): ?>
                        <div class="alert alert-warning py-2">
                            El producto se guardó, pero: <?= htmlspecialchars($avisoImagenes) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($imagenes)): ?>
                        <p class="text-muted small mb-2">Este producto todavía no tiene fotos.</p>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-3 mb-3">
                            <?php foreach ($imagenes as $img): ?>
                                <div class="border rounded p-2 text-center" style="width:130px;">
                                    <img src="<?= htmlspecialchars(urlImagen($img['archivo'], '../../')) ?>" alt=""
                                         style="width:100%;height:90px;object-fit:cover;border-radius:6px;">
                                    <div class="form-check mt-2 text-start">
                                        <input class="form-check-input" type="radio" name="imagen_principal"
                                               id="pri<?= (int) $img['id_imagen'] ?>" value="<?= (int) $img['id_imagen'] ?>"
                                               <?= $img['es_principal'] ? 'checked' : '' ?>>
                                        <label class="form-check-label small" for="pri<?= (int) $img['id_imagen'] ?>">Principal</label>
                                    </div>
                                    <div class="form-check text-start">
                                        <input class="form-check-input" type="checkbox" name="eliminar_imagenes[]"
                                               id="del<?= (int) $img['id_imagen'] ?>" value="<?= (int) $img['id_imagen'] ?>">
                                        <label class="form-check-label small text-danger" for="del<?= (int) $img['id_imagen'] ?>">Eliminar</label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <label class="form-label small mb-1">Agregar más fotos</label>
                    <input
                        type="file"
                        name="imagenes[]"
                        id="imagenes"
                        class="form-control"
                        accept="image/jpeg,image/png,image/webp"
                        multiple>

                    <div class="form-text">
                        JPG, PNG o WebP · hasta 8 fotos por producto · 4 MB cada una.
                        Podés elegir varias a la vez (Ctrl + clic). Marcá "Principal" en la que quieras mostrar en la tienda, incluso entre las nuevas, y "Eliminar" en las que quieras sacar.
                    </div>

                    <div id="vistaPrevia" class="d-flex flex-wrap gap-2 mt-2"></div>

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


<script>
// Vista previa de las fotos nuevas, con la opción de elegir cuál es la principal
document.getElementById('imagenes').addEventListener('change', function () {
    var cont = document.getElementById('vistaPrevia');
    cont.innerHTML = '';
    var esCrear = false;
    Array.prototype.forEach.call(this.files, function (f, i) {
        if (!f.type.startsWith('image/')) return;
        var caja = document.createElement('div');
        caja.style.cssText = 'width:110px;text-align:center;font-size:12px;';

        var img = document.createElement('img');
        img.src = URL.createObjectURL(f);
        img.style.cssText = 'width:100%;height:80px;object-fit:cover;border-radius:8px;border:1px solid #888;';

        var etiqueta = document.createElement('label');
        etiqueta.style.cssText = 'display:block;margin-top:4px;cursor:pointer;';
        var radio = document.createElement('input');
        radio.type = 'radio';
        radio.name = 'imagen_principal';
        radio.value = 'nueva_' + i;
        if (esCrear && i === 0) radio.checked = true;
        etiqueta.appendChild(radio);
        etiqueta.appendChild(document.createTextNode(' Principal'));

        caja.appendChild(img);
        caja.appendChild(etiqueta);
        cont.appendChild(caja);
    });
});
</script>
<script src="../../js/theme-toggle.js"></script>
</body>

</html>