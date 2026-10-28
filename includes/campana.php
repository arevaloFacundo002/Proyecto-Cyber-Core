<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_usuario'])) {
    return;
}

require_once __DIR__ . '/../config/env.php';

$cc_raiz         = isset($ruta_raiz) ? (string) $ruta_raiz : '';
$cc_pusher_key   = env_valor('PUSHER_KEY');
$cc_pusher_clus  = env_valor('PUSHER_CLUSTER', 'us2');
$cc_id_usuario   = (int) $_SESSION['id_usuario'];
$cc_primera_vez  = !defined('CC_CAMPANA_CARGADA');
define('CC_CAMPANA_CARGADA', true);
?>
<?php if ($cc_primera_vez): ?>
<style>
.cc-campana { position: relative; display: inline-block; }
.cc-user .cc-campana { margin: 0 8px 0 6px; }
.cc-campana .cc-campana-btn {
    position: relative; background: transparent; border: 0; cursor: pointer;
    font-size: 18px; line-height: 1; padding: 6px 8px; margin: 0; color: inherit;
    border-radius: 8px;
}
.cc-campana .cc-campana-btn:hover { background: rgba(127,127,127,.18); }
.cc-campana .cc-campana-badge {
    position: absolute; top: -2px; right: -2px; min-width: 18px; height: 18px;
    padding: 0 5px; box-sizing: border-box; border-radius: 9px;
    background: var(--cc-peligro, #EF4444); color: #fff; font-size: 11px; font-weight: 700;
    line-height: 18px; text-align: center;
}
.cc-campana .cc-campana-badge[hidden] { display: none; }
.cc-campana .cc-campana-panel {
    position: absolute; right: 0; top: calc(100% + 10px); z-index: 1000;
    width: 340px; max-width: calc(100vw - 24px); overflow: hidden;
    background: var(--cc-superficie, #020C47); color: var(--cc-texto, #E8EDF2);
    border: 1px solid var(--cc-borde, #142759); border-radius: 12px;
    box-shadow: 0 12px 32px rgba(0,0,0,.35); text-align: left; font-weight: 400;
}
.cc-campana .cc-campana-panel[hidden] { display: none; }
.cc-campana .cc-campana-head {
    padding: 12px 16px; font-weight: 700; font-size: 14px;
    color: var(--cc-primario, #00EAFF); border-bottom: 1px solid var(--cc-borde, #142759);
}
.cc-campana .cc-campana-lista { max-height: 380px; overflow-y: auto; }
.cc-campana a.cc-campana-item {
    display: block; margin: 0; padding: 12px 16px; text-decoration: none; font-weight: 400;
    color: var(--cc-texto, #E8EDF2); border-bottom: 1px solid var(--cc-borde, #142759);
    border-radius: 0; background: transparent;
}
.cc-campana a.cc-campana-item:hover { background: var(--cc-superficie-2, #011F65); }
.cc-campana .cc-campana-item.nueva { border-left: 3px solid var(--cc-primario, #00EAFF); }
.cc-campana .cc-campana-titulo { font-weight: 700; font-size: 14px; margin-bottom: 3px; }
.cc-campana .cc-campana-msg    { font-size: 13px; color: var(--cc-texto-secundario, #7C8DB5); margin-bottom: 4px; }
.cc-campana .cc-campana-fecha  { font-size: 11px; color: var(--cc-texto-secundario, #7C8DB5); }
.cc-campana .cc-campana-vacio  { padding: 22px 16px; margin: 0; text-align: center; font-size: 13px; color: var(--cc-texto-secundario, #7C8DB5); }
.cc-campana a.cc-campana-mas {
    display: block; margin: 0; padding: 11px; text-align: center; text-decoration: none;
    font-weight: 700; font-size: 13px; color: var(--cc-primario, #00EAFF);
    background: var(--cc-superficie-2, #011F65); border-radius: 0;
}
.cc-toast {
    position: fixed; top: 16px; right: 16px; z-index: 5000; width: 300px; max-width: calc(100vw - 32px);
    background: var(--cc-superficie, #020C47); color: var(--cc-texto, #E8EDF2);
    border: 1px solid var(--cc-primario, #00EAFF); border-radius: 10px; padding: 12px 14px;
    box-shadow: 0 10px 28px rgba(0,0,0,.4); font-family: inherit; font-size: 13px;
}
.cc-toast b { display: block; margin-bottom: 3px; font-size: 14px; color: var(--cc-primario, #00EAFF); }
</style>
<?php endif; ?>

<div class="cc-campana" id="cc-campana">
    <button type="button" class="cc-campana-btn" id="cc-campana-btn" aria-label="Notificaciones" title="Notificaciones">
        🔔<span class="cc-campana-badge" id="cc-campana-badge" hidden>0</span>
    </button>
    <div class="cc-campana-panel" id="cc-campana-panel" hidden>
        <div class="cc-campana-head">Notificaciones</div>
        <div class="cc-campana-lista" id="cc-campana-lista"><p class="cc-campana-vacio">Cargando...</p></div>
        <a class="cc-campana-mas" href="<?= htmlspecialchars($cc_raiz) ?>views/notificaciones/historial.php">Ver todas</a>
    </div>
</div>

<?php if ($cc_primera_vez): ?>
<script>
(function () {
    var RAIZ = <?= json_encode($cc_raiz) ?>;
    var ID_USUARIO = <?= json_encode($cc_id_usuario) ?>;
    var PUSHER_KEY = <?= json_encode($cc_pusher_key) ?>;
    var PUSHER_CLUSTER = <?= json_encode($cc_pusher_clus) ?>;
    var AJAX = RAIZ + 'ajax/notificaciones.php';

    var btn = document.getElementById('cc-campana-btn');
    var badge = document.getElementById('cc-campana-badge');
    var panel = document.getElementById('cc-campana-panel');
    var lista = document.getElementById('cc-campana-lista');
    if (!btn || !badge || !panel || !lista) return;

    function esc(t) {
        var d = document.createElement('div');
        d.textContent = t == null ? '' : String(t);
        return d.innerHTML;
    }

    function destino(u) {
        if (!u) return '#';
        if (/^(https?:)?\/\//.test(u) || u.charAt(0) === '/') return u;
        return RAIZ + u.replace(/^(\.\.\/)+/, '');
    }

    function fecha(s) {
        if (!s) return '';
        var f = new Date(String(s).replace(' ', 'T'));
        if (isNaN(f)) return '';
        return f.toLocaleDateString('es-AR') + ' ' + f.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
    }

    function pintarBadge(total) {
        total = parseInt(total, 10) || 0;
        badge.hidden = total <= 0;
        badge.textContent = total > 99 ? '99+' : total;
    }

    function actualizarBadge() {
        fetch(AJAX + '?accion=listar', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) { pintarBadge(d.total); })
            .catch(function () {});
    }

    function pintarLista(items) {
        if (!items || !items.length) {
            lista.innerHTML = '<p class="cc-campana-vacio">Todavía no tenés notificaciones.</p>';
            return;
        }
        lista.innerHTML = items.map(function (n) {
            return '<a class="cc-campana-item' + (String(n.leida) === '0' ? ' nueva' : '') + '" href="' + esc(destino(n.url_accion)) + '">' +
                '<div class="cc-campana-titulo">' + esc(n.titulo) + '</div>' +
                '<div class="cc-campana-msg">' + esc(n.mensaje) + '</div>' +
                '<div class="cc-campana-fecha">' + esc(fecha(n.fecha_creacion)) + '</div></a>';
        }).join('');
    }

    function abrir() {
        fetch(AJAX + '?accion=recientes&limite=6', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                pintarLista(d.notificaciones || []);
                panel.hidden = false;

                var ids = (d.notificaciones || []).filter(function (n) { return String(n.leida) === '0'; })
                                                   .map(function (n) { return n.id_notificacion; });
                if (ids.length) {
                    var f = new URLSearchParams();
                    f.append('accion', 'marcar_vistas');
                    ids.forEach(function (id) { f.append('ids[]', id); });
                    fetch(AJAX, { method: 'POST', body: f, credentials: 'same-origin' }).then(actualizarBadge);
                }
            })
            .catch(function () {
                lista.innerHTML = '<p class="cc-campana-vacio">No se pudieron cargar las notificaciones.</p>';
                panel.hidden = false;
            });
    }

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        if (panel.hidden) { abrir(); } else { panel.hidden = true; }
    });
    document.addEventListener('click', function (e) {
        if (!panel.hidden && !panel.contains(e.target)) panel.hidden = true;
    });

    function aviso(titulo, mensaje) {
        var t = document.createElement('div');
        t.className = 'cc-toast';
        t.innerHTML = '<b>' + esc(titulo) + '</b>' + esc(mensaje);
        document.body.appendChild(t);
        setTimeout(function () { t.remove(); }, 5000);
    }

    actualizarBadge();

    if (PUSHER_KEY) {
        var s = document.createElement('script');
        s.src = 'https://js.pusher.com/8.4/pusher.min.js';
        s.onload = function () {
            try {
                var pusher = new Pusher(PUSHER_KEY, { cluster: PUSHER_CLUSTER });
                pusher.subscribe('canal-usuario-' + ID_USUARIO).bind('nueva-notificacion', function (d) {
                    actualizarBadge();
                    aviso(d.titulo, d.mensaje);
                });
            } catch (err) { setInterval(actualizarBadge, 30000); }
        };
        s.onerror = function () { setInterval(actualizarBadge, 30000); };
        document.head.appendChild(s);
    } else {
        // Sin Pusher configurado: se revisa cada 30 segundos
        setInterval(actualizarBadge, 30000);
    }
})();
</script>
<?php endif; ?>