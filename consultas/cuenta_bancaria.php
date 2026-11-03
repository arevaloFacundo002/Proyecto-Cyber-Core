<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] ?? '') !== 'administrador') {
    header("Location: ../home.php");
    exit;
}

require_once __DIR__ . '/../models/Comprobante.php';

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['csrf_cuenta'])) {
    $_SESSION['csrf_cuenta'] = bin2hex(random_bytes(16));
}

$modelo = new Comprobante();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf_cuenta'], $token)) {
        $_SESSION['cuenta_flash'] = ['tipo' => 'error', 'texto' => 'La sesión del formulario venció. Probá de nuevo.'];
    } else {
        $r = $modelo->guardarCuenta($_POST);
        if (!empty($r['ok'])) {
            $_SESSION['cuenta_flash'] = ['tipo' => 'ok', 'texto' => 'Datos de la cuenta guardados.'];
        } else {
            $_SESSION['cuenta_flash'] = ['tipo' => 'error', 'texto' => $r['error']];
            $_SESSION['cuenta_old'] = $_POST;
        }
    }
    header("Location: cuenta_bancaria.php");
    exit;
}

$flash = $_SESSION['cuenta_flash'] ?? null;
$old   = $_SESSION['cuenta_old'] ?? null;
unset($_SESSION['cuenta_flash'], $_SESSION['cuenta_old']);

$c = $modelo->cuenta() ?? [];
$v = [
    'titular' => $old['titular'] ?? ($c['titular'] ?? ''),
    'banco'   => $old['banco']   ?? ($c['banco'] ?? ''),
    'cbu'     => $old['cbu']     ?? ($c['cbu'] ?? ''),
    'alias'   => $old['alias']   ?? ($c['alias'] ?? ''),
];

$ruta_raiz = '../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cuenta bancaria - CyberCore</title>
<link href="../css/theme.css" rel="stylesheet">
<?php require __DIR__ . '/_estilo_consultas.php'; ?>
<style>
    .container { max-width: 720px; }
    .campo-form { margin-bottom: 16px; }
    .campo-form label { display: block; font-size: 13px; font-weight: 600; color: var(--cc-texto-secundario); margin-bottom: 5px; }
    .campo-form input { width: 100%; box-sizing: border-box; background: var(--cc-superficie-2); color: var(--cc-texto);
        border: 1px solid var(--cc-borde); border-radius: 8px; padding: 10px; font-size: 14px; font-family: inherit; }
    .ayuda { font-size: 12px; color: var(--cc-texto-secundario); margin-top: 4px; }
    .fila2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    @media (max-width: 600px) { .fila2 { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<?php require __DIR__ . '/../includes/header_admin.php'; ?>

<div class="container">
    <h1>🏦 Cuenta bancaria</h1>
    <div class="subtitulo">Estos datos se le muestran al cliente cuando elige pagar por transferencia bancaria</div>

    <?php if ($flash): ?><div class="aviso <?= e($flash['tipo']) ?>"><?= e($flash['texto']) ?></div><?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_cuenta']) ?>">

        <div class="fila2">
            <div class="campo-form">
                <label>Titular de la cuenta</label>
                <input type="text" name="titular" maxlength="100" required value="<?= e($v['titular']) ?>" placeholder="Ej: CyberCore">
            </div>
            <div class="campo-form">
                <label>Banco</label>
                <input type="text" name="banco" maxlength="100" required value="<?= e($v['banco']) ?>" placeholder="Ej: Banco Nación">
            </div>
        </div>

        <div class="fila2">
            <div class="campo-form">
                <label>CBU</label>
                <input type="text" name="cbu" inputmode="numeric" maxlength="30" required value="<?= e($v['cbu']) ?>" placeholder="22 números">
                <div class="ayuda">Tiene que tener exactamente 22 números.</div>
            </div>
            <div class="campo-form">
                <label>Alias (opcional)</label>
                <input type="text" name="alias" maxlength="20" value="<?= e($v['alias']) ?>" placeholder="Ej: CYBERCORE.TIENDA">
                <div class="ayuda">De 6 a 20 caracteres: letras, números, puntos o guiones.</div>
            </div>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button class="btn-cc" type="submit">Guardar</button>
            <a class="btn-cc btn-sec" href="../inicio.php">Volver al panel</a>
        </div>
    </form>
</div>
<script src="../js/theme-toggle.js"></script>
</body>
</html>