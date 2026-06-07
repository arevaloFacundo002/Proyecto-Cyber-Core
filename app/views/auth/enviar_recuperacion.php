<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>enviar</title>
</head>
<body>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (!empty($error)): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Error',
    text: '<?= $error ?>'
}).then(() => {
    window.location = '../../login.php';
});
</script>
<?php endif; ?>

<?php if (!empty($exito)): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Correo enviado',
    text: '<?= $exito ?>'
}).then(() => {
    window.location = '../../login.php';
});
</script>
<?php endif; ?>
    
</body>
</html>
