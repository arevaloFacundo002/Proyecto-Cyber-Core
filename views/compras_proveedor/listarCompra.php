<?php
require_once __DIR__ . '/../../auth/staff.php';
require_once __DIR__ . '/../../models/inputs/CompraProveedor.php';

$modelo = new CompraProveedor();

$buscar = trim($_GET['buscar'] ?? '');
$estado = $_GET['estado'] ?? '';
if (!in_array($estado, ['borrador', 'confirmada', 'anulada'], true)) {
    $estado = '';
}

$limite = 10;
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$total = $modelo->contar($buscar, $estado);
$paginas = max(1, (int) ceil($total / $limite));
$pagina = min($pagina, $paginas);
$compras = $modelo->listar($buscar, $estado, $limite, ($pagina - 1) * $limite);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$badges = ['borrador' => 'warning text-dark', 'confirmada' => 'success', 'anulada' => 'secondary'];
$consulta = http_build_query(['buscar' => $buscar, 'estado' => $estado]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compras a proveedores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-3 mb-5">

    <a href="../../inicio.php" class="btn btn-secondary btn-sm mb-3">← Volver</a>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold mb-0">Compras a proveedores</h3>
        <a href="cargarCompra.php" class="btn btn-primary fw-bold">+ Cargar compra</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['tipo']) ?>">
            <?= htmlspecialchars($flash['texto']) ?>
        </div>
    <?php endif; ?>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-5">
            <input type="text" name="buscar" class="form-control"
                   placeholder="Buscar por comprobante o proveedor..." value="<?= htmlspecialchars($buscar) ?>">
        </div>
        <div class="col-md-3">
            <select name="estado" class="form-select">
                <option value="">Todos los estados</option>
                <option value="borrador" <?= $estado === 'borrador' ? 'selected' : '' ?>>Borrador</option>
                <option value="confirmada" <?= $estado === 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
                <option value="anulada" <?= $estado === 'anulada' ? 'selected' : '' ?>>Anulada</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-dark w-100">Filtrar</button>
        </div>
    </form>

    <div class="card shadow border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>N°</th>
                        <th>Fecha</th>
                        <th>Comprobante</th>
                        <th>Proveedor</th>
                        <th class="text-center">Productos</th>
                        <th class="text-end">Total</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($compras) === 0): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No hay compras para mostrar.</td></tr>
                <?php endif; ?>

                <?php foreach ($compras as $c): ?>
                    <tr>
                        <td><?= (int) $c['id_compras'] ?></td>
                        <td><?= $c['fecha_compra'] ? date('d/m/Y', strtotime($c['fecha_compra'])) : '-' ?></td>
                        <td><?= htmlspecialchars($c['comprobante_compra']) ?></td>
                        <td><?= htmlspecialchars($c['razon_social']) ?></td>
                        <td class="text-center"><?= (int) $c['items'] ?></td>
                        <td class="text-end">$ <?= number_format((float) $c['total_compra'], 2, ',', '.') ?></td>
                        <td>
                            <span class="badge bg-<?= $badges[$c['estado']] ?? 'secondary' ?>">
                                <?= ucfirst($c['estado']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="detalleCompra.php?id=<?= (int) $c['id_compras'] ?>"
                               class="btn btn-sm btn-outline-primary">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($paginas > 1): ?>
        <nav class="mt-3">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= $consulta ?>&pagina=<?= $pagina - 1 ?>">Anterior</a>
                </li>
                <li class="page-item disabled"><span class="page-link">Página <?= $pagina ?> de <?= $paginas ?></span></li>
                <li class="page-item <?= $pagina >= $paginas ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= $consulta ?>&pagina=<?= $pagina + 1 ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

</body>
</html>