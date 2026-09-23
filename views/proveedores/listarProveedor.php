<?php
require_once '../../auth/auth.php';
require_once "../../models/inputs/Proveedor.php";

$prov = new Proveedor();

// BÚSQUEDA Y PAGINACIÓN
$busqueda = $_GET['buscar'] ?? '';
$porPagina = 6;

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) {
    $pagina = 1;
}

$offset = ($pagina - 1) * $porPagina;

// OBTENER PROVEEDORES
$proveedores = $prov->listar(
    $busqueda,
    "todos",
    $porPagina,
    $offset
);

// CONTAR PROVEEDORES
$totalProveedores = $prov->contar(
    $busqueda,
    "todos"
);

$totalPaginas = ceil($totalProveedores / $porPagina);

// MENSAJES
$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Cache-Control" content="no-store">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <title>Gestión de Proveedores</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../css/theme.css" rel="stylesheet">

</head>

<body>
<div class="container mt-4">

    <a href="../panel_inputs.php" class="btn btn-secondary mb-3">
        ← Volver
    </a>

    <!-- ENCABEZADO -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Gestión de Proveedores</h2>

        <a href="crearProveedor.php" class="btn btn-info text-dark fw-bold">
            + Nuevo Proveedor
        </a>
    </div>

    <!-- MENSAJES -->
    <?php if ($mensaje == 'creado') { ?>
        <div class="alert alert-success alert-dismissible fade show">
            Proveedor creado correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php } ?>

    <?php if ($mensaje == 'editado') { ?>
        <div class="alert alert-success alert-dismissible fade show">
            Proveedor editado correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php } ?>

    <?php if ($mensaje == 'eliminado') { ?>
        <div class="alert alert-success alert-dismissible fade show">
            Proveedor eliminado correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php } ?>

    <?php if ($error == '1') { ?>
        <div class="alert alert-danger alert-dismissible fade show">
            Ocurrió un error al realizar la operación.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php } ?>
    <?php if ($error == 'relacion') { ?>
    <div class="alert alert-warning alert-dismissible fade show">
        No se puede eliminar este proveedor porque tiene productos asociados en el catálogo.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php } ?>

    <!-- BUSCADOR -->
    <div class="card shadow border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-9">
                    <label class="form-label fw-bold">Buscar proveedor</label>
                    <input type="text" name="buscar" class="form-control"
                           placeholder="Nombre, contacto, email o teléfono..."
                           value="<?= htmlspecialchars($busqueda) ?>">
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-info text-dark fw-bold w-100">
                        🔎 Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- INFORMACIÓN -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="text-muted">
            <?= $totalProveedores ?> proveedor(es) encontrado(s)
        </span>

        <?php if ($totalPaginas > 0) { ?>
            <span class="text-muted">
                Página <?= $pagina ?> de <?= $totalPaginas ?>
            </span>
        <?php } ?>
    </div>

    <!-- TABLA -->
    <div class="card shadow border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-info">
                        <tr>
                            <th>ID</th>
                            <th>Proveedor</th>
                            <th>Contacto</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th width="200">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($proveedores) > 0) { ?>
                        <?php foreach ($proveedores as $proveedor) { ?>
                            <tr>
                                <!-- ID -->
                                <td><?= $proveedor['id_proveedores'] ?></td>

                                <!-- PROVEEDOR -->
                                <td>
                                    <span class="fw-bold text-dark">
                                        🏢 <?= htmlspecialchars($proveedor['nombre_apellido'] ?? '') ?>
                                    </span>
                                </td>

                                <!-- CONTACTO -->
                                <td><?= htmlspecialchars($proveedor['contacto'] ?? '') ?></td>

                                <!-- EMAIL -->
                                <td><?= htmlspecialchars($proveedor['email'] ?? '') ?></td>

                                <!-- TELÉFONO -->
                                <td><?= htmlspecialchars($proveedor['telefono'] ?? '') ?></td>

                                <!-- ACCIONES -->
                                <td>
                                    <div class="d-flex gap-2">
                                        <!-- EDITAR -->
                                        <a href="editarProveedor.php?id=<?= $proveedor['id_proveedores'] ?>"
                                           class="btn btn-warning btn-sm fw-bold">
                                            Editar
                                        </a>

                                        <!-- ELIMINAR -->
                                        <a href="../../controllers/inputsControllers/ProveedorController.php?accion=eliminar&id=<?= $proveedor['id_proveedores'] ?>"
                                           class="btn btn-danger btn-sm fw-bold"
                                           onclick="return confirm('¿Está seguro de querer eliminar este proveedor?')">
                                            Eliminar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No se encontraron proveedores.
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- PAGINACIÓN -->
    <?php if ($totalPaginas > 1) { ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?buscar=<?= urlencode($busqueda) ?>&pagina=<?= $pagina - 1 ?>">
                        ← Anterior
                    </a>
                </li>

                <?php for ($i = 1; $i <= $totalPaginas; $i++) { ?>
                    <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                        <a class="page-link" href="?buscar=<?= urlencode($busqueda) ?>&pagina=<?= $i ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php } ?>

                <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
                    <a class="page-link" href="?buscar=<?= urlencode($busqueda) ?>&pagina=<?= $pagina + 1 ?>">
                        Siguiente →
                    </a>
                </li>
            </ul>
        </nav>
    <?php } ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../js/theme-toggle.js"></script>
</body>
</html>