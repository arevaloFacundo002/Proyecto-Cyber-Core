<?php
/**
 * Arranque de la aplicacion (Opcion A: se mantienen las URLs actuales).
 *
 * Cada archivo de entrada (login.php, home.php, usuarios/listar.php, etc.)
 * incluye este bootstrap y luego delega en un controlador.
 *
 * Responsabilidades:
 *  - Definir las rutas base del proyecto.
 *  - Iniciar la sesion de forma segura.
 *  - Registrar un autoloader para modelos y controladores.
 */

// Raiz del proyecto (carpeta que contiene /app, /models, /config, ...)
define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH', ROOT_PATH . '/app');
define('MODELS_PATH', ROOT_PATH . '/models');

// Iniciar sesion una sola vez
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clase base de los controladores
require_once APP_PATH . '/core/Controller.php';

// Autoloader: busca la clase en controllers/ y luego en models/
spl_autoload_register(function (string $clase): void {
    $rutas = [
        APP_PATH . '/controllers/' . $clase . '.php',
        MODELS_PATH . '/' . $clase . '.php',
    ];

    foreach ($rutas as $ruta) {
        if (is_file($ruta)) {
            require_once $ruta;
            return;
        }
    }
});
