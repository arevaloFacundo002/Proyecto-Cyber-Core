<?php

require_once '../../auth/auth.php';

?>

<!doctype html>
<html lang="es">

<head>

    <meta charset="utf-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1">

    <meta http-equiv="Cache-Control" content="no-store">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <title>Nuevo Proveedor</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <style>

        body {
            background-color: #f4f4f4;
        }

        .card-form {
            max-width: 800px;
            margin: 40px auto;
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }

        .card-header-custom {
            background: #16c5d8;
            color: white;
            padding: 20px;
        }

        .card-header-custom h2 {
            margin: 0;
            font-weight: bold;
        }

        .btn-sistema {
            background: #16c5d8;
            border: none;
            font-weight: bold;
        }

        .btn-sistema:hover {
            background: #12b1c1;
        }

        .form-control {
            border-radius: 10px;
        }

        .form-label {
            margin-bottom: 6px;
        }

    </style>

</head>


<body>


<div class="container">

    <div class="card card-form shadow">


        <!-- ENCABEZADO -->

        <div class="card-header-custom">

            <h2>
                🏢 Nuevo Proveedor
            </h2>

        </div>


        <!-- FORMULARIO -->

        <div class="card-body p-4">

            <form
                action="../../../controllers/inputsControllers/ProveedorController.php?accion=crear"
                method="POST">


                <!-- NOMBRE / EMPRESA -->

                <div class="mb-3">

                    <label class="form-label fw-bold">

                        Empresa / Razón Social

                    </label>

                    <input
                        type="text"
                        name="razon_social"
                        class="form-control"
                        placeholder="Ingrese el nombre de la empresa o razón social"
                        maxlength="100"
                        required>

                </div>


                <!-- CONTACTO -->

                <div class="mb-3">

                    <label class="form-label fw-bold">

                        Contacto / Persona

                    </label>

                    <input
                        type="text"
                        name="persona_contacto"
                        class="form-control"
                        placeholder="Nombre de la persona de contacto"
                        maxlength="100">

                </div>


                <!-- EMAIL -->

                <div class="mb-3">

                    <label class="form-label fw-bold">

                        Correo electrónico

                    </label>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        placeholder="ejemplo@correo.com"
                        maxlength="100">

                </div>


                <!-- DIRECCIÓN -->

                <div class="mb-3">

                    <label class="form-label fw-bold">

                        Dirección

                    </label>

                    <input
                        type="text"
                        name="direccion"
                        class="form-control"
                        placeholder="Ingrese la dirección"
                        maxlength="150">

                </div>


                <!-- TELÉFONO -->

                <div class="mb-3">

                    <label class="form-label fw-bold">

                        Teléfono

                    </label>

                    <input
                        type="text"
                        name="telefono"
                        class="form-control"
                        placeholder="Ingrese el número de teléfono"
                        maxlength="45">

                </div>


                <!-- BOTONES -->

                <div class="d-flex justify-content-between">


                    <a
                        href="listarProveedor.php"
                        class="btn btn-secondary">

                        ← Volver

                    </a>


                    <button
                        type="submit"
                        class="btn btn-sistema text-white px-4">

                        Guardar Proveedor

                    </button>


                </div>


            </form>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>