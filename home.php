<?php
require_once 'models/Producto.php';
$pro = new Producto();

//Si no esta logeado lo sacamos
require_once 'auth/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $buscar = $_GET['buscar']?? "";
    $rol = $_SESSION['rol'];

    //busqueda de productos
    $productos = $pro->listar_productos($buscar);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Cache-Control" content="no-store" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />

<title>CyberCore - Inicio</title>

<style>
    body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: #f5f5f5;
}

/* HEADER */
header {
    background: #0a0a0a;
    padding: 18px 50px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: white;
    position: sticky;
    top: 0;
    z-index: 100;
}

.logo {
    font-size: 28px;
    font-weight: bold;
    letter-spacing: 1px;
    color: #00eaff;
}

nav a {
    color: white;
    margin: 0 15px;
    text-decoration: none;
    font-weight: 500;
    transition: 0.3s;
}

nav a:hover {
    color: #00eaff;
}

.logout {
    color: #ff4b4b;
    margin-left: 10px;
    text-decoration: none;
    font-weight: bold;
}
.logout:hover {
    text-decoration: underline;
}

/* HERO */
.hero {
    width: 100%;
    height: 420px;
    background: url('img/banner.jpg') center/cover no-repeat;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.hero-overlay {
    position: absolute;
    width: 100%;
    height: 100%;
    background: rgba(10, 10, 10, 0.55);
}

.hero-text {
    position: relative;
    text-align: center;
    color: white;
    z-index: 2;
}

.hero-text h1 {
    font-size: 48px;
    font-weight: bold;
    margin-bottom: 15px;
    text-shadow: 0 0 10px #00eaff;
}

.hero-text p {
    font-size: 20px;
    opacity: 0.9;
    margin-bottom: 20px;
}

.hero-text .btn {
    padding: 12px 25px;
    background: #00eaff;
    color: black;
    font-weight: bold;
    border-radius: 25px;
    text-decoration: none;
    transition: 0.3s;
}

.hero-text .btn:hover {
    background: #009ac0;
}

/* SEARCH BAR */
.search-container {
    padding: 40px;
    text-align: center;
    background: linear-gradient(90deg, #0f0f0f, #1a1a1a);
}

.search-container input {
    width: 45%;
    padding: 15px;
    border-radius: 30px;
    border: none;
    outline: none;
    font-size: 16px;
}

.search-container button {
    padding: 15px 22px;
    background: #00eaff;
    border: none;
    border-radius: 30px;
    font-size: 16px;
    margin-left: 10px;
    cursor: pointer;
    font-weight: bold;
}

.search-container button:hover {
    background: #00b1cc;
}

/* PRODUCT GRID */
.products-title {
    text-align: center;
    margin-top: 40px;
    font-size: 28px;
    color: #111;
    font-weight: bold;
}

.grid {
    padding: 40px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 25px;
}

.card {
    background: white;
    border-radius: 14px;
    padding: 18px;
    text-align: center;
    box-shadow: 0 0 10px rgba(0,0,0,0.12);
    transition: 0.3s;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0 15px rgba(0,255,255,0.4);
}

.card img {
    width: 100%;
    height: 170px;
    object-fit: contain;
}

.card h3 {
    margin: 12px 0 4px 0;
}

.card small {
    color: #777;
}

.price {
    font-size: 22px;
    color: #00b1cc;
    margin-top: 10px;
    font-weight: bold;
}

.btn-ver {
    margin-top: 12px;
    display: inline-block;
    padding: 10px 18px;
    background: #00eaff;
    border-radius: 20px;
    text-decoration: none;
    color: black;
    font-weight: bold;
    transition: 0.3s;
}

.btn-ver:hover {
    background: #00b1cc;
}

.notif-wrapper { position: relative; display: inline-block; }

.panel-notif {
    display: none;
    position: absolute;
    top: 36px;
    left: 50%;
    transform: translateX(-50%);
    width: 340px;
    background: white;
    color: #111;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.25);
    overflow: hidden;
    z-index: 200;
}

.panel-notif::before {
    content: "";
    position: absolute;
    top: -8px;
    left: 50%;
    transform: translateX(-50%);
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-bottom: 8px solid white;
}

.panel-notif.abierto { display: block; }

.panel-notif-header {
    background: #0a0a0a;
    color: #00eaff;
    padding: 12px 16px;
    font-weight: bold;
    font-size: 14px;
}

.panel-notif-item {
    padding: 12px 16px;
    border-bottom: 1px solid #eee;
    position: relative;
}

.panel-notif-item:last-child { border-bottom: none; }

.panel-notif-titulo {
    color: #00717a;
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 3px;
}

.panel-notif-mensaje {
    color: #555;
    font-size: 13px;
    margin-bottom: 5px;
}

.panel-notif-fecha {
    color: #000000;
    font-size: 11px;
}

.panel-notif-badge-nueva {
    display: inline-block;
    background: #00eaff;
    color: #003840;
    font-size: 10px;
    font-weight: bold;
    padding: 2px 8px;
    border-radius: 10px;
    margin-bottom: 4px;
}

.panel-notif-vacio {
    padding: 24px 16px;
    text-align: center;
    color: #888;
    font-size: 13px;
}

.panel-notif-vermas {
    display: block;
    text-align: center;
    padding: 12px;
    background: #f5f5f5;
    color: #00717a;
    font-weight: bold;
    font-size: 13px;
    text-decoration: none;
    border-top: 1px solid #eee;
}

.panel-notif-vermas:hover { background: #eaeaea; }

#panel-notif-lista {
    max-height: 400px; /* Limita la altura máxima de la lista */
    overflow-y: auto;  /* Agrega barra de desplazamiento interna si supera el tamaño */
}
</style>

</head>
<body>

<header>
    <div class="logo">CYBERCORE</div>
    <nav>
        <a href="home.php">Inicio</a>

        <?php if (isset($_SESSION['id_usuario'])): ?>
            <div class="notif-wrapper">
                <a href="javascript:void(0)" id="campana-notificaciones" style="position:relative;">
                    🔔
                    <span id="badge-notificaciones"
                        style="display:none; position:absolute; top:-6px; right:-10px; background:red;
                        color:white; border-radius:50%; padding:2px 6px; font-size:11px;">
                        0</span>
                </a>

                <div id="panel-notificaciones" class="panel-notif">
                    <div class="panel-notif-header">Notificaciones</div>
                    <div id="panel-notif-lista">
                        <p class="panel-notif-vacio">Cargando...</p>
                    </div>
                    <a href="views/notificaciones/historial.php" class="panel-notif-vermas">Ver más</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($rol == 'administrador' || $rol == 'empleado') { ?>
            <a href="inicio.php">Panel</a>

        <?php }elseif($rol == 'usuario' || $rol == 'cliente'){ ?>
            <a href="carrito/carrito.php">🛒 Carrito</a>
            <a class="logout" href="logout.php">Cerrar sesión</a>

        <?php }else{ ?>
            <a href="index.php">Iniciar sesión</a>
            <a href="registro.php">Registrarse</a>
        <?php } ?>
    </nav>
</header>

<!-- HERO con imagen y frase -->
<div class="hero">
    <div class="hero-overlay"></div>
    <div class="hero-text">
        <h1>Potenciá tu mundo gamer</h1>
        <p>Los mejores productos tecnológicos al alcance de tu mano</p>
        <a href="#productos" class="btn">Ver productos</a>
    </div>
</div>


<!-- BUSCADOR -->
<div class="search-container">
    <form method="GET">
        <input 
            type="text" 
            name="buscar" 
            placeholder="Buscar productos, marcas, categorías..."
            value="<?php echo htmlspecialchars($buscar); ?>">
        <button type="submit">Buscar</button>
    </form>
</div>


<!-- TÍTULO LISTA -->
<h2 class="products-title" id="productos">Productos destacados</h2>


<!-- PRODUCTOS -->
<div class="grid">

<?php foreach($productos as $p) { ?>
    <div class="card">

        <img src="img/<?php echo $p['imagen_url']; ?>" alt="">

        <h3><?php echo $p['nombre']; ?></h3>
        <small><?php echo $p['nombre_marca']; ?> • <?php echo $p['categoria']; ?></small>

        <div class="price">$<?php echo number_format($p['precio'], 2); ?></div>


        <!-- STOCK -->
<?php if ($p['stock'] == 0): ?>

    <p style="color:red; font-weight:bold; margin-top:8px;">
        ❌ SIN STOCK
    </p>
    <a class="btn-ver" style="background:#888; pointer-events:none; cursor:not-allowed;">
        No disponible
    </a>

<?php elseif ($p['stock'] <= 2): ?>

    <p style="color:#ff4444; font-weight:bold; margin-top:8px;">
        🔥 Últimas unidades disponibles
    </p>
    <a class="btn-ver" href="producto.php?id=<?php echo $p['id_producto']; ?>">
        Ver más
    </a>

<?php elseif ($p['stock'] <= 4): ?>

    <p style="color:orange; font-weight:bold; margin-top:8px;">
        ⚠️ Pocas unidades en stock
    </p>
    <a class="btn-ver" href="producto.php?id=<?php echo $p['id_producto']; ?>">
        Ver más
    </a>

<?php else: ?>

    <!-- Stock suficiente → no mostrar cantidad -->
    <a class="btn-ver" href="producto.php?id=<?php echo $p['id_producto']; ?>">
        Ver más
    </a>

<?php endif; ?>


    </div>
<?php } ?>


</div>


    <script src="https://js.pusher.com/8.4/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    const idUsuario = <?= json_encode($_SESSION['id_usuario'] ?? null) ?>;

    if (idUsuario) {
        const pusher = new Pusher('961fd9e4c7e0f7109ae5', { cluster: 'us2' });
        const canal = pusher.subscribe('canal-usuario-' + idUsuario);

        canal.bind('nueva-notificacion', function (data) {
            actualizarBadge();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: data.titulo,
                text: data.mensaje,
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true
            });
        });

        function actualizarBadge() {
            fetch('ajax/notificaciones.php?accion=listar')
                .then(res => res.json())
                .then(data => {
                    const badge = document.getElementById('badge-notificaciones');
                    if (!badge) return;
                    if (data.total > 0) {
                        badge.style.display = 'inline-block';
                        badge.textContent = data.total;
                    } else {
                        badge.style.display = 'none';
                    }
                });
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // --- Panel desplegable ---
        const campana = document.getElementById('campana-notificaciones');
        const panel = document.getElementById('panel-notificaciones');
        const lista = document.getElementById('panel-notif-lista');

        function formatearFecha(fechaStr) {
            const f = new Date(fechaStr.replace(' ', 'T'));
            return `${f.toLocaleDateString('es-AR')} ${f.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' })}`;
        }

        function renderPanel(notifs) {
            if (!notifs.length) {
                lista.innerHTML = '<p class="panel-notif-vacio">Todavía no tenés notificaciones.</p>';
                return;
            }
            lista.innerHTML = notifs.map(n => `
                <div class="panel-notif-item">
                    ${n.leida == 0 ? '<span class="panel-notif-badge-nueva">Nueva</span>' : ''}
                    <div class="panel-notif-titulo">${escapeHtml(n.titulo)}</div>
                    <div class="panel-notif-mensaje">${escapeHtml(n.mensaje)}</div>
                    <div class="panel-notif-fecha">${formatearFecha(n.fecha_creacion)}</div>
                </div>
            `).join('');
        }

        function abrirPanel() {
            fetch('ajax/notificaciones.php?accion=recientes&limite=5')
                .then(res => res.json())
                .then(data => {
                    if (!data.notificaciones) {
                        console.error('Respuesta inesperada de notificaciones.php:', data);
                        lista.innerHTML = '<p class="panel-notif-vacio">Error al cargar notificaciones.</p>';
                        panel.classList.add('abierto');
                        return;
                    }

                    renderPanel(data.notificaciones);
                    panel.classList.add('abierto');

                    const idsNoLeidas = data.notificaciones.filter(n => n.leida == 0).map(n => n.id_notificacion);
                    if (idsNoLeidas.length) {
                        const form = new URLSearchParams();
                        form.append('accion', 'marcar_vistas');
                        idsNoLeidas.forEach(id => form.append('ids[]', id));
                        fetch('ajax/notificaciones.php', { method: 'POST', body: form }).then(() => actualizarBadge());
                    }
                })
                .catch(err => console.error('Error al abrir el panel de notificaciones:', err));
        }


        campana.addEventListener('click', function (e) {
            e.stopPropagation();
            panel.classList.contains('abierto') ? panel.classList.remove('abierto') : abrirPanel();
        });

        document.addEventListener('click', function (e) {
            if (!panel.contains(e.target) && e.target !== campana) {
                panel.classList.remove('abierto');
            }
        });

        document.addEventListener('DOMContentLoaded', actualizarBadge);
    }
    </script>

</body>
</html>
