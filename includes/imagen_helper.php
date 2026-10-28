<?php
// Devuelve la URL para mostrar una imagen de producto.
// - Si es de Cloudinary (empieza con http) se usa tal cual.
// - Si es un archivo local (ej: "i5.png" o "productos/p1_ab.png") se le antepone la carpeta img/.
// $prefijo es la ruta hasta la raíz del proyecto: '' desde la raíz, '../' desde carrito/, '../../' desde views/xxx/
if (!function_exists('urlImagen')) {
    function urlImagen(?string $archivo, string $prefijo = ''): string
    {
        $archivo = (string) $archivo;
        if (preg_match('#^https?://#i', $archivo)) {
            return $archivo;
        }
        return $prefijo . 'img/' . $archivo;
    }
}