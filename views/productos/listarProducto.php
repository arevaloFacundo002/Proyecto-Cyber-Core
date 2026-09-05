<?php

require_once '../../auth/auth.php';
require_once '../../models/inputs/Producto.php';

$producto = new Producto();


// -------------------------------------
// BÚSQUEDA
// -------------------------------------

$busqueda = $_GET['buscar'] ?? '';


// -------------------------------------
// FILTRO DE ESTADO
// -------------------------------------

$estado = $_GET['estado'] ?? 'activos';


// -------------------------------------
// PAGINACIÓN
// -------------------------------------

$porPagina = 6;

$pagina = isset($_GET['pagina'])
    ? (int)$_GET['pagina']
    : 1;

if ($pagina < 1) {
    $pagina = 1;
}

$offset = ($pagina - 1) * $porPagina;


// -------------------------------------
// OBTENER PRODUCTOS
// -------------------------------------

$productos = $producto->listar(
    $busqueda,
    $estado,
    $porPagina,
    $offset
);


// -------------------------------------
// CONTAR PRODUCTOS
// -------------------------------------

$totalProductos = $producto->contar(
    $busqueda,
    $estado
);

$totalPaginas = ceil($totalProductos / $porPagina);


// -------------------------------------
// MENSAJES
// -------------------------------------

$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Gestión de Productos</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

</head>


<body>

<div class="container mt-4">


    <!-- VOLVER -->

    <a href="../panel_inputs.php"
       class="btn btn-secondary mb-3">

        ← Volver

    </a>


    <!-- ENCABEZADO -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h2 class="fw-bold">

            Gestión de Productos

        </h2>


        <a href="crearProducto.php"
           class="btn btn-info text-dark fw-bold">

            + Nuevo Producto

        </a>

    </div>


    <!-- MENSAJES -->

    <?php if ($mensaje === 'creado'): ?>

        <div class="alert alert-success alert-dismissible fade show">

            Producto creado correctamente.

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
            </button>

        </div>


    <?php elseif ($mensaje === 'editado'): ?>

        <div class="alert alert-success alert-dismissible fade show">

            Producto editado correctamente.

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
            </button>

        </div>


    <?php elseif ($mensaje === 'eliminado'): ?>

        <div class="alert alert-warning alert-dismissible fade show">

            Producto dado de baja correctamente.

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
            </button>

        </div>


    <?php elseif ($mensaje === 'activado'): ?>

        <div class="alert alert-success alert-dismissible fade show">

            Producto reactivado correctamente.

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
            </button>

        </div>

    <?php endif; ?>


    <?php if ($error === '1'): ?>

        <div class="alert alert-danger alert-dismissible fade show">

            Ocurrió un error al realizar la operación.

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
            </button>

        </div>

    <?php endif; ?>


    <!-- BUSCADOR Y FILTRO -->

    <div class="card shadow border-0 mb-4">

        <div class="card-body">

            <form method="GET">

                <div class="row g-2 align-items-end">


                    <!-- BUSCAR -->

                    <div class="col-md-6">

                        <label class="form-label fw-bold">

                            Buscar producto

                        </label>

                        <input
                            type="text"
                            name="buscar"
                            class="form-control"
                            placeholder="Código, nombre, descripción, categoría o marca..."
                            value="<?= htmlspecialchars($busqueda) ?>">

                    </div>


                    <!-- ESTADO -->

                    <div class="col-md-3">

                        <label class="form-label fw-bold">

                            Estado

                        </label>

                        <select
                            name="estado"
                            class="form-select">

                            <option
                                value="activos"
                                <?= $estado == 'activos' ? 'selected' : '' ?>>

                                🟢 Activos

                            </option>


                            <option
                                value="inactivos"
                                <?= $estado == 'inactivos' ? 'selected' : '' ?>>

                                🔴 Inactivos

                            </option>


                            <option
                                value="todos"
                                <?= $estado == 'todos' ? 'selected' : '' ?>>

                                ⚪ Todos

                            </option>

                        </select>

                    </div>


                    <!-- BOTÓN -->

                    <div class="col-md-3">

                        <button
                            type="submit"
                            class="btn btn-info text-dark fw-bold w-100">

                            🔎 Buscar

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- TABLA -->

    <div class="card shadow border-0">

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-info">

                        <tr>

                            <th>ID</th>

                            <th>Código</th>

                            <th>Producto</th>

                            <th>Precio</th>

                            <th>Stock</th>

                            <th>Categoría</th>

                            <th>Marca</th>

                            <th>Modelo</th>

                            <th>Estado</th>

                            <th width="210">
                                Acciones
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (empty($productos)): ?>

                        <tr>

                            <td colspan="10"
                                class="text-center py-4">

                                No se encontraron productos.

                            </td>

                        </tr>

                    <?php endif; ?>


                    <?php foreach ($productos as $prod): ?>

                        <tr>


                            <!-- ID -->

                            <td>

                                <?= $prod['id_producto'] ?>

                            </td>


                            <!-- CÓDIGO -->

                            <td>

                                <span class="badge bg-secondary">

                                    <?= htmlspecialchars($prod['codigo']) ?>

                                </span>

                            </td>


                            <!-- PRODUCTO -->

                            <td>

                                <strong>

                                    <?= htmlspecialchars($prod['nombre']) ?>

                                </strong>

                                <br>

                                <small class="text-muted">

                                    <?= htmlspecialchars(
                                        $prod['descripcion'] ?? ''
                                    ) ?>

                                </small>

                            </td>


                            <!-- PRECIO -->

                            <td>

                                $<?= number_format(
                                    $prod['precio'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>

                            </td>


                            <!-- STOCK -->

                            <td>

                                <?php

                                $stock = (int)$prod['stock'];

                                if ($stock == 0) {

                                    $claseStock = 'bg-danger';

                                } elseif ($stock <= 5) {

                                    $claseStock = 'bg-warning text-dark';

                                } else {

                                    $claseStock = 'bg-success';

                                }

                                ?>

                                <span class="badge <?= $claseStock ?>">

                                    <?= $stock ?>

                                </span>

                            </td>


                            <!-- CATEGORÍA -->

                            <td>

                                <?= htmlspecialchars(
                                    $prod['nombre_categoria']
                                ) ?>

                            </td>


                            <!-- MARCA -->

                            <td>

                                <?= htmlspecialchars(
                                    $prod['nombre_marca']
                                ) ?>

                            </td>


                            <!-- MODELO -->

                            <td>

                                <?= htmlspecialchars(
                                    $prod['nombre_modelo']
                                    ?? 'Sin modelo'
                                ) ?>

                            </td>


                            <!-- ESTADO -->

                            <td>

                                <?php if ($prod['es_activo'] == 1): ?>

                                    <span class="badge bg-success">

                                        Activo

                                    </span>

                                <?php else: ?>

                                    <span class="badge bg-danger">

                                        Inactivo

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- ACCIONES -->

                            <td>

                                <div class="d-flex gap-1">


                                    <!-- EDITAR -->

                                    <a
                                        href="editarProducto.php?id=<?= $prod['id_producto'] ?>"
                                        class="btn btn-warning btn-sm fw-bold">

                                        Editar

                                    </a>


                                    <?php if ($prod['es_activo'] == 1): ?>


                                        <!-- BAJA -->

                                        <a
                                            href="../../controllers/inputsControllers/ProductoController.php?accion=eliminar&id=<?= $prod['id_producto'] ?>"
                                            class="btn btn-danger btn-sm fw-bold"
                                            onclick="return confirm('¿Está seguro de querer dar de baja este producto?')">

                                            Baja

                                        </a>


                                    <?php else: ?>


                                        <!-- ACTIVAR -->

                                        <a
                                            href="../../controllers/inputsControllers/ProductoController.php?accion=activar&id=<?= $prod['id_producto'] ?>"
                                            class="btn btn-success btn-sm fw-bold"
                                            onclick="return confirm('¿Desea reactivar este producto?')">

                                            Activar

                                        </a>


                                    <?php endif; ?>


                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    <!-- PAGINACIÓN -->

    <?php if ($totalPaginas > 1): ?>

        <nav class="mt-4">

            <ul class="pagination justify-content-center">


                <!-- ANTERIOR -->

                <li class="page-item
                    <?= $pagina <= 1 ? 'disabled' : '' ?>">

                    <a
                        class="page-link"
                        href="?pagina=<?= $pagina - 1 ?>&buscar=<?= urlencode($busqueda) ?>&estado=<?= urlencode($estado) ?>">

                        ←

                    </a>

                </li>


                <!-- NÚMEROS -->

                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>

                    <li class="page-item
                        <?= $i == $pagina ? 'active' : '' ?>">

                        <a
                            class="page-link"
                            href="?pagina=<?= $i ?>&buscar=<?= urlencode($busqueda) ?>&estado=<?= urlencode($estado) ?>">

                            <?= $i ?>

                        </a>

                    </li>

                <?php endfor; ?>


                <!-- SIGUIENTE -->

                <li class="page-item
                    <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">

                    <a
                        class="page-link"
                        href="?pagina=<?= $pagina + 1 ?>&buscar=<?= urlencode($busqueda) ?>&estado=<?= urlencode($estado) ?>">

                        →

                    </a>

                </li>

            </ul>

        </nav>

    <?php endif; ?>


    <!-- INFORMACIÓN -->

    <div class="text-center text-muted mb-4">

        Mostrando
        <?= count($productos) ?>
        de
        <?= $totalProductos ?>
        productos

    </div>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>