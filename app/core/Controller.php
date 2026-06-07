<?php
/**
 * Controlador base.
 *
 * Da a todos los controladores utilidades comunes:
 *  - render(): cargar una vista pasandole datos.
 *  - redirect(): redirigir y cortar la ejecucion.
 *  - requiereLogin(): proteger paginas que necesitan sesion iniciada.
 *
 * $base es el prefijo relativo desde el archivo de entrada hasta la raiz del
 * sitio (por ejemplo '' para home.php y '../' para usuarios/listar.php). Se usa
 * solo para la redireccion al login, de modo que cada pagina apunte al
 * login.php correcto sin importar en que carpeta este.
 */
abstract class Controller
{
    protected string $base;

    public function __construct(string $base = '')
    {
        $this->base = $base;
    }

    /**
     * Carga una vista de app/views y le inyecta las variables de $datos.
     */
    protected function render(string $vista, array $datos = []): void
    {
        extract($datos);
        require APP_PATH . '/views/' . $vista . '.php';
    }

    /**
     * Redirige a una URL y detiene el script.
     */
    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }

    /**
     * Protege una pagina: si no hay sesion iniciada manda al login.
     * Reemplaza al antiguo include de auth.php.
     */
    protected function requiereLogin(): void
    {
        if (!isset($_SESSION['usuario'])) {
            $this->redirect($this->base . 'login.php');
        }

        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        header("Expires: 0");
    }
}
