<?php

require_once '../../auth/auth.php';

require_once '../../models/inputs/MovimientoStock.php';
require_once '../../models/inputs/Producto.php';

$movimientoModel = new MovimientoStock();
$productoModel = new Producto();


/*
 * PARÁMETROS
 */

$busqueda = trim($_GET['busqueda'] ?? '');

$tipo = $_GET['tipo'] ?? '';

$id_producto = isset($_GET['id_producto'])
    ? (int) $_GET['id_producto']
    : 0;

$fecha_desde = $_GET['fecha_desde'] ?? '';

$fecha_hasta = $_GET['fecha_hasta'] ?? '';


/*
 * PAGINACIÓN
 */

$porPagina = 9;

$paginaActual = isset($_GET['pagina'])
    ? max(1, (int) $_GET['pagina'])
    : 1;

$offset = ($paginaActual - 1) * $porPagina;


/*
 * PRODUCTOS PARA EL FILTRO
 */

$productos = $productoModel->listar(
    "",
    "activos",
    1000,
    0
);


/*
 * MOVIMIENTOS
 */

$movimientos = $movimientoModel->listar(
    $busqueda,
    $tipo,
    $id_producto,
    $fecha_desde,
    $fecha_hasta,
    $porPagina,
    $offset
);


/*
 * TOTAL
 */

$totalMovimientos = $movimientoModel->contar(
    $busqueda,
    $tipo,
    $id_producto,
    $fecha_desde,
    $fecha_hasta
);


/*
 * TOTAL DE PÁGINAS
 */

$totalPaginas = max(
    1,
    ceil($totalMovimientos / $porPagina)
);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Movimientos de Stock</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

</head>


<body>

<div class="container-fluid mt-4 px-4">

    <a href="../../inicio.php"
       class="btn btn-secondary mb-3">

        ← Volver

    </a>


    <!-- ENCABEZADO -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold mb-1">
                Movimientos de Stock
            </h2>

            <span class="text-muted">
                Historial de entradas y salidas de productos
            </span>

        </div>


        <a
            href="registrarMovimiento.php"
            class="btn btn-info text-dark fw-bold">

            + Registrar Movimiento

        </a>

    </div>



    <!-- FILTROS -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-body">

            <form
                method="GET"
                action="listarMovimiento.php">

                <div class="row g-2 align-items-end">


                    <!-- BÚSQUEDA -->

                    <div class="col-md-4">

                        <label class="form-label fw-bold">
                            Buscar
                        </label>

                        <input
                            type="text"
                            name="busqueda"
                            class="form-control"
                            value="<?= htmlspecialchars($busqueda) ?>"
                            placeholder="Código, producto, referencia...">

                    </div>


                    <!-- TIPO -->

                    <div class="col-md-2">

                        <label class="form-label fw-bold">
                            Tipo
                        </label>

                        <select
                            name="tipo"
                            class="form-select">

                            <option value="">
                                Todos
                            </option>

                            <option
                                value="E"
                                <?= $tipo === 'E' ? 'selected' : '' ?>>

                                Entradas

                            </option>

                            <option
                                value="S"
                                <?= $tipo === 'S' ? 'selected' : '' ?>>

                                Salidas

                            </option>

                        </select>

                    </div>


                    <!-- PRODUCTO -->

                    <div class="col-md-3">

                        <label class="form-label fw-bold">
                            Producto
                        </label>

                        <select
                            name="id_producto"
                            class="form-select">

                            <option value="0">
                                Todos los productos
                            </option>

                            <?php foreach ($productos as $p): ?>

                                <option
                                    value="<?= $p['id_producto'] ?>"
                                    <?= $id_producto == $p['id_producto'] ? 'selected' : '' ?>>

                                    <?= htmlspecialchars($p['codigo']) ?>
                                    -
                                    <?= htmlspecialchars($p['nombre']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div><br>


                    <!-- FECHA DESDE -->

                    <div class="col-md-3">

                        <label class="form-label fw-bold">
                            Desde
                        </label>

                        <input
                            type="date"
                            name="fecha_desde"
                            class="form-control"
                            value="<?= htmlspecialchars($fecha_desde) ?>">

                    </div>


                    <!-- FECHA HASTA -->

                    <div class="col-md-3">

                        <label class="form-label fw-bold">
                            Hasta
                        </label>

                        <input
                            type="date"
                            name="fecha_hasta"
                            class="form-control"
                            value="<?= htmlspecialchars($fecha_hasta) ?>">

                    </div>


                    <!-- BOTÓN FILTRAR -->

                    <div class="col-md-2">

                        <button
                            type="submit"
                            class="btn btn-info text-dark fw-bold w-100">

                            Filtrar

                        </button>

                    </div>


                    <!-- LIMPIAR -->

                    <div class="col-md-2">

                        <a
                            href="listarMovimiento.php"
                            class="btn btn-secondary w-100">

                            Limpiar

                        </a>

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

                    <thead class="table-dark">

                        <tr>

                            <th class="text-center">
                                ID
                            </th>

                            <th>
                                Fecha
                            </th>

                            <th>
                                Producto
                            </th>

                            <th>
                                Concepto
                            </th>

                            <th class="text-center">
                                Cantidad
                            </th>

                            <th>
                                Referencia
                            </th>

                            <th>
                                Comentario
                            </th>

                            <th >
                                Detalle
                            </th>
                        </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($movimientos)): ?>

                        <tr>

                            <td
                                colspan="7"
                                class="text-center py-4 text-muted">

                                No se encontraron movimientos.

                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach ($movimientos as $mov): ?>

                            <tr>


                                <!-- ID -->

                                <td>

                                    <?= $mov['id_movimientos'] ?>

                                </td>


                                <!-- FECHA -->

                                <td>

                                    <?= date(
                                        'd/m/Y',
                                        strtotime($mov['fecha_movimiento'])
                                    ) ?>

                                </td>


                                <!-- PRODUCTO -->

                                <td>

                                    <div class="fw-bold">

                                        <?= htmlspecialchars(
                                            $mov['nombre_producto']
                                        ) ?>

                                    </div>

                                    <small class="text-muted">

                                        <?= htmlspecialchars(
                                            $mov['codigo']
                                        ) ?>

                                    </small>

                                </td>


                                <!-- CONCEPTO -->

                                <td>

                                    <?= htmlspecialchars(
                                        $mov['concepto']
                                    ) ?>

                                </td>


                                <!-- CANTIDAD -->

                                <td class="text-center">

                                    <?php if ($mov['cantidad'] > 0): ?>

                                        <span class="badge text-bg-success">

                                            +<?= $mov['cantidad'] ?>

                                        </span>

                                    <?php else: ?>

                                        <span class="badge text-bg-danger">

                                            <?= $mov['cantidad'] ?>

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- REFERENCIA -->

                                <td>

                                    <?= htmlspecialchars(
                                        $mov['referencia_ext'] ?? '-'
                                    ) ?>

                                </td>


                                <!-- COMENTARIO -->

                                <td>

                                    <?= htmlspecialchars(
                                        $mov['comentario'] ?? '-'
                                    ) ?>

                                </td>


                                <td>
                                    <a
                                        href="detalleMovimiento.php?id=<?= $mov['id_movimientos'] ?>"
                                        class="btn btn-sm btn-info">
                                        Ver
                                    </a>
                                </td>

                            </tr>

                        <?php endforeach; ?>


                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>


        <!-- PIE DE TABLA -->

        <?php if ($totalMovimientos > 0): ?>

            <div class="card-footer bg-white">

                <div class="d-flex justify-content-between align-items-center">


                    <!-- INFORMACIÓN -->

                    <small class="text-muted">

                        Mostrando

                        <?= $offset + 1 ?>

                        -

                        <?= min(
                            $offset + $porPagina,
                            $totalMovimientos
                        ) ?>

                        de

                        <?= $totalMovimientos ?>

                        movimientos

                    </small>


                    <!-- PAGINACIÓN -->

                    <nav>

                        <ul class="pagination pagination-sm mb-0">


                            <!-- ANTERIOR -->

                            <li
                                class="page-item <?= $paginaActual <= 1 ? 'disabled' : '' ?>">

                                <a
                                    class="page-link"
                                    href="?<?= http_build_query([
                                        'busqueda' => $busqueda,
                                        'tipo' => $tipo,
                                        'id_producto' => $id_producto,
                                        'fecha_desde' => $fecha_desde,
                                        'fecha_hasta' => $fecha_hasta,
                                        'pagina' => $paginaActual - 1
                                    ]) ?>">

                                    ←

                                </a>

                            </li>


                            <!-- NÚMEROS -->

                            <?php for (
                                $i = 1;
                                $i <= $totalPaginas;
                                $i++
                            ): ?>

                                <li
                                    class="page-item <?= $i == $paginaActual ? 'active' : '' ?>">

                                    <a
                                        class="page-link"
                                        href="?<?= http_build_query([
                                            'busqueda' => $busqueda,
                                            'tipo' => $tipo,
                                            'id_producto' => $id_producto,
                                            'fecha_desde' => $fecha_desde,
                                            'fecha_hasta' => $fecha_hasta,
                                            'pagina' => $i
                                        ]) ?>">

                                        <?= $i ?>

                                    </a>

                                </li>

                            <?php endfor; ?>


                            <!-- SIGUIENTE -->

                            <li
                                class="page-item <?= $paginaActual >= $totalPaginas ? 'disabled' : '' ?>">

                                <a
                                    class="page-link"
                                    href="?<?= http_build_query([
                                        'busqueda' => $busqueda,
                                        'tipo' => $tipo,
                                        'id_producto' => $id_producto,
                                        'fecha_desde' => $fecha_desde,
                                        'fecha_hasta' => $fecha_hasta,
                                        'pagina' => $paginaActual + 1
                                    ]) ?>">

                                    →

                                </a>

                            </li>

                        </ul>

                    </nav>

                </div>

            </div>

        <?php endif; ?>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>