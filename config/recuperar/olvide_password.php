
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contraseña</title>
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

    <form action="enviar_recuperacion.php" method="POST">
        <input type="email" name="correo" placeholder="Ingresa tu correo para recuperar tu contraseña" required>
        <button type="submit">Recuperar contraseña</button>
    </form>
</div>
    
</body>
</html>
