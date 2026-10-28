<?php
// Galería de imágenes de los productos.
// Los archivos se guardan en img/productos/ y la tabla producto_imagenes guarda su ruta (relativa a img/).
// productos.imagen_url sigue guardando la imagen PRINCIPAL, así el inicio, el carrito y el checkout no cambian.
require_once __DIR__ . '/Database.php';

class ProductoImagen
{
    public const MAX_POR_PRODUCTO = 8;
    public const MAX_BYTES        = 4 * 1024 * 1024; // 4 MB por foto
    private const TIPOS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    private mysqli $conexion;
    private static bool $tablaLista = false;

    public function __construct()
    {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    // Configuración de Cloudinary; devuelve null si no está completa (en ese caso se guarda en el servidor)
    private function cloudinary(): ?array
    {
        static $cfg = false;
        if ($cfg === false) {
            $cfg = null;
            $ruta = __DIR__ . '/../config/cloudinary.php';
            if (is_file($ruta)) {
                $c = require $ruta;
                if (is_array($c) && !empty($c['cloud_name']) && !empty($c['api_key']) && !empty($c['api_secret'])
                    && strpos($c['cloud_name'] . $c['api_key'] . $c['api_secret'], 'TU_') === false) {
                    $c['api_base'] = rtrim($c['api_base'] ?? 'https://api.cloudinary.com/v1_1', '/');
                    $cfg = $c;
                }
            }
        }
        return $cfg;
    }

    public function usaCloudinary(): bool
    {
        return $this->cloudinary() !== null;
    }

    // Llamada firmada a la API de Cloudinary. Devuelve el JSON decodificado o lanza Exception.
    private function llamarCloudinary(string $accion, array $params, ?string $archivoLocal = null): array
    {
        $c = $this->cloudinary();
        $params['timestamp'] = time();

        // Firma: parámetros ordenados alfabéticamente (sin file/api_key) + API secret, con SHA-1
        $aFirmar = $params;
        ksort($aFirmar);
        $partes = [];
        foreach ($aFirmar as $k => $v) {
            $partes[] = $k . '=' . $v;
        }
        $params['api_key']   = $c['api_key'];
        $params['signature'] = sha1(implode('&', $partes) . $c['api_secret']);

        if ($archivoLocal !== null) {
            $params['file'] = new CURLFile($archivoLocal);
        }

        $ch = curl_init($c['api_base'] . '/' . rawurlencode($c['cloud_name']) . '/image/' . $accion);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $params,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 60,
        ]);
        if (!empty($c['ca_bundle'])) {
            curl_setopt($ch, CURLOPT_CAINFO, $c['ca_bundle']);
        }

        $resp  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            throw new Exception('No se pudo conectar con Cloudinary (' . $error . ').');
        }
        $json = json_decode($resp, true);
        if (!is_array($json)) {
            throw new Exception('Respuesta inesperada de Cloudinary.');
        }
        if (isset($json['error']['message'])) {
            throw new Exception('Cloudinary: ' . $json['error']['message']);
        }
        return $json;
    }

    // Sube una foto y devuelve ['public_id' => ..., 'url' => ...].
    private function subirACloudinary(string $tmp): array
    {
        $c = $this->cloudinary();
        $json = $this->llamarCloudinary('upload', ['folder' => $c['folder'] ?? 'cybercore/productos'], $tmp);
        if (empty($json['secure_url'])) {
            throw new Exception('Cloudinary no devolvió la URL de la imagen.');
        }
        return [
            'public_id' => $json['public_id'] ?? ($this->publicIdDesdeUrl($json['secure_url']) ?? $json['secure_url']),
            'url'       => $json['secure_url'],
        ];
    }

    // De "https://res.cloudinary.com/xxx/image/upload/v123/carpeta/abc.jpg" saca "carpeta/abc"
    private function publicIdDesdeUrl(string $url): ?string
    {
        if (!preg_match('#/image/upload/(?:[^/]+/)*?(?:v\d+/)?(.+?)\.[a-z0-9]+$#i', $url, $m)) {
            return null;
        }
        return $m[1];
    }

    private function borrarDeCloudinary(string $ruta, string $url): void
    {
        if ($this->cloudinary() === null || strpos($url, 'res.cloudinary.com') === false) {
            return;
        }
        $publicId = preg_match('#^https?://#i', $ruta) ? $this->publicIdDesdeUrl($url) : $ruta;
        if (!$publicId) {
            return;
        }
        try {
            $this->llamarCloudinary('destroy', ['public_id' => urldecode($publicId)]);
        } catch (Throwable $e) {
            error_log('No se pudo borrar la imagen de Cloudinary: ' . $e->getMessage());
        }
    }

    private function carpetaFisica(): string
    {
        return realpath(__DIR__ . '/../img') . DIRECTORY_SEPARATOR . 'productos';
    }

    // Crea la tabla si no existe (hacerlo siempre fuera de una transacción).
    public function asegurarTabla(): void
    {
        if (self::$tablaLista) {
            return;
        }
        $this->conexion->query(
            "CREATE TABLE IF NOT EXISTS producto_imagenes (
                id_imagen        INT NOT NULL AUTO_INCREMENT,
                rela_id_producto INT NOT NULL,
                ruta_imagen      VARCHAR(255) NOT NULL,
                url_original     VARCHAR(500) NULL,
                orden            INT NOT NULL DEFAULT 1,
                es_principal     TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (id_imagen),
                KEY idx_imagen_producto (rela_id_producto),
                CONSTRAINT fk_imagen_producto FOREIGN KEY (rela_id_producto)
                    REFERENCES productos (id_producto) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        self::$tablaLista = true;
    }

    // Imágenes de un producto, la principal primero. Para el administrador (crea la tabla y migra la imagen vieja).
    public function listar(int $id_producto): array
    {
        $this->asegurarTabla();
        $this->migrarImagenVieja($id_producto);
        return $this->consultar($id_producto);
    }

    // Para la tienda: nunca falla. Si la galería no existe, devuelve solo la imagen del producto.
    public function listarPublico(int $id_producto, ?string $imagen_url = null): array
    {
        try {
            $filas = $this->consultar($id_producto);
            if ($filas) {
                return $filas;
            }
        } catch (Throwable $e) {
            // la tabla todavía no existe
        }

        return ($imagen_url !== null && $imagen_url !== '')
            ? [['id_imagen' => 0, 'archivo' => $imagen_url, 'es_principal' => 1]]
            : [];
    }

    private function consultar(int $id_producto): array
    {
        $st = $this->conexion->prepare(
            "SELECT id_imagen, ruta_imagen, url_original,
                    COALESCE(url_original, ruta_imagen) AS archivo,
                    orden, es_principal
             FROM producto_imagenes WHERE rela_id_producto = ?
             ORDER BY es_principal DESC, orden ASC, id_imagen ASC"
        );
        $st->bind_param("i", $id_producto);
        $st->execute();

        $filas = [];
        $r = $st->get_result();
        while ($f = $r->fetch_assoc()) {
            $filas[] = $f;
        }
        return $filas;
    }

    // Si el producto tenía imagen_url pero no tiene filas en la galería, la pasa a la galería.
    private function migrarImagenVieja(int $id_producto): void
    {
        if ($this->consultar($id_producto)) {
            return;
        }
        $st = $this->conexion->prepare("SELECT imagen_url FROM productos WHERE id_producto = ?");
        $st->bind_param("i", $id_producto);
        $st->execute();
        $p = $st->get_result()->fetch_assoc();

        if ($p && !empty($p['imagen_url'])) {
            $ruta = $p['imagen_url'];
            $url  = preg_match('#^https?://#i', $ruta) ? $ruta : null;
            $st = $this->conexion->prepare(
                "INSERT INTO producto_imagenes (rela_id_producto, ruta_imagen, url_original, orden, es_principal) VALUES (?, ?, ?, 1, 1)"
            );
            $st->bind_param("iss", $id_producto, $ruta, $url);
            $st->execute();
        }
    }

    // Guarda las fotos subidas ($_FILES['imagenes']). Devuelve ['guardadas' => n, 'errores' => [...]]
    public function guardarSubidas(int $id_producto, array $files): array
    {
        $this->asegurarTabla();
        $this->migrarImagenVieja($id_producto);

        $resultado = ['guardadas' => 0, 'errores' => [], 'ids' => []];
        if (empty($files['name'])) {
            return $resultado;
        }

        $nombres = (array) $files['name'];
        $total   = count($this->consultar($id_producto));

        $enNube = $this->usaCloudinary();
        $dir    = $this->carpetaFisica();
        if (!$enNube && !is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            $resultado['errores'][] = 'No se pudo crear la carpeta img/productos.';
            return $resultado;
        }

        foreach ($nombres as $i => $nombreOriginal) {
            $error = $files['error'][$i];
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $etiqueta = '«' . mb_substr((string) $nombreOriginal, 0, 40) . '»';

            if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                $resultado['errores'][] = "$etiqueta supera el tamaño que permite el servidor.";
                continue;
            }
            if ($error !== UPLOAD_ERR_OK) {
                $resultado['errores'][] = "$etiqueta no se pudo subir.";
                continue;
            }
            if ($total >= self::MAX_POR_PRODUCTO) {
                $resultado['errores'][] = "Máximo " . self::MAX_POR_PRODUCTO . " fotos por producto ($etiqueta no se guardó).";
                continue;
            }

            $tmp = $files['tmp_name'][$i];
            if ($files['size'][$i] > self::MAX_BYTES) {
                $resultado['errores'][] = "$etiqueta pesa más de 4 MB.";
                continue;
            }

            // Se valida el contenido real del archivo, no solo la extensión
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
            if (!isset(self::TIPOS[$mime]) || @getimagesize($tmp) === false) {
                $resultado['errores'][] = "$etiqueta no es una imagen válida (usá JPG, PNG o WebP).";
                continue;
            }

            if ($enNube) {
                try {
                    $sub = $this->subirACloudinary($tmp);
                    $ruta = $sub['public_id'];
                    $url  = $sub['url'];
                } catch (Throwable $e) {
                    $resultado['errores'][] = "$etiqueta no se pudo subir a la nube. " . $e->getMessage();
                    continue;
                }
            } else {
                $archivo = 'p' . $id_producto . '_' . bin2hex(random_bytes(6)) . '.' . self::TIPOS[$mime];
                if (!move_uploaded_file($tmp, $dir . DIRECTORY_SEPARATOR . $archivo)) {
                    $resultado['errores'][] = "$etiqueta no se pudo guardar en el servidor.";
                    continue;
                }
                $ruta = 'productos/' . $archivo;
                $url  = null;
            }

            $stOrden = $this->conexion->prepare("SELECT COALESCE(MAX(orden), 0) + 1 FROM producto_imagenes WHERE rela_id_producto = ?");
            $stOrden->bind_param("i", $id_producto);
            $stOrden->execute();
            $orden = (int) $stOrden->get_result()->fetch_row()[0];
            $st = $this->conexion->prepare(
                "INSERT INTO producto_imagenes (rela_id_producto, ruta_imagen, url_original, orden, es_principal) VALUES (?, ?, ?, ?, 0)"
            );
            $st->bind_param("issi", $id_producto, $ruta, $url, $orden);
            $st->execute();

            $resultado['ids'][$i] = (int) $this->conexion->insert_id;
            $total++;
            $resultado['guardadas']++;
        }

        $this->normalizarPrincipal($id_producto);
        return $resultado;
    }

    // Borra imágenes (solo si son de este producto). El archivo físico se borra solo si está en img/productos/.
    public function eliminar(int $id_producto, array $ids): void
    {
        $this->asegurarTabla();

        foreach ($ids as $id) {
            $id = (int) $id;
            $st = $this->conexion->prepare(
                "SELECT ruta_imagen, url_original FROM producto_imagenes WHERE id_imagen = ? AND rela_id_producto = ?"
            );
            $st->bind_param("ii", $id, $id_producto);
            $st->execute();
            $f = $st->get_result()->fetch_assoc();
            if (!$f) {
                continue;
            }

            $st = $this->conexion->prepare("DELETE FROM producto_imagenes WHERE id_imagen = ? AND rela_id_producto = ?");
            $st->bind_param("ii", $id, $id_producto);
            $st->execute();

            if (!empty($f['url_original'])) {
                // Imagen en la nube: en ruta_imagen está el public_id (o la URL si es una imagen cargada a mano)
                $this->borrarDeCloudinary($f['ruta_imagen'], $f['url_original']);
            } elseif (strpos($f['ruta_imagen'], 'productos/') === 0 && basename($f['ruta_imagen']) === substr($f['ruta_imagen'], 10)) {
                $ruta = $this->carpetaFisica() . DIRECTORY_SEPARATOR . basename($f['ruta_imagen']);
                if (is_file($ruta)) {
                    @unlink($ruta);
                }
            }
        }
        $this->normalizarPrincipal($id_producto);
    }

    public function marcarPrincipal(int $id_producto, int $id_imagen): void
    {
        $this->asegurarTabla();

        $st = $this->conexion->prepare("SELECT 1 FROM producto_imagenes WHERE id_imagen = ? AND rela_id_producto = ?");
        $st->bind_param("ii", $id_imagen, $id_producto);
        $st->execute();
        if (!$st->get_result()->fetch_row()) {
            return;
        }

        $st = $this->conexion->prepare("UPDATE producto_imagenes SET es_principal = (id_imagen = ?) WHERE rela_id_producto = ?");
        $st->bind_param("ii", $id_imagen, $id_producto);
        $st->execute();

        $this->normalizarPrincipal($id_producto);
    }

    // Garantiza una sola principal (la primera si falta) y copia su ruta a productos.imagen_url.
    public function normalizarPrincipal(int $id_producto): void
    {
        $filas = $this->consultar($id_producto);

        if (!$filas) {
            $st = $this->conexion->prepare("UPDATE productos SET imagen_url = NULL WHERE id_producto = ?");
            $st->bind_param("i", $id_producto);
            $st->execute();
            return;
        }

        $principal = $filas[0]; // consultar() ordena la principal primero
        $st = $this->conexion->prepare("UPDATE producto_imagenes SET es_principal = (id_imagen = ?) WHERE rela_id_producto = ?");
        $st->bind_param("ii", $principal['id_imagen'], $id_producto);
        $st->execute();

        $st = $this->conexion->prepare("UPDATE productos SET imagen_url = ? WHERE id_producto = ?");
        $st->bind_param("si", $principal['archivo'], $id_producto);
        $st->execute();
    }
}