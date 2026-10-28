<?php

require_once '../../auth/auth.php';

require_once '../../models/tablas_maestras/Categoria.php';
require_once '../../models/tablas_maestras/Marca.php';
require_once '../../models/tablas_maestras/ModeloProducto.php';

$categoria = new Categoria();
$marca = new Marca();
$modelo = new ModeloProducto();

$categorias = $categoria->listar();
$marcas = $marca->listar();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Nuevo Producto</title>

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
    <a href="listarProducto.php"
       class="btn btn-secondary btn-sm mb-3">
        ← Volver
    </a>

    <!-- TITULO -->
    <div class="mb-3">
        <h2 class="fw-bold mb-0">
            Gestión de Productos
        </h2>
    </div>

    <!-- FORMULARIO -->
    <div class="card shadow border-0">

        <div class="card-body">

            <form
                action="../../controllers/inputsControllers/ProductoController.php?accion=crear"
                method="POST"
                enctype="multipart/form-data">

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
                        rows="2"></textarea>

                </div>


                <!-- IMÁGENES -->
                <div class="mb-3">

                    <label class="form-label fw-bold">
                        Imágenes del producto
                    </label>

                    <input
                        type="file"
                        name="imagenes[]"
                        id="imagenes"
                        class="form-control"
                        accept="image/jpeg,image/png,image/webp"
                        multiple>

                    <div class="form-text">
                        Podés elegir varias fotos a la vez (JPG, PNG o WebP · hasta 8 fotos · 4 MB cada una).
                        Mantené apretado Ctrl para elegir varias a la vez. Abajo, en la vista previa, marcá cuál será la principal.
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
                            required>

                    </div>


                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">
                            Stock inicial
                        </label>
                        <input
                            type="number"
                            class="form-control bg-light"
                            value="0"
                            disabled>
                        <input type="hidden" name="stock" value="0">
                        <small class="text-muted">
                            Inicia en 0. Podrás cargar stock desde Movimientos.
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
                            min="0">

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
                                    value="<?= $cat['id_categoria'] ?>">

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
                                    value="<?= $m['id_marca'] ?>">

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

                        Guardar Producto

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
</script>

<script>
document.getElementById('marca').addEventListener('change', function () {

    const idMarca = this.value;
    const selectModelo = document.getElementById('modelo');

    // Limpiar modelos actuales
    selectModelo.innerHTML = `
        <option value="">
            Seleccionar modelo
        </option>
    `;

    // Si no seleccionó ninguna marca, terminamos
    if (idMarca === '') {
        return;
    }

    fetch('../../ajax/modelosPorMarca.php?id_marca=' + idMarca)
        .then(response => response.json())
        .then(modelos => {

            modelos.forEach(modelo => {

                const option = document.createElement('option');

                option.value = modelo.id_modelo_producto;
                option.textContent = modelo.nombre_modelo;

                selectModelo.appendChild(option);
            });

        })
        .catch(error => {
            console.error('Error al cargar los modelos:', error);
        });

});
</script>

<script>
// Vista previa de las fotos nuevas, con la opción de elegir cuál es la principal
document.getElementById('imagenes').addEventListener('change', function () {
    var cont = document.getElementById('vistaPrevia');
    cont.innerHTML = '';
    var esCrear = true;
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