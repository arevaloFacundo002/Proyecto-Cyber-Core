<?php

if (!isset($_ENV['APP_ENV_CARGADO'])) {
    $archivo = __DIR__ . '/../.env';

    if (is_file($archivo) && is_readable($archivo)) {
        foreach (file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            $linea = trim($linea);

            if ($linea === '' || $linea[0] === '#' || strpos($linea, '=') === false) {
                continue;
            }

            [$clave, $valor] = explode('=', $linea, 2);
            $clave = trim($clave);
            $valor = trim($valor);

            if (strlen($valor) >= 2 && ($valor[0] === '"' || $valor[0] === "'") && substr($valor, -1) === $valor[0]) {
                $valor = substr($valor, 1, -1);
            }

            if ($clave !== '' && !isset($_ENV[$clave])) {
                $_ENV[$clave] = $valor;
                putenv($clave . '=' . $valor);
            }
        }
    }

    $_ENV['APP_ENV_CARGADO'] = true;
}

function env_valor(string $clave, string $defecto = ''): string
{
    $v = $_ENV[$clave] ?? '';
    return ($v === '' || $v === null) ? $defecto : (string) $v;
}