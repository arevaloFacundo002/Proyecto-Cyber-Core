 <?php
/*
 * Header estándar del panel de administrador.
 *
 * Cómo usarlo en cualquier vista del admin:
 *
 *   1. Antes de incluir este archivo, definí $ruta_raiz apuntando a la
 *      carpeta donde están inicio.php, home.php y logout.php (la raíz
 *      del proyecto), según cuántas carpetas de profundidad tenga el
 *      archivo actual:
 *
 *        views/panel_tablas.php                  -> $ruta_raiz = '../';
 *        views/proveedores/listarProveedor.php    -> $ruta_raiz = '../../';
 *        views/tablas_maestras/marcas/listarMarca.php -> $ruta_raiz = '../../../';
 *
 *   2. Incluí este archivo donde quieras que aparezca el header:
 *        require __DIR__ . '/../includes/header_admin.php';
 *        (ajustando los '../' según la profundidad real)
 *
 *   3. Asegurate de que la página ya tenga <link href=".../css/theme.css">
 *      cargado en el <head> antes de este include, porque el header usa
 *      esas clases.
 */

if (!isset($ruta_raiz)) {
    $ruta_raiz = '';
}

$usuario_actual = $_SESSION['usuario'] ?? '';
?>
<header class="cc-header-admin">
    <div class="cc-logo">CYBERCORE PANEL</div>
    <div class="cc-user">
        Hola, <b><?= htmlspecialchars($usuario_actual) ?></b>
        <a class="cc-ir-tienda" href="<?= htmlspecialchars($ruta_raiz) ?>home.php">🛒 Tienda</a>
        <a class="cc-logout" href="<?= htmlspecialchars($ruta_raiz) ?>logout.php">Cerrar sesión</a>
    </div>
</header>