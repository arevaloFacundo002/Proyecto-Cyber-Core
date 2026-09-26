 <?php
/**
 * add_toggle.php
 *
 * Busca todos los archivos .php del proyecto que ya tienen
 * el link a css/theme.css, y les agrega el <script> del
 * interruptor de modo claro/oscuro justo antes de </body>.
 *
 * COMO USARLO:
 * 1. Copia este archivo a la RAIZ del proyecto.
 * 2. Corre: php add_toggle.php
 * 3. Revisa el reporte.
 * 4. Podes borrar este archivo despues.
 */

$raiz = __DIR__;

$carpetasIgnoradas = [
    'vendor',
    'auth',
    'models',
    'controllers',
    '.git',
    'ajax',
    'config',
];

$archivosModificados = [];
$archivosOmitidos = [];

function debeIgnorar($rutaRelativa, $carpetasIgnoradas)
{
    $partes = explode(DIRECTORY_SEPARATOR, $rutaRelativa);
    foreach ($carpetasIgnoradas as $carpeta) {
        if (in_array($carpeta, $partes)) {
            return true;
        }
    }
    return false;
}

function calcularProfundidad($rutaRelativa)
{
    $partes = explode(DIRECTORY_SEPARATOR, $rutaRelativa);
    return count($partes) - 1;
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $archivo) {

    if ($archivo->getExtension() !== 'php') {
        continue;
    }

    $rutaCompleta = $archivo->getPathname();
    $rutaRelativa = ltrim(str_replace($raiz, '', $rutaCompleta), DIRECTORY_SEPARATOR);

    if (debeIgnorar($rutaRelativa, $carpetasIgnoradas)) {
        continue;
    }

    $contenido = file_get_contents($rutaCompleta);

    // Solo tocamos archivos que ya usan theme.css
    if (strpos($contenido, 'theme.css') === false) {
        continue;
    }

    // Si ya tiene el script del toggle, no lo tocamos de nuevo
    if (strpos($contenido, 'theme-toggle.js') !== false) {
        $archivosOmitidos[] = $rutaRelativa . '  (ya tenia el toggle)';
        continue;
    }

    // No tiene </body>? lo omitimos (puede ser un archivo de logica pura)
    if (stripos($contenido, '</body>') === false) {
        $archivosOmitidos[] = $rutaRelativa . '  (no se encontro </body>)';
        continue;
    }

    $profundidad = calcularProfundidad($rutaRelativa);
    $prefijo = str_repeat('../', $profundidad);
    $lineaScript = '<script src="' . $prefijo . 'js/theme-toggle.js"></script>';

    // Insertamos el script justo antes de la primera </body> (case-insensitive)
    $posicion = stripos($contenido, '</body>');

    $nuevoContenido =
        substr($contenido, 0, $posicion)
        . $lineaScript . "\n"
        . substr($contenido, $posicion);

    file_put_contents($rutaCompleta, $nuevoContenido);

    $archivosModificados[] = $rutaRelativa . '  ->  ' . $lineaScript;
}

echo "=========================================\n";
echo "ARCHIVOS MODIFICADOS (" . count($archivosModificados) . ")\n";
echo "=========================================\n";
foreach ($archivosModificados as $linea) {
    echo $linea . "\n";
}

echo "\n";
echo "=========================================\n";
echo "ARCHIVOS OMITIDOS (" . count($archivosOmitidos) . ")\n";
echo "=========================================\n";
foreach ($archivosOmitidos as $linea) {
    echo $linea . "\n";
}

echo "\nListo. Revisa el navegador para confirmar que todo se ve bien.\n";