<?php
require_once '../../auth/auth.php';
require_once '../../models/inputs/Proveedor.php';

$prov = new Proveedor();

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: listarProveedor.php");
    exit();
}

$proveedor = $prov->obtenerPorId((int)$id);

if (!$proveedor) {
    header("Location: listarProveedor.php?error=1");
    exit();
}

?>
<!doctype html>
<html lang="es">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Editar Proveedor</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
        crossorigin="anonymous">

</head>

<body class="bg-light">

<div class="container mt-4 mb-5">

    <!-- VOLVER -->
    <a
        href="listarProveedor.php"
        class="btn btn-secondary mb-4">

        ← Volver

    </a>


    <!-- ENCABEZADO -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h2 class="fw-bold">

            ✏️ Editar Proveedor

        </h2>

    </div>


    <!-- FORMULARIO -->

    <div class="card shadow border-0">

        <div class="card-header bg-info text-dark">

            <h5 class="mb-0 fw-bold">

                Datos del proveedor

            </h5>

        </div>


        <div class="card-body p-4">

            <form
                action="../../../controllers/inputsControllers/ProveedorController.php?accion=editar"
                method="POST">


                <!-- ID OCULTO -->

                <input
                    type="hidden"
                    name="id_proveedor"
                    value="<?= $proveedor['id_proveedor'] ?>">


                <div class="row">


                    <!-- RAZÓN SOCIAL -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label fw-bold">

                            Razón Social
                        </label>

                        <input
                            type="text"
                            name="razon_social"
                            class="form-control"
                            value="<?= htmlspecialchars($proveedor['razon_social']) ?>"
                            placeholder="Ingrese la razón social"
                            required>

                    </div>


                    <!-- PERSONA DE CONTACTO -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label fw-bold">

                            Persona de Contacto

                        </label>

                        <input
                            type="text"
                            name="persona_contacto"
                            class="form-control"
                            value="<?= htmlspecialchars($proveedor['persona_contacto']) ?>"
                            placeholder="Ingrese el nombre del contacto">

                    </div>


                    <!-- EMAIL -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label fw-bold">

                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            value="<?= htmlspecialchars($proveedor['email']) ?>"
                            placeholder="ejemplo@empresa.com">

                    </div>


                    <!-- TELÉFONO -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label fw-bold">

                            Teléfono
                        </label>

                        <input
                            type="text"
                            name="telefono"
                            class="form-control"
                            value="<?= htmlspecialchars($proveedor['telefono']) ?>"
                            placeholder="Ingrese el teléfono">

                    </div>


                    <!-- DIRECCIÓN -->

                    <div class="col-md-8 mb-3">

                        <label class="form-label fw-bold">

                            Dirección

                        </label>

                        <input
                            type="text"
                            name="direccion"
                            class="form-control"
                            value="<?= htmlspecialchars($proveedor['direccion']) ?>"
                            placeholder="Ingrese la dirección">

                    </div>
                    

                </div>


                <!-- BOTONES -->

                <div class="d-flex justify-content-between mt-4">

                    <a
                        href="listarProveedor.php"
                        class="btn btn-secondary">

                        Cancelar
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


<!-- Bootstrap JS -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous">
</script>

</body>
</html>