<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar </title>
</head>
<style>
    body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #0a0a0a, #090e13ff);
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    }

    .login-box {
        background: #111;
        padding: 40px;
        width: 380px;
        border-radius: 15px;
        box-shadow: 0 0 25px #00eaff44;
        text-align: center;
    }

    .logo {
        font-size: 32px;
        color: #00eaff;
        font-weight: bold;
        margin-bottom: 25px;
    }

    input {
        width: 90%;
        padding: 14px;
        margin: 10px 0;
        border-radius: 8px;
        border: none;
        background: #1c1c1c;
        color: white;
    }

    button {
        width: 95%;
        padding: 14px;
        margin-top: 15px;
        background: #00eaff;
        border-radius: 25px;
        font-weight: bold;
        cursor: pointer;
    }
</style>
<body>

<div class="login-box">
    <div class="logo">CYBERCORE</div>
    <h2>Cambiar contraseña</h2>

    <form method="POST">
        <input type="password" name="password" placeholder="Ingrese su nueva contraseña" required>
        <input type="password" name="password2" placeholder="Repetir contraseña" required>
        <button name="guardar">Guardar</button>
    </form>

</div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <?php if (!empty($error)): ?>
    <script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?= $error ?>'
    });
    </script>
    <?php endif; ?>

    <?php if (!empty($exito)): ?>
    <script>
    Swal.fire({
        icon: 'success',
        title: 'Éxito',
        text: '<?= $exito ?>'
    }).then(() => {
        window.location = '../../login.php';
    });
    </script>
    <?php endif; ?>
</body>
</html>
