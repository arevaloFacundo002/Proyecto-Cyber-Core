<?php
// Si NO hay usuario logueado → al login
require_once "auth/auth.php";

// Si el usuario NO es admin ni empleado → a la tienda
if ($_SESSION['rol'] != "administrador" && $_SESSION['rol'] != "empleado") {
    header("Location: home.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>CyberCore - Panel</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Cache-Control" content="no-store" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />

<link href="css/theme.css" rel="stylesheet">

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
}

/* HEADER */
header {
    background: var(--cc-superficie-2);
    padding: 20px 50px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid rgba(0, 234, 255, 0.3);
    box-shadow: 0 0 15px rgba(0, 234, 255, 0.2);
}

.logo {
    font-size: 28px;
    font-weight: bold;
    color: var(--cc-primario);
    letter-spacing: 2px;
    text-shadow: 0 0 8px rgba(0, 234, 255, 0.6);
}

.user {
    font-size: 18px;
    color: var(--cc-texto);
}

.logout {
    color: var(--cc-peligro);
    margin-left: 10px;
    text-decoration: none;
    font-weight: bold;
}
.logout:hover {
    text-decoration: underline;
}

.ir-tienda {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-left: 18px;
    padding: 8px 16px;
    border-radius: 20px;
    background: var(--cc-superficie);
    border: 1px solid var(--cc-borde);
    color: var(--cc-texto);
    text-decoration: none;
    font-weight: bold;
    transition: 0.2s;
}

.ir-tienda:hover {
    border-color: var(--cc-primario);
    color: var(--cc-primario);
}

/* CONTENIDO */
.container {
    padding: 50px;
}

h1 {
    text-align: center;
    margin-bottom: 40px;
    color: var(--cc-primario);
    text-shadow: 0 0 12px rgba(0, 234, 255, 0.5);
}

/* GRID */
.grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 35px;
    margin-top: 40px;
}

.card {
    background: var(--cc-superficie);
    border: 1px solid var(--cc-borde);
    padding: 25px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 0 15px rgba(0,0,0,0.35);
    transition: 0.3s;
    color: var(--cc-texto);
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0 20px rgba(0, 234, 255, 0.35);
    border-color: var(--cc-primario);
}

.card h2 {
    margin-bottom: 15px;
    color: var(--cc-texto);
}

.card a {
    background: var(--cc-primario);
    color: var(--cc-texto-sobre-primario);
    padding: 12px 20px;
    border-radius: 20px;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
    margin-top: 15px;
    transition: 0.3s;
}

.card a:hover {
    background: var(--cc-primario-hover);
}

/* RESPONSIVE */
@media (max-width: 900px) {
    .grid {
        grid-template-columns: 1fr;
    }
}

/* BUSCADOR DE MÓDULOS */
.buscador-modulos {
    max-width: 650px;
    margin: 0 auto 10px auto;
    position: relative;
}

.buscador-modulos input {
    width: 100%;
    box-sizing: border-box;
    padding: 15px 20px;
    border-radius: 30px;
    border: 1px solid var(--cc-borde);
    outline: none;
    font-size: 16px;
    background: var(--cc-superficie);
    color: var(--cc-texto);
}

.buscador-modulos input:focus {
    border-color: var(--cc-primario);
    box-shadow: 0 0 10px rgba(0, 234, 255, 0.35);
}

.resultados-modulos {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    right: 0;
    background: var(--cc-superficie);
    border: 1px solid var(--cc-borde);
    border-radius: 14px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.35);
    overflow: hidden;
    z-index: 50;
    display: none;
}

.resultados-modulos.activo {
    display: block;
}

.resultados-modulos a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    text-decoration: none;
    color: var(--cc-texto);
    border-bottom: 1px solid var(--cc-borde);
    transition: 0.2s;
}

.resultados-modulos a:last-child {
    border-bottom: none;
}

.resultados-modulos a:hover,
.resultados-modulos a.activo-teclado {
    background: var(--cc-superficie-2);
}

.resultados-modulos .icono-resultado {
    font-size: 20px;
    flex-shrink: 0;
}

.resultados-modulos .texto-resultado b {
    display: block;
    color: var(--cc-primario);
}

.resultados-modulos .texto-resultado small {
    color: var(--cc-texto-secundario);
}

.resultados-modulos .grupo-resultado {
    padding: 8px 20px;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--cc-texto-secundario);
    background: var(--cc-superficie-2);
}

.sin-resultados {
    padding: 18px 20px;
    color: var(--cc-texto-secundario);
    text-align: center;
}
</style>
</head>

<body>

<header>
    <div class="logo">CYBERCORE PANEL</div>
    <div class="user">
        Hola, <b><?php echo $_SESSION['usuario']; ?></b>
        <?php $ruta_raiz = ''; include __DIR__ . '/includes/campana.php'; ?>
        <a class="ir-tienda" href="home.php">🛒 Tienda</a>
        <a class="logout" href="logout.php">Cerrar sesión</a>
    </div>
</header>

<div class="container">
    <?php $es_admin = (($_SESSION['rol'] ?? '') === 'administrador'); ?>
    <h1><?php echo $es_admin ? 'Panel del Administrador' : 'Panel del Empleado'; ?></h1>

    <div class="buscador-modulos">
        <input
            type="text"
            id="buscadorModulos"
            placeholder="🔍 Buscar módulo del sistema... (ej: marcas, usuarios, IVA, reportes)"
            autocomplete="off">
        <div class="resultados-modulos" id="resultadosModulos"></div>
    </div>

    <div class="grid">
        
        <?php if ($es_admin): ?>
        <div class="card">
            <h2>Usuarios y Roles</h2>
            <p>Agregá empleados y administradores, cambiá roles y bloqueá cuentas.</p>
            <a href="usuarios/listar.php">Gestionar</a>
        </div>
        <?php endif; ?>

        <?php if ($es_admin): ?>
        <div class="card">
            <h2>Configuración del sistema</h2>
            <p>Categorías, marcas, métodos de pago, IVA, provincias y más.</p>
            <a href="views/panel_tablas.php">Configurar</a>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2>Pedidos</h2>
            <p>Ver pedidos de clientes y actualizar su estado.</p>
            <a href="views/pedidos/listarPedidos.php">Gestionar</a>
        </div>

        <?php if ($es_admin): ?>
        <div class="card">
            <h2>Promociones</h2>
            <p>Descuentos automáticos por monto de compra y fechas.</p>
            <a href="views/promociones/listarPromociones.php">Gestionar</a>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2>Devoluciones</h2>
            <p>Pedidos de devolución de los clientes: aprobar o rechazar.</p>
            <a href="views/devoluciones/listarDevoluciones.php">Gestionar</a>
        </div>

        <?php if ($es_admin): ?>
        <div class="card">
            <h2>Comunicaciones</h2>
            <p>Anuncios y avisos para clientes o personal, por la app, correo o WhatsApp.</p>
            <a href="views/anuncios/listarAnuncio.php">Gestionar</a>
        </div>
        <?php endif; ?>

        <?php if ($es_admin): ?>
        <div class="card">
            <h2>Cupones</h2>
            <p>Códigos de descuento que el cliente escribe al comprar.</p>
            <a href="views/cupones/listarCupones.php">Gestionar</a>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2>Facturas</h2>
            <p>Facturas emitidas y pedidos pendientes de facturar.</p>
            <a href="consultas/facturas.php">Gestionar</a>
        </div>

        <div class="card">
            <h2>Transacciones</h2>
            <p>Todos los pagos del sitio, con totales por estado y método.</p>
            <a href="consultas/transacciones.php">Gestionar</a>
        </div>

        <?php if ($es_admin): ?>
        <div class="card">
            <h2>Cuenta bancaria</h2>
            <p>Datos de la cuenta para pagos por transferencia.</p>
            <a href="consultas/cuenta_bancaria.php">Gestionar</a>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2>Caja</h2>
            <p>Apertura, ingresos y egresos, cierre del efectivo e historial.</p>
            <a href="consultas/caja.php">Gestionar</a>
        </div>

        <div class="card">
            <h2>Productos y Proveedores</h2>
            <p>Entradas del sistema.</p>
            <a href="views/panel_inputs.php">Gestionar</a>
        </div>

        <div class="card">
            <h2>Movimientos</h2>
            <p>Facturas de compra y venta cargadas: cada factura es un solo movimiento.</p>
            <a href="views/movimientos/listarFacturas.php">Ver</a>
        </div>

        <div class="card">
            <h2>Cargar factura</h2>
            <p>Cargá una factura completa de compra o venta, con todos sus productos de una vez.</p>
            <a href="views/movimientos/cargarFactura.php">Cargar</a>
        </div>

        <div class="card">
            <h2>Consultas y Reportes</h2>
            <p>Consultas del sistema y reportes en PDF y Excel con gráficos.</p>
            <a href="consultas/menu_consultas.php">Ver consultas y reportes</a>
        </div>

        <?php if ($es_admin): ?>
        <?php endif; ?>

    </div>
</div>

<script src="js/theme-toggle.js"></script>

<script>
// ===================== BUSCADOR DE MÓDULOS DEL ADMIN =====================
// Lista de todos los módulos administrables del sistema.
// Para agregar un módulo nuevo en el futuro, solo hay que sumar un objeto acá.
const ES_ADMIN = <?php echo $es_admin ? 'true' : 'false'; ?>;
const PREFIJOS_SOLO_ADMIN = ['usuarios/', 'views/tablas_maestras/', 'views/cupones/', 'views/promociones/', 'views/anuncios/', 'views/panel_tablas.php', 'consultas/cuenta_bancaria.php', 'reportes/reporte_usuarios.php'];

const modulosAdmin = [
    { nombre: "Usuarios y Roles", grupo: "General", icono: "👥",
      descripcion: "Agregá empleados y administradores, cambiá roles y bloqueá cuentas.",
      url: "usuarios/listar.php",
      claves: "usuarios abm alta baja modificacion cuentas" },

    { nombre: "Pedidos", grupo: "General", icono: "📦",
      descripcion: "Ver pedidos de clientes y actualizar su estado.",
      url: "views/pedidos/listarPedidos.php",
      claves: "pedidos ordenes compras estado seguimiento tracking transportista pago aprobar cancelar" },

    { nombre: "Promociones", grupo: "General", icono: "🏷",
      descripcion: "Descuentos automáticos por porcentaje o monto fijo, con compra mínima y fechas.",
      url: "views/promociones/listarPromociones.php",
      claves: "promociones promocion descuentos descuento ofertas porcentaje monto minimo" },

    { nombre: "Devoluciones", grupo: "General", icono: "↩",
      descripcion: "Pedidos de devolución de los clientes: aprobar, rechazar y devolver al stock.",
      url: "views/devoluciones/listarDevoluciones.php",
      claves: "devoluciones devolucion reembolso cambio producto defectuoso stock aprobar rechazar" },

    { nombre: "Comunicaciones", grupo: "General", icono: "📣",
      descripcion: "Anuncios y avisos para clientes o personal: app, correo y WhatsApp.",
      url: "views/anuncios/listarAnuncio.php",
      claves: "comunicaciones anuncios avisos notificaciones campanita whatsapp correo mensajes ofertas" },

    { nombre: "Historial de notificaciones", grupo: "General", icono: "🔔",
      descripcion: "Todas tus notificaciones y si se enviaron por correo o WhatsApp.",
      url: "views/notificaciones/historial.php",
      claves: "notificaciones historial campanita avisos" },

    { nombre: "Cupones", grupo: "General", icono: "🎟",
      descripcion: "Códigos de descuento con mínimo de compra, vigencia y límite de usos.",
      url: "views/cupones/listarCupones.php",
      claves: "cupones cupon codigo descuento regalo promocion usos limite" },

    { nombre: "Facturas", grupo: "General", icono: "🧾",
      descripcion: "Facturas emitidas, detalle imprimible (A y B) y pedidos sin facturar.",
      url: "consultas/facturas.php",
      claves: "facturas factura emitir comprobante iva tipo a tipo b pdf imprimir" },

    { nombre: "Transacciones", grupo: "General", icono: "💳",
      descripcion: "Todos los pagos del sitio con filtros y totales por estado y método de pago.",
      url: "consultas/transacciones.php",
      claves: "transacciones pagos cobros dinero metodo de pago reembolso pendiente aprobado" },

    { nombre: "Cuenta bancaria", grupo: "General", icono: "🏦",
      descripcion: "Titular, banco, CBU y alias que ve el cliente al pagar por transferencia.",
      url: "consultas/cuenta_bancaria.php",
      claves: "cuenta bancaria cbu alias transferencia banco titular datos pago" },

    { nombre: "Caja", grupo: "General", icono: "🧮",
      descripcion: "Abrir y cerrar la caja, anotar ingresos y egresos y ver el efectivo esperado.",
      url: "consultas/caja.php",
      claves: "caja abrir cerrar cierre apertura arqueo efectivo ingreso egreso turno" },

    { nombre: "Historial de cajas", grupo: "General", icono: "📜",
      descripcion: "Cajas anteriores con su diferencia y reporte en PDF.",
      url: "consultas/caja_historial.php",
      claves: "historial cajas cierres diferencia sobrante faltante reporte pdf" },

    { nombre: "Pedidos sin facturar", grupo: "General", icono: "⏳",
      descripcion: "Pedidos que todavía no tienen factura emitida.",
      url: "consultas/facturas.php?tab=pendientes",
      claves: "pedidos sin facturar pendientes emitir factura" },

    { nombre: "Pedidos nuevos", grupo: "General", icono: "🟡",
      descripcion: "Pedidos recién recibidos, a la espera de confirmar.",
      url: "views/pedidos/listarPedidos.php?estado=Pedido%20recibido",
      claves: "pedidos nuevos recibidos pendientes confirmar" },

    { nombre: "Pedidos en camino", grupo: "General", icono: "🚚",
      descripcion: "Pedidos despachados que están en camino al cliente.",
      url: "views/pedidos/listarPedidos.php?estado=En%20camino",
      claves: "pedidos en camino despachados envio entrega seguimiento" },

    { nombre: "Productos y Proveedores", grupo: "General", icono: "📦",
      descripcion: "Menú de entradas del sistema.",
      url: "views/panel_inputs.php",
      claves: "productos proveedores inputs entradas" },

    { nombre: "Movimientos de stock", grupo: "General", icono: "🔁",
      descripcion: "Facturas de compra y venta cargadas, una fila por factura.",
      url: "views/movimientos/listarFacturas.php",
      claves: "movimientos stock historial entradas salidas facturas compras ventas" },

    { nombre: "Cargar factura", grupo: "General", icono: "📥",
      descripcion: "Cargar una factura completa de compra o venta con todos sus productos.",
      url: "views/movimientos/cargarFactura.php",
      claves: "cargar factura compra venta proveedor stock productos entrada salida" },

    { nombre: "Detalle de movimientos por producto", grupo: "General", icono: "📋",
      descripcion: "Historial de movimientos de stock producto por producto.",
      url: "views/movimientos/listarMovimiento.php",
      claves: "detalle movimientos producto historial ajuste inventario" },

    { nombre: "Categorías", grupo: "Configuración del sistema", icono: "📂",
      descripcion: "Gestión de categorías y subcategorías de productos.",
      url: "views/tablas_maestras/categorias/listarCategoria.php",
      claves: "categorias subcategorias productos" },

    { nombre: "Conceptos de Movimiento", grupo: "Configuración del sistema", icono: "🔄",
      descripcion: "Gestión de entradas, salidas y ajustes de stock.",
      url: "views/tablas_maestras/conceptoMovimientos/listarConcepto.php",
      claves: "conceptos movimiento entradas salidas ajustes stock" },

    { nombre: "Marcas", grupo: "Configuración del sistema", icono: "🏷️",
      descripcion: "Gestión de fabricantes y marcas comerciales.",
      url: "views/tablas_maestras/marcas/listarMarca.php",
      claves: "marcas fabricantes" },

    { nombre: "Métodos de Pago", grupo: "Configuración del sistema", icono: "💳",
      descripcion: "Administración de medios de pago habilitados.",
      url: "views/tablas_maestras/metodosPago/listarMetodo.php",
      claves: "metodos pago medios tarjeta efectivo" },

    { nombre: "Condiciones IVA", grupo: "Configuración del sistema", icono: "🧾",
      descripcion: "Configuración de condiciones fiscales.",
      url: "views/tablas_maestras/CondicionIva/listarCondicion.php",
      claves: "iva condiciones fiscales impuestos" },

    { nombre: "Modelos de Productos", grupo: "Configuración del sistema", icono: "💻",
      descripcion: "Gestión de modelos asociados a las marcas.",
      url: "views/tablas_maestras/modelosProducto/listarModelo.php",
      claves: "modelos productos marcas" },

    { nombre: "Provincias", grupo: "Configuración del sistema", icono: "🗺️",
      descripcion: "Gestión de provincias argentinas.",
      url: "views/tablas_maestras/provincias/listarProvincia.php",
      claves: "provincias argentina ubicacion" },

    { nombre: "Localidades", grupo: "Configuración del sistema", icono: "📍",
      descripcion: "Gestión de ciudades y localidades.",
      url: "views/tablas_maestras/localidades/listarLocalidad.php",
      claves: "localidades ciudades ubicacion" },

    { nombre: "Perfiles", grupo: "Configuración del sistema", icono: "👤",
      descripcion: "Administración de roles y permisos.",
      url: "views/tablas_maestras/perfiles/listarPerfil.php",
      claves: "perfiles roles permisos" },

    { nombre: "Tipos de Contactos", grupo: "Configuración del sistema", icono: "📲",
      descripcion: "Gestión de los tipos de contacto con los usuarios.",
      url: "views/tablas_maestras/tiposContacto/listarTipoContacto.php",
      claves: "tipos contacto telefono email" },

    { nombre: "Proveedores", grupo: "Productos y Proveedores", icono: "👤",
      descripcion: "Gestión de Proveedores y contactos.",
      url: "views/proveedores/listarProveedor.php",
      claves: "proveedores contactos" },

    { nombre: "Productos", grupo: "Productos y Proveedores", icono: "💻",
      descripcion: "Gestión de entradas de productos.",
      url: "views/productos/listarProducto.php",
      claves: "productos stock catalogo imagenes fotos galeria precio stock cloudinary" },

    { nombre: "Consultas y Reportes", grupo: "Consultas y Reportes", icono: "🧮",
      descripcion: "Consultas del sistema y reportes en PDF y Excel con gráficos.",
      url: "consultas/menu_consultas.php",
      claves: "consultas reportes pdf excel csv graficos informes ventas inventario compras proveedores clientes devoluciones ganancias stock" },

    { nombre: "Reporte de usuarios y clientes", grupo: "Consultas y Reportes", icono: "📄",
      descripcion: "Generar archivo PDF con información del sistema.",
      url: "reportes/reporte_usuarios.php",
      claves: "reporte reportes pdf usuarios" },

    { nombre: "Últimos precios de compra", grupo: "Consultas y Reportes", icono: "🧮",
      descripcion: "Producto y proveedor con el último precio de compra.",
      url: "consultas/consulta1.php",
      claves: "ultimo precio compra producto proveedor precio compra" },

    { nombre: "Pedidos detallados", grupo: "Consultas y Reportes", icono: "🧮",
      descripcion: "Cada pedido con sus productos, cliente y envío. Filtros por fecha y estado.",
      url: "consultas/consulta2.php",
      claves: "pedidos detallados agrupados cliente envio productos" },

    { nombre: "Productos más vendidos", grupo: "Consultas y Reportes", icono: "🧮",
      descripcion: "Top 5 de productos más vendidos.",
      url: "consultas/consulta3.php",
      claves: "top productos mas vendidos ranking" },

    { nombre: "Mejores clientes", grupo: "Consultas y Reportes", icono: "🧮",
      descripcion: "Clientes con más pedidos realizados.",
      url: "consultas/consulta4.php",
      claves: "mejores clientes pedidos frecuentes" },

    { nombre: "Marcas con más productos", grupo: "Consultas y Reportes", icono: "🧮",
      descripcion: "Marcas con mayor cantidad de productos.",
      url: "consultas/consulta5.php",
      claves: "marcas cantidad productos" },
];

const inputBuscador = document.getElementById('buscadorModulos');
const cajaResultados = document.getElementById('resultadosModulos');
let indiceActivo = -1;

function normalizar(texto) {
    return texto
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, ''); // saca tildes para que "categoria" encuentre "categoría"
}

function buscarModulos(texto) {
    const termino = normalizar(texto.trim());

    if (termino === '') {
        return [];
    }

    return modulosAdmin.filter(function (m) {
        if (!ES_ADMIN && PREFIJOS_SOLO_ADMIN.some(function (pre) { return m.url.toLowerCase().indexOf(pre.toLowerCase()) === 0; })) { return false; }
        const bolsa = normalizar(m.nombre + ' ' + m.descripcion + ' ' + m.grupo + ' ' + m.claves);
        return bolsa.includes(termino);
    });
}

function renderResultados(lista) {
    indiceActivo = -1;

    if (lista.length === 0) {
        cajaResultados.innerHTML = '<div class="sin-resultados">No se encontraron módulos.</div>';
        cajaResultados.classList.add('activo');
        return;
    }

    let html = '';
    let grupoAnterior = null;

    lista.forEach(function (m) {
        if (m.grupo !== grupoAnterior) {
            html += '<div class="grupo-resultado">' + m.grupo + '</div>';
            grupoAnterior = m.grupo;
        }
        html += '<a href="' + m.url + '">' +
                    '<span class="icono-resultado">' + m.icono + '</span>' +
                    '<span class="texto-resultado"><b>' + m.nombre + '</b><small>' + m.descripcion + '</small></span>' +
                '</a>';
    });

    cajaResultados.innerHTML = html;
    cajaResultados.classList.add('activo');
}

inputBuscador.addEventListener('input', function () {
    const resultados = buscarModulos(this.value);
    if (this.value.trim() === '') {
        cajaResultados.classList.remove('activo');
        cajaResultados.innerHTML = '';
        return;
    }
    renderResultados(resultados);
});

// Navegación con teclado (flechas + enter)
inputBuscador.addEventListener('keydown', function (e) {
    const opciones = cajaResultados.querySelectorAll('a');
    if (opciones.length === 0) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        indiceActivo = (indiceActivo + 1) % opciones.length;
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        indiceActivo = (indiceActivo - 1 + opciones.length) % opciones.length;
    } else if (e.key === 'Enter') {
        if (indiceActivo >= 0) {
            e.preventDefault();
            opciones[indiceActivo].click();
        }
        return;
    } else {
        return;
    }

    opciones.forEach(function (op) { op.classList.remove('activo-teclado'); });
    opciones[indiceActivo].classList.add('activo-teclado');
    opciones[indiceActivo].scrollIntoView({ block: 'nearest' });
});

// Cerrar el desplegable al hacer clic afuera
document.addEventListener('click', function (e) {
    if (!e.target.closest('.buscador-modulos')) {
        cajaResultados.classList.remove('activo');
    }
});
</script>

</body>
</html>