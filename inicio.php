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
        <a class="ir-tienda" href="home.php">🛒 Tienda</a>
        <a class="logout" href="logout.php">Cerrar sesión</a>
    </div>
</header>

<div class="container">
    <h1>Panel del Administrador</h1>

    <div class="buscador-modulos">
        <input
            type="text"
            id="buscadorModulos"
            placeholder="🔍 Buscar módulo del sistema... (ej: marcas, usuarios, IVA, reportes)"
            autocomplete="off">
        <div class="resultados-modulos" id="resultadosModulos"></div>
    </div>

    <div class="grid">
        
        <div class="card">
            <h2>ABM Usuarios</h2>
            <p>Alta, baja y modificación del sistema.</p>
            <a href="usuarios/listar.php">Gestionar</a>
        </div>

        <div class="card">
            <h2>Tablas maestras</h2>
            <p>Altas, bajas y modificaciones.</p>
            <a href="views/panel_tablas.php">Gestionar</a>
        </div>

        <div class="card">
            <h2>Productos y Proveedores</h2>
            <p>Entradas del sistema.</p>
            <a href="views/panel_inputs.php">Gestionar</a>
        </div>

        <div class="card">
            <h2>Movimientos</h2>
            <p>Historial de movimientos de productos.</p>
            <a href="views/movimientos/listarMovimiento.php">Ver</a>
        </div>

        <div class="card">
            <h2>Consultas</h2>
            <p>Consultas SQL del sistema.</p>
            <a href="consultas/menu_consultas.php">Ver consultas</a>
        </div>

        <div class="card">
            <h2>Reporte de Usuarios</h2>
            <p>Generar archivo PDF con información del sistema.</p>
            <a href="reportes/reporte_usuarios.php">Ver reporte</a>
        </div>

    </div>
</div>

<script src="js/theme-toggle.js"></script>

<script>
// ===================== BUSCADOR DE MÓDULOS DEL ADMIN =====================
// Lista de todos los módulos administrables del sistema.
// Para agregar un módulo nuevo en el futuro, solo hay que sumar un objeto acá.
const modulosAdmin = [
    { nombre: "ABM Usuarios", grupo: "General", icono: "👥",
      descripcion: "Alta, baja y modificación de usuarios del sistema.",
      url: "usuarios/listar.php",
      claves: "usuarios abm alta baja modificacion cuentas" },

    { nombre: "Tablas maestras", grupo: "General", icono: "🗂️",
      descripcion: "Menú de tablas maestras del sistema.",
      url: "views/panel_tablas.php",
      claves: "tablas maestras" },

    { nombre: "Productos y Proveedores", grupo: "General", icono: "📦",
      descripcion: "Menú de entradas del sistema.",
      url: "views/panel_inputs.php",
      claves: "productos proveedores inputs entradas" },

    { nombre: "Movimientos de stock", grupo: "General", icono: "🔁",
      descripcion: "Historial de movimientos de productos.",
      url: "views/movimientos/listarMovimiento.php",
      claves: "movimientos stock historial entradas salidas" },

    { nombre: "Consultas SQL", grupo: "General", icono: "🧮",
      descripcion: "Consultas SQL del sistema.",
      url: "consultas/menu_consultas.php",
      claves: "consultas sql reportes queries" },

    { nombre: "Reporte de Usuarios", grupo: "General", icono: "📄",
      descripcion: "Generar archivo PDF con información del sistema.",
      url: "reportes/reporte_usuarios.php",
      claves: "reporte reportes pdf usuarios" },

    { nombre: "Categorías", grupo: "Tablas maestras", icono: "📂",
      descripcion: "Gestión de categorías y subcategorías de productos.",
      url: "views/tablas_maestras/categorias/listarCategoria.php",
      claves: "categorias subcategorias productos" },

    { nombre: "Conceptos de Movimiento", grupo: "Tablas maestras", icono: "🔄",
      descripcion: "Gestión de entradas, salidas y ajustes de stock.",
      url: "views/tablas_maestras/conceptoMovimientos/listarConcepto.php",
      claves: "conceptos movimiento entradas salidas ajustes stock" },

    { nombre: "Marcas", grupo: "Tablas maestras", icono: "🏷️",
      descripcion: "Gestión de fabricantes y marcas comerciales.",
      url: "views/tablas_maestras/marcas/listarMarca.php",
      claves: "marcas fabricantes" },

    { nombre: "Métodos de Pago", grupo: "Tablas maestras", icono: "💳",
      descripcion: "Administración de medios de pago habilitados.",
      url: "views/tablas_maestras/metodosPago/listarMetodo.php",
      claves: "metodos pago medios tarjeta efectivo" },

    { nombre: "Condiciones IVA", grupo: "Tablas maestras", icono: "🧾",
      descripcion: "Configuración de condiciones fiscales.",
      url: "views/tablas_maestras/CondicionIva/listarCondicion.php",
      claves: "iva condiciones fiscales impuestos" },

    { nombre: "Modelos de Productos", grupo: "Tablas maestras", icono: "💻",
      descripcion: "Gestión de modelos asociados a las marcas.",
      url: "views/tablas_maestras/modelosProducto/listarModelo.php",
      claves: "modelos productos marcas" },

    { nombre: "Provincias", grupo: "Tablas maestras", icono: "🗺️",
      descripcion: "Gestión de provincias argentinas.",
      url: "views/tablas_maestras/provincias/listarProvincia.php",
      claves: "provincias argentina ubicacion" },

    { nombre: "Localidades", grupo: "Tablas maestras", icono: "📍",
      descripcion: "Gestión de ciudades y localidades.",
      url: "views/tablas_maestras/localidades/listarLocalidad.php",
      claves: "localidades ciudades ubicacion" },

    { nombre: "Perfiles", grupo: "Tablas maestras", icono: "👤",
      descripcion: "Administración de roles y permisos.",
      url: "views/tablas_maestras/perfiles/listarPerfil.php",
      claves: "perfiles roles permisos" },

    { nombre: "Tipos de Contactos", grupo: "Tablas maestras", icono: "📲",
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
      claves: "productos stock catalogo" }
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