<?php

require_once '../../auth/auth.php';
require_once '../../models/inputs/MovimientoStock.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: listarMovimiento.php");
    exit();
}

$id_movimiento = (int) $_GET['id'];

$movimientoModel = new MovimientoStock();

$movimiento = $movimientoModel->obtenerPorId($id_movimiento);

if (!$movimiento) {
    header("Location: listarMovimiento.php?error=no_encontrado");
    exit();
}

// Determinar si es entrada o salida
$tipo = $movimiento['tipo_movimiento'];

if ($tipo === 'E') {
    $tipoTexto = 'Entrada';
    $badgeTipo = 'success';
    $cantidadTexto = '+' . abs((int) $movimiento['cantidad']);
} else {
    $tipoTexto = 'Salida';
    $badgeTipo = 'danger';
    $cantidadTexto = '-' . abs((int) $movimiento['cantidad']);
}

// Formatear fecha
$fechaFormateada = date(
    'd/m/Y',
    strtotime($movimiento['fecha_movimiento'])
);

// Formatear hora
$horaFormateada = !empty($movimiento['hora_movimiento'])
    ? date('H:i:s', strtotime($movimiento['hora_movimiento']))
    : '--:--:--';

?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Detalle del Movimiento</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <style>

        body {
            background-color: #f8f9fa;
        }

        .detalle-card {
            border: none;
            border-radius: 12px;
        }

        .seccion {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 16px;
            height: 100%;
        }

        .titulo-seccion {
            font-size: 0.85rem;
            font-weight: 700;
            color: #6c757d;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .dato-label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 2px;
        }

        .dato {
            font-weight: 600;
            color: #212529;
        }

        .cantidad {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .movimiento-id {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .usuario-box {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 14px;
        }

        .icono-usuario {
            font-size: 1.5rem;
        }

        .comentario-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
            min-height: 55px;
        }

    </style>

</head>

<body>

<div class="container mt-4 mb-4">

    <!-- VOLVER -->

    <a
        href="listarMovimiento.php"
        class="btn btn-secondary mb-3">

        ← Volver a movimientos

    </a>


    <!-- ENCABEZADO -->

    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>

            <h2 class="fw-bold mb-1">
                Detalle del Movimiento
            </h2>

            <span class="movimiento-id">
                Movimiento #<?= $movimiento['id_movimientos'] ?>
            </span>

        </div>

        <span class="badge bg-<?= $badgeTipo ?> fs-6 px-3 py-2">

            <?= $tipoTexto ?>

        </span>

    </div>


    <!-- TARJETA PRINCIPAL -->

    <div class="card shadow-sm detalle-card">

        <div class="card-body p-3">

            <div class="row g-3">


                <!-- INFORMACIÓN DEL MOVIMIENTO -->

                <div class="col-md-6">

                    <div class="seccion">

                        <div class="titulo-seccion">
                            Información del movimiento
                        </div>


                        <div class="row">

                            <!-- CONCEPTO -->

                            <div class="col-12 mb-3">

                                <div class="dato-label">
                                    Concepto
                                </div>

                                <div class="dato">
                                    <?= htmlspecialchars(
                                        $movimiento['concepto']
                                    ) ?>
                                </div>

                            </div>


                            <!-- CANTIDAD -->

                            <div class="col-6">

                                <div class="dato-label">
                                    Cantidad
                                </div>

                                <div class="cantidad text-<?= $badgeTipo ?>">

                                    <?= $cantidadTexto ?>

                                </div>

                            </div>


                            <!-- FECHA -->

                            <div class="col-6">

                                <div class="dato-label">
                                    Fecha
                                </div>

                                <div class="dato">

                                    <?= $fechaFormateada ?>

                                </div>

                                <div class="dato-label mt-2">
                                    Hora
                                </div>

                                <div class="dato">

                                    <?= $horaFormateada ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- PRODUCTO -->

                <div class="col-md-6">

                    <div class="seccion">

                        <div class="titulo-seccion">
                            Producto
                        </div>


                        <div class="mb-3">

                            <div class="dato-label">
                                Código
                            </div>

                            <div class="dato">

                                <?= htmlspecialchars(
                                    $movimiento['codigo']
                                ) ?>

                            </div>

                        </div>


                        <div>

                            <div class="dato-label">
                                Nombre del producto
                            </div>

                            <div class="dato">

                                <?= htmlspecialchars(
                                    $movimiento['nombre_producto']
                                ) ?>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- REFERENCIA -->

                <div class="col-md-6">

                    <div class="seccion">

                        <div class="titulo-seccion">
                            Referencia
                        </div>

                        <div class="dato">

                            <?php if (!empty($movimiento['referencia_ext'])): ?>

                                <?= htmlspecialchars(
                                    $movimiento['referencia_ext']
                                ) ?>

                            <?php else: ?>

                                <span class="text-muted">
                                    Sin referencia
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>


                <!-- USUARIO -->

                <div class="col-md-6">

                    <div class="seccion">

                        <div class="titulo-seccion">
                            Registrado por
                        </div>

                        <div class="usuario-box">

                            <div class="d-flex align-items-center">

                                <div class="icono-usuario me-3">
                                    👤
                                </div>

                                <div>

                                    <div class="dato">

                                        <?= !empty($movimiento['nombre_usuario'])
                                            ? htmlspecialchars($movimiento['nombre_usuario'])
                                            : 'Usuario no disponible'
                                        ?>

                                    </div>

                                    <?php if (!empty($movimiento['correo_usuario'])): ?>

                                        <div class="text-muted small">

                                            <?= htmlspecialchars(
                                                $movimiento['correo_usuario']
                                            ) ?>

                                        </div>

                                    <?php endif; ?>

                                    <?php if (!empty($movimiento['rol_usuario'])): ?>

                                        <span class="badge bg-secondary mt-1">

                                            <?= htmlspecialchars(
                                                $movimiento['rol_usuario']
                                            ) ?>

                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- COMENTARIO -->

                <div class="col-12">

                    <div class="seccion">

                        <div class="titulo-seccion">
                            Comentario
                        </div>

                        <div class="comentario-box">

                            <?php if (!empty($movimiento['comentario'])): ?>

                                <?= nl2br(
                                    htmlspecialchars(
                                        $movimiento['comentario']
                                    )
                                ) ?>

                            <?php else: ?>

                                <span class="text-muted">
                                    Sin comentarios.
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>