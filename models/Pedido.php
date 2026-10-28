<?php

// Plan B por si el servidor no tiene activada la extensión "mbstring" de PHP
// (en Laragon suele venir activada; esto evita un error fatal si no lo está).
if (!function_exists('mb_substr')) {
    function mb_substr($s, $start, $len = null, $enc = null) { return substr($s, $start, $len); }
}
if (!function_exists('mb_strtolower')) {
    function mb_strtolower($s, $enc = null) { return strtolower($s); }
}
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper($s, $enc = null) { return strtoupper($s); }
}
if (!defined('MB_CASE_TITLE')) {
    define('MB_CASE_TITLE', 2);
}
if (!function_exists('mb_convert_case')) {
    function mb_convert_case($s, $modo, $enc = null) { return ucwords(strtolower($s)); }
}

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../includes/avisos.php';

// Errores "esperables" (stock, datos faltantes...) cuyo mensaje sí se le muestra al usuario.
class PedidoException extends Exception {}

class Pedido
{
    private mysqli $conexion;

    // Estados estándar del pedido, en orden. Lo usan tanto el panel admin
    // (para el <select> de cambiar estado) como el seguimiento del cliente
    // (para saber hasta dónde "rellenar" la línea de tiempo).
    public const ESTADOS = [
        'Pedido recibido'      => ['emoji' => '🟡', 'color' => '#facc15'],
        'Confirmado'           => ['emoji' => '🔵', 'color' => '#38bdf8'],
        'Preparando pedido'    => ['emoji' => '🟠', 'color' => '#fb923c'],
        'Listo para despachar' => ['emoji' => '🟣', 'color' => '#c084fc'],
        'En camino'            => ['emoji' => '🚚', 'color' => '#22d3ee'],
        'Entregado'            => ['emoji' => '🟢', 'color' => '#22c55e'],
        'Cancelado'            => ['emoji' => '🔴', 'color' => '#ef4444'],
    ];

    public function __construct()
    {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    // =====================================================
    //  DATOS DEL CLIENTE (a partir del usuario logueado)
    // =====================================================
    public function obtenerCliente(int $id_usuario): ?array
    {
        $sql = "SELECT c.id_cliente, c.nombre, c.apellido,
                       d.calle, d.numero_exterior, d.barrio_colonia, d.piso_departamento,
                       l.id_localidad, l.nombre_localidad, l.codigo_postal,
                       p.nombre_provincia, p.dias_transitos_base
                FROM clientes c
                INNER JOIN direcciones d ON d.id_direccion = c.rela_id_direccion
                LEFT JOIN localidades l ON l.id_localidad = d.rela_id_localidad
                LEFT JOIN provincias p ON p.id_provincia = l.rela_id_provincia
                WHERE c.rela_id_usuario = ?
                LIMIT 1";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();

        $fila = $stmt->get_result()->fetch_assoc();

        return $fila ?: null;
    }

    public function direccionEnTexto(array $c): string
    {
        $partes = [];

        $calle = trim(($c['calle'] ?? '') . ' ' . ($c['numero_exterior'] ?? ''));
        if ($calle !== '') {
            $partes[] = $calle;
        }
        if (!empty($c['piso_departamento'])) {
            $partes[] = 'Piso/Depto ' . $c['piso_departamento'];
        }
        if (!empty($c['barrio_colonia'])) {
            $partes[] = $c['barrio_colonia'];
        }
        if (!empty($c['nombre_localidad'])) {
            $loc = $c['nombre_localidad'];
            if (!empty($c['codigo_postal'])) {
                $loc .= ' (CP ' . $c['codigo_postal'] . ')';
            }
            $partes[] = $loc;
        }
        if (!empty($c['nombre_provincia'])) {
            $partes[] = $c['nombre_provincia'];
        }

        return implode(', ', $partes);
    }

    // =====================================================
    //  MÉTODOS DE PAGO ACTIVOS
    // =====================================================
    public function listarMetodosPago(): array
    {
        $res = $this->conexion->query(
            "SELECT id_metodo_pago, nombre, descripcion
             FROM metodos_pago
             WHERE es_activo = 1
             ORDER BY nombre"
        );

        $metodos = [];
        while ($fila = $res->fetch_assoc()) {
            $metodos[] = $fila;
        }
        return $metodos;
    }

    // =====================================================
    //  ENVÍO
    // =====================================================
    private function buscarTarifa(?int $id_localidad): ?array
    {
        if ($id_localidad) {
            $stmt = $this->conexion->prepare(
                "SELECT * FROM tarifa_envios
                 WHERE rela_id_localidades = ?
                 ORDER BY id_tarifa_envios
                 LIMIT 1"
            );
            $stmt->bind_param("i", $id_localidad);
            $stmt->execute();
            $fila = $stmt->get_result()->fetch_assoc();
            if ($fila) {
                return $fila;
            }
        }

        // Si la localidad no tiene tarifa propia usamos la primera cargada.
        $res = $this->conexion->query(
            "SELECT * FROM tarifa_envios ORDER BY id_tarifa_envios LIMIT 1"
        );
        $fila = $res->fetch_assoc();

        return $fila ?: null;
    }

    // Costo = costo base + costo adicional por kg * peso total.
    private function costoEnvio(array $tarifa, float $pesoTotal): float
    {
        $base      = (float) $tarifa['costo_base'];
        $adicional = (float) $tarifa['costo_adicional_por_kg'];

        return round($base + ($adicional * $pesoTotal), 2);
    }

    // =====================================================
    //  LÍNEAS DEL CARRITO (precios y stock REALES, de la base)
    // =====================================================
    private function leerLineas(array $carrito, bool $bloquear): array
    {
        $lineas    = [];
        $problemas = [];

        $sql = "SELECT id_producto, nombre, precio, stock, imagen_url, peso_envio
                FROM productos
                WHERE id_producto = ?
                AND COALESCE(es_descontinuado, 0) = 0";

        if ($bloquear) {
            $sql .= " FOR UPDATE";
        }

        $stmt = $this->conexion->prepare($sql);

        // Ordenamos por id para bloquear siempre en el mismo orden.
        usort($carrito, function ($a, $b) {
            return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
        });

        foreach ($carrito as $item) {
            $id       = (int) ($item['id'] ?? 0);
            $cantidad = (int) ($item['cantidad'] ?? 0);

            if ($id <= 0 || $cantidad <= 0) {
                continue;
            }

            $stmt->bind_param("i", $id);
            $stmt->execute();
            $p = $stmt->get_result()->fetch_assoc();

            if (!$p) {
                $problemas[] = "Un producto de tu carrito ya no está disponible.";
                continue;
            }

            if ((int) $p['stock'] < $cantidad) {
                $problemas[] = "«" . $p['nombre'] . "»: pediste " . $cantidad
                             . " pero solo quedan " . (int) $p['stock'] . ".";
            }

            $precio = (float) $p['precio'];
            $peso   = (float) $p['peso_envio'];

            $lineas[] = [
                'id'       => (int) $p['id_producto'],
                'nombre'   => $p['nombre'],
                'imagen'   => $p['imagen_url'],
                'precio'   => $precio,
                'cantidad' => $cantidad,
                'subtotal' => round($precio * $cantidad, 2),
                'peso'     => $peso * $cantidad,
            ];
        }

        return [$lineas, $problemas];
    }

    // =====================================================
    //  DATOS PARA MOSTRAR EN EL CHECKOUT (sin escribir nada)
    // =====================================================
    public function prepararCheckout(int $id_usuario, array $carrito): array
    {
        $cliente  = $this->obtenerCliente($id_usuario);
        $es_nuevo = ($cliente === null);

        [$lineas, $problemas] = $this->leerLineas($carrito, false);

        if (empty($lineas)) {
            return ['error' => 'vacio'];
        }

        $subtotal = 0.0;
        $peso     = 0.0;
        foreach ($lineas as $l) {
            $subtotal += $l['subtotal'];
            $peso     += $l['peso'];
        }

        // Si todavía no es cliente no sabemos su localidad: mostramos el envío
        // estimado con la tarifa general. El costo final se calcula al confirmar.
        $id_localidad = ($cliente && $cliente['id_localidad']) ? (int) $cliente['id_localidad'] : null;
        $tarifa = $this->buscarTarifa($id_localidad);
        $envio  = 0.0;

        if ($tarifa) {
            $envio = $this->costoEnvio($tarifa, $peso);
        } else {
            $problemas[] = "No hay tarifas de envío configuradas. Avisale a un administrador.";
        }

        $dias = ($cliente && $cliente['dias_transitos_base'] !== null) ? (int) $cliente['dias_transitos_base'] : 5;

        return [
            'es_nuevo'  => $es_nuevo,
            'cliente'   => $cliente,
            'direccion' => $cliente ? $this->direccionEnTexto($cliente) : '',
            'lineas'    => $lineas,
            'subtotal'  => round($subtotal, 2),
            'peso'      => $peso,
            'tarifa'    => $tarifa,
            'envio'     => $envio,
            'total'     => round($subtotal + $envio, 2),
            'dias'      => $dias,
            'metodos'   => $this->listarMetodosPago(),
            'problemas' => $problemas,
        ];
    }

    // =====================================================
    //  TEXTO LIBRE: PROVINCIA / LOCALIDAD ESCRITAS POR EL CLIENTE
    // =====================================================

    // Minúsculas y sin tildes, para comparar "cordoba" con "Córdoba".
    public static function normalizar(string $t): string
    {
        $t = mb_strtolower(trim($t), 'UTF-8');
        $t = strtr($t, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n',
            'Á' => 'a', 'À' => 'a', 'Ä' => 'a', 'Â' => 'a',
            'É' => 'e', 'È' => 'e', 'Ë' => 'e', 'Ê' => 'e',
            'Í' => 'i', 'Ì' => 'i', 'Ï' => 'i', 'Î' => 'i',
            'Ó' => 'o', 'Ò' => 'o', 'Ö' => 'o', 'Ô' => 'o',
            'Ú' => 'u', 'Ù' => 'u', 'Ü' => 'u', 'Û' => 'u',
            'Ñ' => 'n',
        ]);
        return preg_replace('/\s+/', ' ', $t);
    }

    private function limpiarTexto($t): string
    {
        $t = strip_tags((string) $t);
        return trim(preg_replace('/\s+/u', ' ', $t));
    }

    // Si lo escribió todo en minúsculas o MAYÚSCULAS lo dejamos prolijo: "san luis" -> "San Luis".
    private function formatearNombre(string $t): string
    {
        if ($t !== mb_strtolower($t, 'UTF-8') && $t !== mb_strtoupper($t, 'UTF-8')) {
            return $t; // ya tiene mayúsculas y minúsculas mezcladas: se respeta
        }

        $chicas  = ['de', 'del', 'la', 'las', 'los', 'el', 'y'];
        $palabras = explode(' ', mb_strtolower($t, 'UTF-8'));

        foreach ($palabras as $i => $p) {
            if ($i > 0 && in_array($p, $chicas, true)) {
                continue;
            }
            $palabras[$i] = mb_convert_case($p, MB_CASE_TITLE, 'UTF-8');
        }

        return implode(' ', $palabras);
    }

    private function buscarOCrearProvincia(string $nombre): int
    {
        $nombre = $this->formatearNombre($this->limpiarTexto($nombre));
        $buscada = self::normalizar($nombre);

        $res = $this->conexion->query("SELECT id_provincia, nombre_provincia FROM provincias");
        while ($f = $res->fetch_assoc()) {
            if (self::normalizar((string) $f['nombre_provincia']) === $buscada) {
                return (int) $f['id_provincia'];
            }
        }

        $stmt = $this->conexion->prepare(
            "INSERT INTO provincias (nombre_provincia, dias_transitos_base, es_activo)
             VALUES (?, 5, 1)"
        );
        $stmt->bind_param("s", $nombre);
        $stmt->execute();

        return (int) $this->conexion->insert_id;
    }

    private function buscarOCrearLocalidad(string $nombre, int $id_provincia, string $codigo_postal): int
    {
        $nombre  = $this->formatearNombre($this->limpiarTexto($nombre));
        $buscada = self::normalizar($nombre);

        $stmt = $this->conexion->prepare(
            "SELECT id_localidad, nombre_localidad FROM localidades WHERE rela_id_provincia = ?"
        );
        $stmt->bind_param("i", $id_provincia);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($f = $res->fetch_assoc()) {
            if (self::normalizar((string) $f['nombre_localidad']) === $buscada) {
                return (int) $f['id_localidad'];
            }
        }

        $stmt = $this->conexion->prepare(
            "INSERT INTO localidades (nombre_localidad, codigo_postal, tipo_zona, rela_id_provincia, es_activo)
             VALUES (?, ?, NULL, ?, 1)"
        );
        $stmt->bind_param("ssi", $nombre, $codigo_postal, $id_provincia);
        $stmt->execute();

        return (int) $this->conexion->insert_id;
    }

    // Provincias y localidades activas, para sugerirlas mientras el cliente escribe.
    // Son solo sugerencias: puede escribir cualquier otra y se agrega sola.
    public function listarUbicaciones(): array
    {
        $provincias  = [];
        $localidades = [];

        $res = $this->conexion->query(
            "SELECT p.nombre_provincia, l.nombre_localidad
             FROM provincias p
             LEFT JOIN localidades l ON l.rela_id_provincia = p.id_provincia AND l.es_activo = 1
             WHERE p.es_activo = 1
             ORDER BY p.nombre_provincia, l.nombre_localidad"
        );

        while ($f = $res->fetch_assoc()) {
            $prov = $f['nombre_provincia'];
            if (!isset($localidades[$prov])) {
                $provincias[]       = $prov;
                $localidades[$prov] = [];
            }
            if (!empty($f['nombre_localidad'])) {
                $localidades[$prov][] = $f['nombre_localidad'];
            }
        }

        return ['provincias' => $provincias, 'localidades' => $localidades];
    }

    // =====================================================
    //  VALIDAR LOS DATOS PERSONALES Y DE DIRECCIÓN (cliente nuevo)
    // =====================================================
    public function validarDatosCliente(array $d): array
    {
        $nombre   = mb_substr($this->limpiarTexto($d['nombre'] ?? ''), 0, 100);
        $apellido = mb_substr($this->limpiarTexto($d['apellido'] ?? ''), 0, 200);
        $telefono = mb_substr($this->limpiarTexto($d['telefono'] ?? ''), 0, 100);
        $cuil     = preg_replace('/\D/', '', (string) ($d['cuil'] ?? ''));

        $calle     = mb_substr($this->limpiarTexto($d['calle'] ?? ''), 0, 150);
        $numero    = mb_substr($this->limpiarTexto($d['numero'] ?? ''), 0, 10);
        $barrio    = mb_substr($this->limpiarTexto($d['barrio'] ?? ''), 0, 100);
        $provincia = mb_substr($this->limpiarTexto($d['provincia'] ?? ''), 0, 150);
        $localidad = mb_substr($this->limpiarTexto($d['localidad'] ?? ''), 0, 150);
        $cp        = mb_substr(preg_replace('/[^A-Za-z0-9 ]/', '', (string) ($d['codigo_postal'] ?? '')), 0, 10);

        if ($nombre === '' || $apellido === '') {
            throw new PedidoException("Completá tu nombre y apellido.");
        }
        if (strlen(preg_replace('/\D/', '', $telefono)) < 6) {
            throw new PedidoException("Ingresá un teléfono de contacto válido.");
        }
        if (strlen($cuil) !== 11) {
            throw new PedidoException("El CUIL/CUIT tiene que tener 11 números (podés escribirlo con o sin guiones).");
        }
        if ($calle === '' || $numero === '') {
            throw new PedidoException("Completá la calle y el número de tu dirección (si no tiene número, escribí S/N).");
        }
        if ($provincia === '') {
            throw new PedidoException("Escribí tu provincia.");
        }
        if ($localidad === '') {
            throw new PedidoException("Escribí tu localidad.");
        }

        return [
            'nombre'        => $nombre,
            'apellido'      => $apellido,
            'telefono'      => $telefono,
            'cuil'          => $cuil,
            'calle'         => $calle,
            'numero'        => $numero,
            'barrio'        => $barrio,
            'provincia'     => $provincia,
            'localidad'     => $localidad,
            'codigo_postal' => $cp,
        ];
    }

    // Da de alta provincia/localidad (si hacen falta), dirección, cliente y teléfono.
    // Se ejecuta DENTRO de la transacción del pedido: si el pedido falla, no queda nada guardado.
    private function registrarCliente(int $id_usuario, array $c, array $entrega): void
    {
        $id_prov = $this->buscarOCrearProvincia($c['provincia']);
        $id_loc  = $this->buscarOCrearLocalidad($c['localidad'], $id_prov, $c['codigo_postal']);

        $piso       = $entrega['piso_depto_torre'] ?? '';
        $referencia = $entrega['referencia'] ?? '';
        $piso       = $piso === null ? '' : mb_substr((string) $piso, 0, 20);
        $referencia = $referencia === null ? '' : (string) $referencia;

        $stmt = $this->conexion->prepare(
            "INSERT INTO direcciones
                (calle, numero_exterior, barrio_colonia, piso_departamento,
                 referencia_adicionales, rela_id_localidad)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssssi", $c['calle'], $c['numero'], $c['barrio'], $piso, $referencia, $id_loc);
        $stmt->execute();
        $id_direccion = (int) $this->conexion->insert_id;

        $stmt = $this->conexion->prepare(
            "INSERT INTO clientes (nombre, apellido, cuil_cuit, fecha_registro, rela_id_direccion, rela_id_usuario)
             VALUES (?, ?, ?, NOW(), ?, ?)"
        );
        $stmt->bind_param("sssii", $c['nombre'], $c['apellido'], $c['cuil'], $id_direccion, $id_usuario);
        $stmt->execute();
        $id_cliente = (int) $this->conexion->insert_id;

        // Tipo de contacto 1 = Celular
        $stmt = $this->conexion->prepare(
            "INSERT INTO contactos (valor, rela_id_tipo_contacto, rela_id_cliente) VALUES (?, 1, ?)"
        );
        $stmt->bind_param("si", $c['telefono'], $id_cliente);
        $stmt->execute();
    }

    // =====================================================
    //  VALIDAR LOS DATOS DE ENTREGA QUE MANDA EL FORMULARIO
    // =====================================================
    // $datos viene directo de $_POST. Devuelve un array limpio, listo
    // para guardar, o lanza PedidoException si falta algo obligatorio.
    public function validarDatosEntrega(array $datos): array
    {
        $recibe_quien = ($datos['recibe_quien'] ?? 'yo') === 'otra_persona' ? 'otra_persona' : 'yo';

        $recibe_nombre   = null;
        $recibe_telefono = null;

        if ($recibe_quien === 'otra_persona') {
            $recibe_nombre   = trim($datos['recibe_nombre'] ?? '');
            $recibe_telefono = trim($datos['recibe_telefono'] ?? '');

            if ($recibe_nombre === '' || $recibe_telefono === '') {
                throw new PedidoException("Completá el nombre y teléfono de quién va a recibir el pedido.");
            }
        }

        $tipos_validos = ['casa', 'departamento', 'trabajo', 'local', 'otro'];
        $tipo_lugar    = $datos['tipo_lugar'] ?? 'casa';
        if (!in_array($tipo_lugar, $tipos_validos, true)) {
            $tipo_lugar = 'casa';
        }

        $piso_depto_torre = trim($datos['piso_depto_torre'] ?? '');
        if ($tipo_lugar === 'departamento' && $piso_depto_torre === '') {
            throw new PedidoException("Indicá piso/depto para que el repartidor lo encuentre.");
        }

        $referencia = trim($datos['referencia'] ?? '');

        return [
            'recibe_quien'      => $recibe_quien,
            'recibe_nombre'     => $recibe_nombre,
            'recibe_telefono'   => $recibe_telefono,
            'tipo_lugar'        => $tipo_lugar,
            'piso_depto_torre'  => $piso_depto_torre !== '' ? $piso_depto_torre : null,
            'referencia'        => $referencia !== '' ? $referencia : null,
        ];
    }

    // =====================================================
    //  CREAR EL PEDIDO (todo o nada)
    // =====================================================
    public function crearPedido(int $id_usuario, array $carrito, int $id_metodo_pago, array $datosEntrega, ?array $datosCliente = null): array
    {
        // La tabla del historial se crea ANTES de abrir la transacción (CREATE TABLE la confirmaría).
        $this->asegurarHistorial();

        try {

            $this->conexion->begin_transaction();

            // ---- Cliente (si es su primera compra, se crea acá mismo) ----
            $cliente = $this->obtenerCliente($id_usuario);
            if (!$cliente) {
                if ($datosCliente === null) {
                    throw new PedidoException("Completá tus datos personales y de entrega para poder comprar.");
                }

                $this->registrarCliente($id_usuario, $datosCliente, $datosEntrega);
                $cliente = $this->obtenerCliente($id_usuario);

                if (!$cliente) {
                    throw new PedidoException("No pudimos guardar tus datos. Probá de nuevo.");
                }
            }

            // ---- Método de pago ----
            $stmt = $this->conexion->prepare(
                "SELECT id_metodo_pago FROM metodos_pago
                 WHERE id_metodo_pago = ? AND es_activo = 1"
            );
            $stmt->bind_param("i", $id_metodo_pago);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new PedidoException("Elegí un método de pago válido.");
            }

            // ---- Concepto de stock "Venta a cliente" ----
            $res = $this->conexion->query(
                "SELECT id_concepto FROM conceptos_movimiento
                 WHERE tipo_movimiento = 'S'
                 AND descripcion = 'Venta a cliente'
                 AND es_activo = 1
                 LIMIT 1"
            );
            $concepto = $res->fetch_assoc();
            if (!$concepto) {
                throw new PedidoException("Falta el concepto de movimiento «Venta a cliente». Avisale a un administrador.");
            }
            $id_concepto = (int) $concepto['id_concepto'];

            // ---- Promoción neutra ("sin promoción") ----
            // detalle_pedidos exige una promoción. Se busca por el tipo 'Ninguna'
            // (sin tildes, así no depende de la codificación del cliente SQL) y,
            // si todavía no existe, se crea sola: ya no hace falta correr ningún script.
            $res = $this->conexion->query(
                "SELECT id_promociones FROM promociones
                 WHERE tipo = 'Ninguna'
                 ORDER BY id_promociones
                 LIMIT 1"
            );
            $promo = $res->fetch_assoc();

            if ($promo) {
                $id_promo = (int) $promo['id_promociones'];
            } else {
                $this->conexion->query(
                    "INSERT INTO promociones (nombre, tipo, fecha_inicio, fecha_fin, monto_minmo_requererido)
                     VALUES ('Sin promocion', 'Ninguna', NULL, NULL, 0)"
                );
                $id_promo = (int) $this->conexion->insert_id;
            }

            // ---- Productos (bloqueados hasta terminar) ----
            [$lineas, $problemas] = $this->leerLineas($carrito, true);

            if (empty($lineas)) {
                throw new PedidoException("Tu carrito está vacío.");
            }
            if (!empty($problemas)) {
                throw new PedidoException(implode(' ', $problemas));
            }

            $subtotal = 0.0;
            $peso     = 0.0;
            foreach ($lineas as $l) {
                $subtotal += $l['subtotal'];
                $peso     += $l['peso'];
            }

            // ---- Envío ----
            $tarifa = $this->buscarTarifa($cliente['id_localidad'] ? (int) $cliente['id_localidad'] : null);
            if (!$tarifa) {
                throw new PedidoException("No hay tarifas de envío configuradas.");
            }

            $envio = $this->costoEnvio($tarifa, $peso);
            $total = round($subtotal + $envio, 2);

            // ---- Pedido (rela_id_envio se completa después) ----
            $estadoInicial = 'Pedido recibido';

            $stmt = $this->conexion->prepare(
                "INSERT INTO pedidos (fecha_pedidos, estado, moneda, monto_total, rela_id_envio, rela_id_cliente)
                 VALUES (CURDATE(), ?, 'ARS', ?, 0, ?)"
            );
            $id_cliente = (int) $cliente['id_cliente'];
            $stmt->bind_param("sdi", $estadoInicial, $total, $id_cliente);
            $stmt->execute();
            $id_pedido = (int) $this->conexion->insert_id;

            // ---- Primer registro del historial ----
            $this->registrarHistorial(
                $id_pedido,
                $estadoInicial,
                trim($cliente['nombre'] . ' ' . $cliente['apellido']),
                'Pedido registrado por el cliente.'
            );

            // ---- Detalle + stock + historial ----
            $stmtDetalle = $this->conexion->prepare(
                "INSERT INTO detalle_pedidos
                    (cantidad, precio_unitario_base, precio__base_final, subtotal_base,
                     subtotal_final, impuesto_aplicado, nota_detalles,
                     rela_id_productos, rela_id_pedidos, rela_id_promociones)
                 VALUES (?, ?, ?, ?, ?, 0, NULL, ?, ?, ?)"
            );

            $stmtStock = $this->conexion->prepare(
                "UPDATE productos SET stock = stock - ? WHERE id_producto = ?"
            );

            $stmtHist = $this->conexion->prepare(
                "INSERT INTO historial_movimientos
                    (fecha_movimiento, cantidad, referencia_ext, comentario,
                     rela_id_productos, rela_id_conceptos)
                 VALUES (CURDATE(), ?, ?, ?, ?, ?)"
            );

            $referenciaStock = "PEDIDO-" . $id_pedido;
            $comentario      = "Venta web";

            foreach ($lineas as $l) {
                $cant   = $l['cantidad'];
                $precio = $l['precio'];
                $sub    = $l['subtotal'];
                $idProd = $l['id'];

                $stmtDetalle->bind_param(
                    "iddddiii",
                    $cant, $precio, $precio, $sub, $sub,
                    $idProd, $id_pedido, $id_promo
                );
                $stmtDetalle->execute();

                $stmtStock->bind_param("ii", $cant, $idProd);
                $stmtStock->execute();

                $movimiento = -$cant;
                $stmtHist->bind_param(
                    "issii",
                    $movimiento, $referenciaStock, $comentario, $idProd, $id_concepto
                );
                $stmtHist->execute();
            }

            // ---- Envío (con los datos de entrega ya validados) ----
            $dias = $cliente['dias_transitos_base'] !== null ? (int) $cliente['dias_transitos_base'] : 5;
            $fechaEstimada = date('Y-m-d H:i:s', strtotime("+{$dias} days"));
            $direccion     = $this->direccionEnTexto($cliente);
            $empresa       = $tarifa['proveedor_servicio'];
            $id_tarifa     = (int) $tarifa['id_tarifa_envios'];

            $stmt = $this->conexion->prepare(
                "INSERT INTO envios
                    (direccion_entrega, recibe_quien, recibe_nombre, recibe_telefono,
                     tipo_lugar, piso_depto_torre, referencia,
                     empresa_transporte, estado_envio,
                     fecha_estimada_entrega, rela_id_pedidos, rela_id_tarifa_envios)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "ssssssssssii",
                $direccion,
                $datosEntrega['recibe_quien'],
                $datosEntrega['recibe_nombre'],
                $datosEntrega['recibe_telefono'],
                $datosEntrega['tipo_lugar'],
                $datosEntrega['piso_depto_torre'],
                $datosEntrega['referencia'],
                $empresa,
                $estadoInicial,
                $fechaEstimada,
                $id_pedido,
                $id_tarifa
            );
            $stmt->execute();
            $id_envio = (int) $this->conexion->insert_id;

            $stmt = $this->conexion->prepare(
                "UPDATE pedidos SET rela_id_envio = ? WHERE id_pedidos = ?"
            );
            $stmt->bind_param("ii", $id_envio, $id_pedido);
            $stmt->execute();

            // ---- Pago (queda pendiente: no hay pasarela real) ----
            $refPago = "WEB-" . $id_pedido . "-" . time();

            $stmt = $this->conexion->prepare(
                "INSERT INTO pagos
                    (estado_pagos, fechas_pagos, monto, referencia_transaccion,
                     rela_id_pedidos, rela_id_metodo_pago)
                 VALUES ('Pendiente', NOW(), ?, ?, ?, ?)"
            );
            $stmt->bind_param("dsii", $total, $refPago, $id_pedido, $id_metodo_pago);
            $stmt->execute();

            $this->conexion->commit();

            // Avisos (campanita / correo). Nunca rompen la compra: si fallan, solo quedan en el log.
            avisar_pedido_nuevo($this->conexion, $id_pedido, array_column($lineas, 'id'));

            return ['ok' => true, 'id_pedido' => $id_pedido];

        } catch (PedidoException $e) {

            $this->conexion->rollback();
            return ['ok' => false, 'error' => $e->getMessage()];

        } catch (Throwable $e) {

            $this->conexion->rollback();
            error_log("Error al crear pedido: " . $e->getMessage());
            return ['ok' => false, 'error' => "No pudimos procesar tu pedido. Probá de nuevo en unos minutos."];
        }
    }

    // =====================================================
    //  UN PEDIDO (solo si es del usuario logueado) - vista del cliente
    // =====================================================
    public function obtenerPedido(int $id_pedido, int $id_usuario): ?array
    {
        $sql = "SELECT p.id_pedidos, p.fecha_pedidos, p.estado, p.moneda, p.monto_total,
                       e.direccion_entrega, e.recibe_quien, e.recibe_nombre, e.recibe_telefono,
                       e.tipo_lugar, e.piso_depto_torre, e.referencia,
                       e.empresa_transporte, e.estado_envio, e.fecha_estimada_entrega,
                       e.num_seguimiento, e.fecha_envio,
                       (SELECT f.id_facturas FROM facturas f WHERE f.rela_id_pedidos = p.id_pedidos LIMIT 1) AS id_factura,
                       (SELECT f.numero_factura FROM facturas f WHERE f.rela_id_pedidos = p.id_pedidos LIMIT 1) AS numero_factura,
                       pg.estado_pagos, pg.referencia_transaccion,
                       mp.nombre AS metodo_pago
                FROM pedidos p
                INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
                LEFT JOIN envios e ON e.rela_id_pedidos = p.id_pedidos
                LEFT JOIN pagos pg ON pg.rela_id_pedidos = p.id_pedidos
                LEFT JOIN metodos_pago mp ON mp.id_metodo_pago = pg.rela_id_metodo_pago
                WHERE p.id_pedidos = ? AND c.rela_id_usuario = ?
                LIMIT 1";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("ii", $id_pedido, $id_usuario);
        $stmt->execute();

        $pedido = $stmt->get_result()->fetch_assoc();

        if (!$pedido) {
            return null;
        }

        $pedido['items']     = $this->itemsDelPedido($id_pedido);
        $pedido['historial'] = $this->obtenerHistorial($id_pedido);

        return $pedido;
    }

    // Lista de pedidos del usuario logueado (vista "Mis pedidos").
    // $grupo: 'todos' | 'curso' (sin entregar ni cancelar) | 'entregados' (entregados y cancelados)
    public function listarPedidosCliente(int $id_usuario, string $grupo = 'todos'): array
    {
        $sql = "SELECT p.id_pedidos, p.fecha_pedidos, p.estado, p.monto_total,
                       e.empresa_transporte, e.num_seguimiento,
                       (SELECT pg.estado_pagos FROM pagos pg WHERE pg.rela_id_pedidos = p.id_pedidos ORDER BY pg.id_pagos DESC LIMIT 1) AS estado_pagos,
                       (SELECT f.id_facturas FROM facturas f WHERE f.rela_id_pedidos = p.id_pedidos LIMIT 1) AS id_factura,
                       (SELECT COUNT(*) FROM detalle_pedidos d WHERE d.rela_id_pedidos = p.id_pedidos) AS cant_items
                FROM pedidos p
                INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
                LEFT JOIN envios e ON e.rela_id_pedidos = p.id_pedidos
                WHERE c.rela_id_usuario = ?";

        if ($grupo === 'curso') {
            $sql .= " AND p.estado NOT IN ('Entregado','Cancelado')";
        } elseif ($grupo === 'entregados') {
            $sql .= " AND p.estado IN ('Entregado','Cancelado')";
        }

        $sql .= " ORDER BY p.id_pedidos DESC";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();

        $filas = [];
        $res = $stmt->get_result();
        while ($f = $res->fetch_assoc()) {
            $filas[] = $f;
        }
        return $filas;
    }

    // Cantidad de pedidos del usuario por grupo (para los contadores de los filtros).
    public function contarPedidosCliente(int $id_usuario): array
    {
        $stmt = $this->conexion->prepare(
            "SELECT p.estado, COUNT(*) AS total
             FROM pedidos p INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
             WHERE c.rela_id_usuario = ? GROUP BY p.estado"
        );
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();

        $c = ['todos' => 0, 'curso' => 0, 'entregados' => 0];
        $res = $stmt->get_result();
        while ($f = $res->fetch_assoc()) {
            $n = (int) $f['total'];
            $c['todos'] += $n;
            if (in_array($f['estado'], ['Entregado', 'Cancelado'], true)) {
                $c['entregados'] += $n;
            } else {
                $c['curso'] += $n;
            }
        }
        return $c;
    }

    private function itemsDelPedido(int $id_pedido): array
    {
        $stmt = $this->conexion->prepare(
            "SELECT dp.cantidad, dp.precio_unitario_base, dp.subtotal_final, pr.nombre
             FROM detalle_pedidos dp
             INNER JOIN productos pr ON pr.id_producto = dp.rela_id_productos
             WHERE dp.rela_id_pedidos = ?"
        );
        $stmt->bind_param("i", $id_pedido);
        $stmt->execute();

        $items = [];
        $res = $stmt->get_result();
        while ($fila = $res->fetch_assoc()) {
            $items[] = $fila;
        }
        return $items;
    }

    // =====================================================
    //  HISTORIAL DE CAMBIOS DEL PEDIDO
    // =====================================================
    private static bool $historialListo = false;

    // Crea la tabla del historial si todavía no existe (así no hace falta correr ningún script).
    // IMPORTANTE: llamarla siempre ANTES de abrir una transacción.
    private function asegurarHistorial(): void
    {
        if (self::$historialListo) {
            return;
        }

        $this->conexion->query(
            "CREATE TABLE IF NOT EXISTS pedido_historial (
                id_historial     INT NOT NULL AUTO_INCREMENT,
                rela_id_pedidos  INT NOT NULL,
                estado           VARCHAR(50)  NOT NULL,
                autor            VARCHAR(100) NOT NULL,
                nota             TEXT NULL,
                fecha_evento     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id_historial),
                KEY idx_historial_pedido (rela_id_pedidos),
                CONSTRAINT fk_historial_pedido FOREIGN KEY (rela_id_pedidos)
                    REFERENCES pedidos (id_pedidos) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        self::$historialListo = true;
    }

    private function registrarHistorial(int $id_pedido, string $estado, string $autor, ?string $nota, ?string $fecha = null): void
    {
        $autor = mb_substr(trim($autor) !== '' ? trim($autor) : 'Sistema', 0, 100);

        if ($fecha === null) {
            $stmt = $this->conexion->prepare(
                "INSERT INTO pedido_historial (rela_id_pedidos, estado, autor, nota) VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param("isss", $id_pedido, $estado, $autor, $nota);
        } else {
            $stmt = $this->conexion->prepare(
                "INSERT INTO pedido_historial (rela_id_pedidos, estado, autor, nota, fecha_evento) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("issss", $id_pedido, $estado, $autor, $nota, $fecha);
        }

        $stmt->execute();
    }

    // Devuelve los eventos del pedido, del más viejo al más nuevo.
    // Los pedidos anteriores al historial muestran un único evento "Pedido registrado".
    public function obtenerHistorial(int $id_pedido): array
    {
        $this->asegurarHistorial();

        $stmt = $this->conexion->prepare(
            "SELECT estado, autor, nota, fecha_evento
             FROM pedido_historial
             WHERE rela_id_pedidos = ?
             ORDER BY fecha_evento ASC, id_historial ASC"
        );
        $stmt->bind_param("i", $id_pedido);
        $stmt->execute();

        $eventos = [];
        $res = $stmt->get_result();
        while ($f = $res->fetch_assoc()) {
            $eventos[] = $f;
        }

        if (empty($eventos)) {
            $stmt = $this->conexion->prepare("SELECT estado, fecha_pedidos FROM pedidos WHERE id_pedidos = ?");
            $stmt->bind_param("i", $id_pedido);
            $stmt->execute();
            $p = $stmt->get_result()->fetch_assoc();

            if ($p) {
                $eventos[] = [
                    'estado'       => $p['estado'],
                    'autor'        => 'Sistema',
                    'nota'         => 'Pedido registrado.',
                    'fecha_evento' => $p['fecha_pedidos'] . ' 00:00:00',
                ];
            }
        }

        return $eventos;
    }

    // =====================================================
    //  REGLAS DE CAMBIO DE ESTADO
    // =====================================================
    // Pedidos viejos (cargados antes de existir esta lista) con otros nombres de estado.
    private const EQUIVALENCIAS = ['Pagado' => 'Confirmado', 'En proceso' => 'Confirmado'];

    // Camino normal de un pedido (sin "Cancelado", que es una salida lateral).
    public function secuenciaEstados(): array
    {
        return array_values(array_diff(array_keys(self::ESTADOS), ['Cancelado']));
    }

    public function posicionEstado(string $estado): int
    {
        $estado = self::EQUIVALENCIAS[$estado] ?? $estado;
        $pos = array_search($estado, $this->secuenciaEstados(), true);

        return $pos === false ? 0 : $pos;
    }

    // A qué estados se puede pasar desde el actual:
    // solo hacia adelante, y se puede cancelar hasta antes de que salga "En camino".
    public function estadosPermitidos(string $actual): array
    {
        if ($actual === 'Entregado' || $actual === 'Cancelado') {
            return [];
        }

        $sec = $this->secuenciaEstados();
        $pos = $this->posicionEstado($actual);

        $permitidos = [];
        foreach ($sec as $i => $estado) {
            if ($i > $pos) {
                $permitidos[] = $estado;
            }
        }

        if ($pos < array_search('En camino', $sec, true)) {
            $permitidos[] = 'Cancelado';
        }

        return $permitidos;
    }

    // El paso siguiente del camino normal (para el botón rápido), o null si ya no hay.
    public function siguienteEstado(string $actual): ?string
    {
        if ($actual === 'Entregado' || $actual === 'Cancelado') {
            return null;
        }

        $sec  = $this->secuenciaEstados();
        $next = $this->posicionEstado($actual) + 1;

        return $sec[$next] ?? null;
    }

    // Mensaje que se guarda (y se le muestra al cliente) cuando el admin no escribe una nota.
    public static function notaPorDefecto(string $estado): string
    {
        $notas = [
            'Pedido recibido'      => 'Recibimos tu pedido.',
            'Confirmado'           => 'Confirmamos tu pedido. Ya estamos trabajando en él.',
            'Preparando pedido'    => 'Estamos preparando tu pedido.',
            'Listo para despachar' => 'Tu pedido está empaquetado y listo para ser despachado.',
            'En camino'            => 'Tu pedido salió de nuestro depósito y está en camino.',
            'Entregado'            => 'Tu pedido fue entregado. ¡Gracias por tu compra!',
            'Cancelado'            => 'El pedido fue cancelado.',
        ];

        return $notas[$estado] ?? '';
    }

    // =====================================================
    //  PANEL ADMIN: listar pedidos (con filtro de estado y buscador)
    // =====================================================
    public function listarPedidosAdmin(string $estado = 'todos', string $buscar = ''): array
    {
        $sql = "SELECT p.id_pedidos, p.fecha_pedidos, p.estado, p.monto_total,
                       c.nombre, c.apellido, u.correo,
                       e.estado_envio, e.empresa_transporte, e.num_seguimiento
                FROM pedidos p
                INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
                LEFT JOIN usuarios u ON u.id_usuario = c.rela_id_usuario
                LEFT JOIN envios e ON e.rela_id_pedidos = p.id_pedidos";

        $donde  = [];
        $tipos  = '';
        $params = [];

        if ($estado !== 'todos' && array_key_exists($estado, self::ESTADOS)) {
            $donde[]  = "p.estado = ?";
            $tipos   .= 's';
            $params[] = $estado;
        }

        $buscar = trim($buscar);
        if ($buscar !== '') {
            $digitos = preg_replace('/\D/', '', $buscar);
            $id      = ($digitos !== '' && strlen($digitos) < 10) ? (int) $digitos : 0;
            $like    = '%' . $buscar . '%';

            $donde[] = "(p.id_pedidos = ?
                         OR c.nombre LIKE ?
                         OR c.apellido LIKE ?
                         OR CONCAT(c.nombre, ' ', c.apellido) LIKE ?
                         OR u.correo LIKE ?
                         OR e.num_seguimiento LIKE ?)";
            $tipos   .= 'isssss';
            array_push($params, $id, $like, $like, $like, $like, $like);
        }

        if ($donde) {
            $sql .= " WHERE " . implode(' AND ', $donde);
        }

        $sql .= " ORDER BY p.id_pedidos DESC";

        $stmt = $this->conexion->prepare($sql);

        if ($params) {
            $stmt->bind_param($tipos, ...$params);
        }

        $stmt->execute();
        $res = $stmt->get_result();

        $pedidos = [];
        while ($fila = $res->fetch_assoc()) {
            $pedidos[] = $fila;
        }
        return $pedidos;
    }

    // Cantidad de pedidos por estado (para los contadores de las pestañas).
    public function contarPorEstado(): array
    {
        $cuentas = ['todos' => 0];

        $res = $this->conexion->query("SELECT estado, COUNT(*) AS total FROM pedidos GROUP BY estado");
        while ($f = $res->fetch_assoc()) {
            $cuentas[$f['estado']] = (int) $f['total'];
            $cuentas['todos']     += (int) $f['total'];
        }

        return $cuentas;
    }

    // =====================================================
    //  PANEL ADMIN: un pedido puntual, sin restricción de dueño
    // =====================================================
    public function obtenerPedidoAdmin(int $id_pedido): ?array
    {
        $sql = "SELECT p.id_pedidos, p.fecha_pedidos, p.estado, p.moneda, p.monto_total,
                       c.id_cliente, c.nombre AS cliente_nombre, c.apellido AS cliente_apellido,
                       u.correo AS cliente_correo,
                       (SELECT ct.valor FROM contactos ct
                        WHERE ct.rela_id_cliente = c.id_cliente
                        ORDER BY ct.id_contacto LIMIT 1) AS cliente_telefono,
                       e.direccion_entrega, e.recibe_quien, e.recibe_nombre, e.recibe_telefono,
                       e.tipo_lugar, e.piso_depto_torre, e.referencia,
                       e.empresa_transporte, e.num_seguimiento, e.fecha_envio,
                       e.estado_envio, e.fecha_estimada_entrega,
                       (SELECT f.id_facturas FROM facturas f WHERE f.rela_id_pedidos = p.id_pedidos LIMIT 1) AS id_factura,
                       (SELECT f.numero_factura FROM facturas f WHERE f.rela_id_pedidos = p.id_pedidos LIMIT 1) AS numero_factura,
                       pg.estado_pagos, pg.referencia_transaccion,
                       mp.nombre AS metodo_pago
                FROM pedidos p
                INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
                LEFT JOIN usuarios u ON u.id_usuario = c.rela_id_usuario
                LEFT JOIN envios e ON e.rela_id_pedidos = p.id_pedidos
                LEFT JOIN pagos pg ON pg.rela_id_pedidos = p.id_pedidos
                LEFT JOIN metodos_pago mp ON mp.id_metodo_pago = pg.rela_id_metodo_pago
                WHERE p.id_pedidos = ?
                LIMIT 1";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id_pedido);
        $stmt->execute();

        $pedido = $stmt->get_result()->fetch_assoc();

        if (!$pedido) {
            return null;
        }

        $pedido['items']     = $this->itemsDelPedido($id_pedido);
        $pedido['historial'] = $this->obtenerHistorial($id_pedido);

        return $pedido;
    }

    // Transportistas ya usados en las tarifas de envío (para sugerirlos en el formulario).
    public function listarTransportistas(): array
    {
        $res = $this->conexion->query(
            "SELECT DISTINCT proveedor_servicio FROM tarifa_envios
             WHERE proveedor_servicio IS NOT NULL AND proveedor_servicio <> ''
             ORDER BY proveedor_servicio"
        );

        $lista = [];
        while ($f = $res->fetch_assoc()) {
            $lista[] = $f['proveedor_servicio'];
        }
        return $lista;
    }

    // =====================================================
    //  PANEL ADMIN: cambiar el estado de un pedido
    // =====================================================
    // Devuelve ['ok' => true, ...] o ['ok' => false, 'error' => '...'].
    // Todo ocurre en una transacción: estado, envío, pago, stock (si se cancela) e historial.
    public function cambiarEstado(
        int $id_pedido,
        string $nuevo_estado,
        string $autor,
        ?string $nota = null,
        ?string $transportista = null,
        ?string $tracking = null
    ): array {

        $this->asegurarHistorial(); // antes de la transacción

        $nota          = mb_substr(trim((string) $nota), 0, 1000);
        $transportista = mb_substr(trim((string) $transportista), 0, 100);
        $tracking      = mb_substr(trim((string) $tracking), 0, 100);

        try {

            $this->conexion->begin_transaction();

            $stmt = $this->conexion->prepare("SELECT estado FROM pedidos WHERE id_pedidos = ? FOR UPDATE");
            $stmt->bind_param("i", $id_pedido);
            $stmt->execute();
            $fila = $stmt->get_result()->fetch_assoc();

            if (!$fila) {
                throw new PedidoException("El pedido no existe.");
            }

            $actual = $fila['estado'];

            if (!array_key_exists($nuevo_estado, self::ESTADOS)) {
                throw new PedidoException("El estado elegido no es válido.");
            }

            if ($actual === 'Entregado' || $actual === 'Cancelado') {
                throw new PedidoException("Este pedido ya está finalizado (" . $actual . ") y no se puede modificar.");
            }

            $mismo = ($nuevo_estado === $actual);

            if ($mismo) {
                // Mismo estado: solo se permite para sumar una nota o cargar el seguimiento.
                if ($nota === '' && $transportista === '' && $tracking === '') {
                    throw new PedidoException("No hay cambios para guardar.");
                }
            } elseif (!in_array($nuevo_estado, $this->estadosPermitidos($actual), true)) {
                throw new PedidoException("No se puede pasar de «" . $actual . "» a «" . $nuevo_estado . "».");
            }

            // ---- Pedido ----
            if (!$mismo) {
                $stmt = $this->conexion->prepare("UPDATE pedidos SET estado = ? WHERE id_pedidos = ?");
                $stmt->bind_param("si", $nuevo_estado, $id_pedido);
                $stmt->execute();
            }

            // ---- Envío (estado + transportista + seguimiento, solo lo que se haya escrito) ----
            $stmt = $this->conexion->prepare(
                "UPDATE envios
                 SET estado_envio       = ?,
                     empresa_transporte = COALESCE(NULLIF(?, ''), empresa_transporte),
                     num_seguimiento    = COALESCE(NULLIF(?, ''), num_seguimiento)
                 WHERE rela_id_pedidos = ?"
            );
            $stmt->bind_param("sssi", $nuevo_estado, $transportista, $tracking, $id_pedido);
            $stmt->execute();

            if ($nuevo_estado === 'En camino') {
                $stmt = $this->conexion->prepare(
                    "UPDATE envios SET fecha_envio = COALESCE(fecha_envio, CURDATE()) WHERE rela_id_pedidos = ?"
                );
                $stmt->bind_param("i", $id_pedido);
                $stmt->execute();
            }

            // ---- Pago y stock ----
            $stock_devuelto = 0;

            if (!$mismo) {
                if ($nuevo_estado === 'Cancelado') {

                    $stmt = $this->conexion->prepare(
                        "UPDATE pagos
                         SET estado_pagos = CASE estado_pagos
                                 WHEN 'Pendiente' THEN 'Cancelado'
                                 WHEN 'Aprobado'  THEN 'A reembolsar'
                                 ELSE estado_pagos END
                         WHERE rela_id_pedidos = ?"
                    );
                    $stmt->bind_param("i", $id_pedido);
                    $stmt->execute();

                    $stock_devuelto = $this->devolverStock($id_pedido);

                } else {
                    // Al confirmar el pedido se da por aprobado el pago que estaba pendiente.
                    $stmt = $this->conexion->prepare(
                        "UPDATE pagos SET estado_pagos = 'Aprobado'
                         WHERE rela_id_pedidos = ? AND estado_pagos = 'Pendiente'"
                    );
                    $stmt->bind_param("i", $id_pedido);
                    $stmt->execute();
                }
            }

            // ---- Historial ----
            $nota_final = $nota !== '' ? $nota : ($mismo ? '' : self::notaPorDefecto($nuevo_estado));

            $extra = [];
            if ($transportista !== '') {
                $extra[] = "Transporte: " . $transportista;
            }
            if ($tracking !== '') {
                $extra[] = "N° de seguimiento: " . $tracking;
            }
            if ($stock_devuelto > 0) {
                $extra[] = "Stock devuelto: " . $stock_devuelto . " unidad(es).";
            }
            if ($extra) {
                $nota_final = trim($nota_final . "\n" . implode(' · ', $extra));
            }

            // Pedidos anteriores al historial: primero se deja el registro inicial, para que la línea de tiempo salga completa.
            $stmt = $this->conexion->prepare("SELECT 1 FROM pedido_historial WHERE rela_id_pedidos = ? LIMIT 1");
            $stmt->bind_param("i", $id_pedido);
            $stmt->execute();

            if ($stmt->get_result()->num_rows === 0) {
                $stmt = $this->conexion->prepare("SELECT fecha_pedidos FROM pedidos WHERE id_pedidos = ?");
                $stmt->bind_param("i", $id_pedido);
                $stmt->execute();
                $fp = $stmt->get_result()->fetch_assoc();

                $this->registrarHistorial(
                    $id_pedido,
                    $actual,
                    'Sistema',
                    'Pedido registrado.',
                    ($fp['fecha_pedidos'] ?? date('Y-m-d')) . ' 00:00:00'
                );
            }

            $this->registrarHistorial($id_pedido, $nuevo_estado, $autor, $nota_final !== '' ? $nota_final : null);

            $this->conexion->commit();

            // Aviso al cliente (campanita / correo / WhatsApp según el estado)
            if (!$mismo) {
                avisar_estado_pedido($this->conexion, $id_pedido, $nuevo_estado, $nota_final, $transportista, $tracking);
            }

            return [
                'ok'              => true,
                'estado_anterior' => $actual,
                'estado_nuevo'    => $nuevo_estado,
                'nota'            => $nota_final,
                'stock_devuelto'  => $stock_devuelto,
            ];

        } catch (PedidoException $e) {

            $this->conexion->rollback();
            return ['ok' => false, 'error' => $e->getMessage()];

        } catch (Throwable $e) {

            $this->conexion->rollback();
            error_log("Error al cambiar el estado del pedido: " . $e->getMessage());
            return ['ok' => false, 'error' => "No se pudo actualizar el pedido. Probá de nuevo."];
        }
    }

    // Repone el stock de un pedido cancelado, con su movimiento en el historial de stock.
    // Solo devuelve lo que realmente se descontó al comprar (así los pedidos viejos no suman stock de más).
    private function devolverStock(int $id_pedido): int
    {
        $ref_venta = "PEDIDO-" . $id_pedido;
        $ref_dev   = "DEV-PEDIDO-" . $id_pedido;

        // Si por algún motivo ya se devolvió, no se repite.
        $stmt = $this->conexion->prepare("SELECT 1 FROM historial_movimientos WHERE referencia_ext = ? LIMIT 1");
        $stmt->bind_param("s", $ref_dev);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return 0;
        }

        $stmt = $this->conexion->prepare(
            "SELECT rela_id_productos, SUM(-cantidad) AS devolver
             FROM historial_movimientos
             WHERE referencia_ext = ? AND cantidad < 0
             GROUP BY rela_id_productos
             HAVING devolver > 0"
        );
        $stmt->bind_param("s", $ref_venta);
        $stmt->execute();
        $res = $stmt->get_result();

        $lineas = [];
        while ($f = $res->fetch_assoc()) {
            $lineas[] = [(int) $f['rela_id_productos'], (int) $f['devolver']];
        }

        if (empty($lineas)) {
            return 0;
        }

        // Concepto de movimiento "Devolución de cliente" (entrada). Se busca sin depender de la tilde.
        $res = $this->conexion->query(
            "SELECT id_concepto FROM conceptos_movimiento
             WHERE tipo_movimiento = 'E' AND es_activo = 1 AND descripcion LIKE 'Devoluci%cliente%'
             ORDER BY id_concepto LIMIT 1"
        );
        $c = $res->fetch_assoc();

        if ($c) {
            $id_concepto = (int) $c['id_concepto'];
        } else {
            $this->conexion->query(
                "INSERT INTO conceptos_movimiento (descripcion, tipo_movimiento, es_activo)
                 VALUES ('Devolucion de cliente', 'E', 1)"
            );
            $id_concepto = (int) $this->conexion->insert_id;
        }

        $stmtStock = $this->conexion->prepare("UPDATE productos SET stock = stock + ? WHERE id_producto = ?");
        $stmtHist  = $this->conexion->prepare(
            "INSERT INTO historial_movimientos
                (fecha_movimiento, cantidad, referencia_ext, comentario, rela_id_productos, rela_id_conceptos)
             VALUES (CURDATE(), ?, ?, 'Cancelación de pedido', ?, ?)"
        );

        $total = 0;
        foreach ($lineas as [$id_producto, $cantidad]) {
            $stmtStock->bind_param("ii", $cantidad, $id_producto);
            $stmtStock->execute();

            $stmtHist->bind_param("isii", $cantidad, $ref_dev, $id_producto, $id_concepto);
            $stmtHist->execute();

            $total += $cantidad;
        }

        return $total;
    }

    // =====================================================
    //  PAGOS Y FACTURAS
    // =====================================================
    public const IVA_PORCENTAJE = 21.0; // los precios de la tienda ya incluyen IVA

    // Crea la factura del pedido (sin abrir transacción: la maneja quien llama).
    // Devuelve ['ok'=>true,'id_factura'=>..,'numero'=>..] o ['ok'=>false,'error'=>..]
    private function emitirFacturaInterna(int $id_pedido): array
    {
        $st = $this->conexion->prepare(
            "SELECT p.estado, p.monto_total, ci.nombre AS condicion
             FROM pedidos p
             INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
             LEFT JOIN condiciones_iva ci ON ci.id_condicion_iva = c.rela_id_condicion_iva
             WHERE p.id_pedidos = ? FOR UPDATE"
        );
        $st->bind_param("i", $id_pedido);
        $st->execute();
        $p = $st->get_result()->fetch_assoc();

        if (!$p) {
            return ['ok' => false, 'error' => 'El pedido no existe.'];
        }
        if ($p['estado'] === 'Cancelado') {
            return ['ok' => false, 'error' => 'No se factura un pedido cancelado.'];
        }

        $st = $this->conexion->prepare("SELECT id_facturas, numero_factura FROM facturas WHERE rela_id_pedidos = ? LIMIT 1");
        $st->bind_param("i", $id_pedido);
        $st->execute();
        if ($ya = $st->get_result()->fetch_assoc()) {
            return ['ok' => false, 'error' => 'Ese pedido ya tiene factura.', 'ya_existia' => true,
                    'id_factura' => (int) $ya['id_facturas'], 'numero' => $ya['numero_factura']];
        }

        $total  = round((float) $p['monto_total'], 2);
        $neto   = round($total / (1 + self::IVA_PORCENTAJE / 100), 2);
        $iva    = round($total - $neto, 2);
        $tipo   = (stripos((string) $p['condicion'], 'inscripto') !== false) ? 'A' : 'B';

        $sig    = (int) $this->conexion->query("SELECT COALESCE(MAX(id_facturas),0)+1 FROM facturas FOR UPDATE")->fetch_row()[0];
        $numero = 'F-' . str_pad((string) $sig, 4, '0', STR_PAD_LEFT);

        $st = $this->conexion->prepare(
            "INSERT INTO facturas (fecha_emicion, numero_factura, monto_total, tipo_factura, monto_total_bruto, estado_fiscal, monto_total_impuesto, rela_id_pedidos)
             VALUES (NOW(), ?, ?, ?, ?, '1', ?, ?)"
        );
        $st->bind_param("sdsddi", $numero, $total, $tipo, $neto, $iva, $id_pedido);
        $st->execute();

        return ['ok' => true, 'id_factura' => $this->conexion->insert_id, 'numero' => $numero, 'tipo' => $tipo];
    }

    // Emisión manual (botón "Emitir factura" del administrador).
    public function emitirFactura(int $id_pedido): array
    {
        try {
            $this->conexion->begin_transaction();
            $r = $this->emitirFacturaInterna($id_pedido);
            if ($r['ok']) {
                $this->conexion->commit();
                avisar_factura_emitida($this->conexion, $id_pedido, (string) ($r['numero'] ?? ''));
            } else {
                $this->conexion->rollback();
            }
            return $r;
        } catch (Throwable $e) {
            $this->conexion->rollback();
            error_log("Error al emitir factura: " . $e->getMessage());
            return ['ok' => false, 'error' => 'No se pudo emitir la factura.'];
        }
    }

    // Marca el pago como aprobado y emite la factura automáticamente.
    public function aprobarPago(int $id_pedido, string $autor): array
    {
        $this->asegurarHistorial(); // antes de la transacción (CREATE TABLE hace commit implícito)

        try {
            $this->conexion->begin_transaction();

            $st = $this->conexion->prepare(
                "SELECT pg.id_pagos, pg.estado_pagos, p.estado
                 FROM pedidos p
                 LEFT JOIN pagos pg ON pg.rela_id_pedidos = p.id_pedidos
                 WHERE p.id_pedidos = ? ORDER BY pg.id_pagos DESC LIMIT 1 FOR UPDATE"
            );
            $st->bind_param("i", $id_pedido);
            $st->execute();
            $f = $st->get_result()->fetch_assoc();

            if (!$f || empty($f['id_pagos'])) {
                throw new Exception('El pedido no tiene un pago registrado.');
            }
            if ($f['estado'] === 'Cancelado') {
                throw new Exception('El pedido está cancelado.');
            }
            if ($f['estado_pagos'] === 'Aprobado') {
                throw new Exception('El pago ya estaba aprobado.');
            }

            $st = $this->conexion->prepare("UPDATE pagos SET estado_pagos = 'Aprobado' WHERE id_pagos = ?");
            $st->bind_param("i", $f['id_pagos']);
            $st->execute();

            $fac = $this->emitirFacturaInterna($id_pedido);
            $numero = $fac['numero'] ?? null;

            $nota = 'Pago aprobado.' . (!empty($fac['ok']) ? ' Se emitió la factura ' . $numero . '.' : '');
            $this->registrarHistorial($id_pedido, $f['estado'], $autor, $nota);

            $this->conexion->commit();

            avisar_pago_aprobado($this->conexion, $id_pedido, !empty($fac['ok']) ? $numero : null);

            return ['ok' => true, 'factura' => $numero, 'factura_nueva' => !empty($fac['ok'])];
        } catch (Throwable $e) {
            $this->conexion->rollback();
            return ['ok' => false, 'error' => $e->getMessage() ?: 'No se pudo aprobar el pago.'];
        }
    }

    // Factura completa para mostrar. Si se pasa $id_usuario, solo la devuelve si es de ese cliente.
    public function obtenerFactura(int $id_factura, ?int $id_usuario = null): ?array
    {
        $sql = "SELECT f.id_facturas, f.numero_factura, f.fecha_emicion, f.tipo_factura, f.monto_total,
                       f.monto_total_bruto, f.monto_total_impuesto, f.rela_id_pedidos,
                       p.fecha_pedidos, p.estado, p.monto_total AS total_pedido,
                       c.nombre, c.apellido, c.cuil_cuit, ci.nombre AS condicion_iva,
                       d.calle, d.numero_exterior, d.barrio_colonia, l.nombre_localidad, pr.nombre_provincia,
                       e.direccion_entrega, e.recibe_nombre, e.empresa_transporte, e.num_seguimiento,
                       (SELECT mp.nombre FROM pagos pg INNER JOIN metodos_pago mp ON mp.id_metodo_pago = pg.rela_id_metodo_pago
                        WHERE pg.rela_id_pedidos = p.id_pedidos ORDER BY pg.id_pagos DESC LIMIT 1) AS metodo_pago,
                       (SELECT pg.estado_pagos FROM pagos pg
                        WHERE pg.rela_id_pedidos = p.id_pedidos ORDER BY pg.id_pagos DESC LIMIT 1) AS estado_pagos
                FROM facturas f
                INNER JOIN pedidos p ON p.id_pedidos = f.rela_id_pedidos
                INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
                LEFT JOIN condiciones_iva ci ON ci.id_condicion_iva = c.rela_id_condicion_iva
                LEFT JOIN direcciones d ON d.id_direccion = c.rela_id_direccion
                LEFT JOIN localidades l ON l.id_localidad = d.rela_id_localidad
                LEFT JOIN provincias pr ON pr.id_provincia = l.rela_id_provincia
                LEFT JOIN envios e ON e.rela_id_pedidos = p.id_pedidos
                WHERE f.id_facturas = ?";
        if ($id_usuario !== null) {
            $sql .= " AND c.rela_id_usuario = ?";
        }
        $sql .= " LIMIT 1";

        $st = $this->conexion->prepare($sql);
        if ($id_usuario !== null) {
            $st->bind_param("ii", $id_factura, $id_usuario);
        } else {
            $st->bind_param("i", $id_factura);
        }
        $st->execute();
        $f = $st->get_result()->fetch_assoc();

        if (!$f) {
            return null;
        }

        $f['items'] = $this->itemsDelPedido((int) $f['rela_id_pedidos']);
        return $f;
    }
}