 <!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña - CyberCore</title>
<link href="../../css/theme.css" rel="stylesheet">
<style>
    body {
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    .login-box {
        background: var(--cc-superficie);
        padding: 40px;
        width: 380px;
        border-radius: 15px;
        box-shadow: 0 0 25px rgba(0, 234, 255, 0.25);
        border: 1px solid var(--cc-borde);
        text-align: center;
        color: var(--cc-texto);
    }

    .logo {
        font-size: 32px;
        color: var(--cc-primario);
        font-weight: bold;
        margin-bottom: 25px;
    }

    input {
        width: 90%;
        box-sizing: border-box;
        padding: 14px;
        margin: 10px 0;
        border-radius: 8px;
        border: 1px solid var(--cc-borde);
        background: var(--cc-fondo);
        color: var(--cc-texto);
    }

    button {
        width: 95%;
        padding: 14px;
        margin-top: 15px;
        background: var(--cc-primario);
        color: var(--cc-texto-sobre-primario);
        border: none;
        border-radius: 25px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.2s;
    }

    button:hover {
        background: var(--cc-primario-hover);
    }

    .volver-login {
        display: inline-block;
        margin-top: 18px;
        color: var(--cc-primario);
        text-decoration: none;
        font-size: 14px;
    }

    .volver-login:hover {
        text-decoration: underline;
    }
</style>
</head>

<body>
    
<div class="login-box">

    <div class="logo">CYBERCORE</div>
    <h2>Cambiar contraseña</h2>

    <form action="enviar_recuperacion.php" method="POST">
        <input type="email" name="correo" placeholder="Ingresa tu correo para recuperar tu contraseña" required>
        <button type="submit">Recuperar contraseña</button>
    </form>

    <a class="volver-login" href="../../login.php">← Volver al login</a>
</div>
    
<script src="../../js/theme-toggle.js"></script>
</body>
</html>