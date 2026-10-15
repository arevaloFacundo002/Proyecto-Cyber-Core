<?php

require_once __DIR__ . '/Database.php';

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

        $pedido['items'] = $this->itemsDelPedido($id_pedido);

        return $pedido;
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
    //  PANEL ADMIN: listar todos los pedidos (con filtro de estado)
    // =====================================================
    public function listarPedidosAdmin(string $estado = 'todos'): array
    {
        $sql = "SELECT p.id_pedidos, p.fecha_pedidos, p.estado, p.monto_total,
                       c.nombre, c.apellido,
                       e.estado_envio, e.empresa_transporte
                FROM pedidos p
                INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
                LEFT JOIN envios e ON e.rela_id_pedidos = p.id_pedidos";

        if ($estado !== 'todos' && array_key_exists($estado, self::ESTADOS)) {
            $sql .= " WHERE p.estado = ?";
        }

        $sql .= " ORDER BY p.id_pedidos DESC";

        $stmt = $this->conexion->prepare($sql);

        if ($estado !== 'todos' && array_key_exists($estado, self::ESTADOS)) {
            $stmt->bind_param("s", $estado);
        }

        $stmt->execute();
        $res = $stmt->get_result();

        $pedidos = [];
        while ($fila = $res->fetch_assoc()) {
            $pedidos[] = $fila;
        }
        return $pedidos;
    }

    // =====================================================
    //  PANEL ADMIN: un pedido puntual, sin restricción de dueño
    // =====================================================
    public function obtenerPedidoAdmin(int $id_pedido): ?array
    {
        $sql = "SELECT p.id_pedidos, p.fecha_pedidos, p.estado, p.moneda, p.monto_total,
                       c.id_cliente, c.nombre AS cliente_nombre, c.apellido AS cliente_apellido,
                       (SELECT ct.valor FROM contactos ct
                        WHERE ct.rela_id_cliente = c.id_cliente
                        ORDER BY ct.id_contacto LIMIT 1) AS cliente_telefono,
                       e.direccion_entrega, e.recibe_quien, e.recibe_nombre, e.recibe_telefono,
                       e.tipo_lugar, e.piso_depto_torre, e.referencia,
                       e.empresa_transporte, e.estado_envio, e.fecha_estimada_entrega,
                       pg.estado_pagos, pg.referencia_transaccion,
                       mp.nombre AS metodo_pago
                FROM pedidos p
                INNER JOIN clientes c ON c.id_cliente = p.rela_id_cliente
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

        $pedido['items'] = $this->itemsDelPedido($id_pedido);

        return $pedido;
    }

    // =====================================================
    //  PANEL ADMIN: cambiar el estado de un pedido
    // =====================================================
    public function cambiarEstado(int $id_pedido, string $nuevo_estado): bool
    {
        if (!array_key_exists($nuevo_estado, self::ESTADOS)) {
            return false;
        }

        $stmt = $this->conexion->prepare(
            "UPDATE pedidos SET estado = ? WHERE id_pedidos = ?"
        );
        $stmt->bind_param("si", $nuevo_estado, $id_pedido);
        $stmt->execute();

        // El estado del envío va siempre de la mano del estado del pedido,
        // para no tener que mantener dos cosas sincronizadas a mano.
        $stmt = $this->conexion->prepare(
            "UPDATE envios SET estado_envio = ? WHERE rela_id_pedidos = ?"
        );
        $stmt->bind_param("si", $nuevo_estado, $id_pedido);
        $stmt->execute();

        return true;
    }
}