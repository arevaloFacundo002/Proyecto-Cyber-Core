<?php
# Este archivo lo usan todas las paginas protegidas:
# 1) si no hay sesion, manda al login
# 2) segun el rol de la persona, decide si puede entrar a esa pagina (aunque escriba la direccion a mano)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ruta de esta pagina dentro del proyecto (ej: "views/productos/listarProducto.php")
$_raiz    = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
$_archivo = str_replace('\\', '/', (string) realpath($_SERVER['SCRIPT_FILENAME'] ?? ''));
$_rel     = ltrim(substr($_archivo, strlen($_raiz)), '/');

// Direccion web de la carpeta del proyecto (para poder redirigir bien)
$_script = $_SERVER['SCRIPT_NAME'] ?? '';
$_base   = ($_rel !== '' && substr($_script, -strlen($_rel)) === $_rel)
    ? substr($_script, 0, -strlen($_rel) - 1)
    : '/DESARROLLO CYBER CORE/Proyecto-Cyber-Core';
$_base = str_replace(' ', '%20', $_base);

if (!isset($_SESSION['usuario'])) {
    header("Location: " . $_base . "/login.php");
    exit();
}

// ---------------------------------------------------------------
// PERMISOS POR ROL
//   administrador -> todo
//   empleado      -> operacion diaria (productos, proveedores, stock, facturas,
//                    pedidos, devoluciones, caja y consultas)
//   cliente/usuario -> solo la tienda
// Se puede tener la cantidad de empleados que haga falta.
// ---------------------------------------------------------------

// Paginas que puede abrir cualquier persona con sesion (aunque no sea personal del negocio)
$_libres = [
    'views/notificaciones/historial.php',
    'clientes/completar_datos.php',
    'clientes/obtener_localidades.php',
];

// Solo el administrador
$_solo_admin = [
    'usuarios/',
    'views/tablas_maestras/',
    'controllers/tablasMaestrasControllers/',
    'views/cupones/',
    'views/promociones/',
    'views/anuncios/',
    'views/panel_tablas.php',
    'consultas/cuenta_bancaria.php',
    'consultas/auditoria.php',
    'reportes/reporte_usuarios.php',
    'clientes/crear_cliente.php',
    'clientes/editar_cliente.php',
];

// Administrador y empleado
$_personal = [
    'views/',
    'controllers/',
    'consultas/',
    'reportes/',
    'inicio.php',
];

function _empieza_con(string $ruta, array $prefijos): bool
{
    foreach ($prefijos as $p) {
        if ($ruta === $p || (substr($p, -1) === '/' && strpos($ruta, $p) === 0)) {
            return true;
        }
    }
    return false;
}

$_rol = $_SESSION['rol'] ?? '';
$_permitido = true;

if (_empieza_con($_rel, $_libres)) {
    $_permitido = true;
} elseif (_empieza_con($_rel, $_solo_admin)) {
    $_permitido = ($_rol === 'administrador');
} elseif (_empieza_con($_rel, $_personal)) {
    $_permitido = in_array($_rol, ['administrador', 'empleado'], true);
}

if (!$_permitido) {
    header("Location: " . $_base . "/home.php");
    exit();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");