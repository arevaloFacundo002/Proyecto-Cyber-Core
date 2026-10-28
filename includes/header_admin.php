<?php
// Encabezado del panel. Antes de incluirlo: $ruta_raiz = '../'; (según la profundidad de la página)

if (!isset($ruta_raiz)) {
    $ruta_raiz = '';
}

$usuario_actual = $_SESSION['usuario'] ?? '';
?>
<header class="cc-header-admin">
    <div class="cc-logo">CYBERCORE PANEL</div>
    <div class="cc-user">
        Hola, <b><?= htmlspecialchars($usuario_actual) ?></b>
        <?php include __DIR__ . '/campana.php'; ?>
        <a class="cc-ir-tienda" href="<?= htmlspecialchars($ruta_raiz) ?>home.php">🛒 Tienda</a>
        <a class="cc-logout" href="<?= htmlspecialchars($ruta_raiz) ?>logout.php">Cerrar sesión</a>
    </div>
</header>