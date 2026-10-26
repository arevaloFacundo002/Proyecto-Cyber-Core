<?php
/**
 * config/env.php
 * Carga el archivo .env una sola vez. Todos los demás archivos de config
 * (Database, mail, pusher, whatsapp) lo requieren antes de usar $_ENV.
 */
 
require_once __DIR__ . '/../vendor/autoload.php';
 
if (!isset($_ENV['APP_ENV_CARGADO'])) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad(); // safeLoad no rompe si falta el .env (ej: en un server donde las variables se setean distinto)
    $_ENV['APP_ENV_CARGADO'] = true;
}
 