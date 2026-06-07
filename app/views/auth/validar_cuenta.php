<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validacion de cuenta</title>
</head>
<body>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if ($error != ""): ?>
    <script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?= $error ?> Serás redirigido al login...'
    }).then(() => {
        window.location = 'login.php';
    });
    </script>
    <?php endif; ?>

    <?php if ($exito != ""): ?>
    <script>
    Swal.fire({
        icon: 'success',
        title: 'Éxito',
        text: '<?= $exito ?> Serás redirigido al login...'
    }).then(() => {
        window.location = 'login.php';
    });
    </script>
    <?php endif; ?>

</body>
</html>
