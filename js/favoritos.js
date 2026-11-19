document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('.btn-fav');
    if (!btn) return;
    ev.preventDefault();

    if (btn.dataset.login === '0') {
        window.location.href = btn.dataset.loginUrl || 'login.php';
        return;
    }

    var datos = new FormData();
    datos.append('id_producto', btn.dataset.id);
    datos.append('csrf', btn.dataset.csrf);

    fetch(btn.dataset.url, { method: 'POST', body: datos, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (r) {
            if (!r.ok) { return; }
            btn.classList.toggle('activo', r.favorito);
            btn.setAttribute('aria-pressed', r.favorito ? 'true' : 'false');
            btn.title = r.favorito ? 'Quitar de favoritos' : 'Agregar a favoritos';

            var contador = document.getElementById('favCuenta');
            if (contador) { contador.textContent = r.total; }

            if (!r.favorito && btn.dataset.quitar === '1') {
                var tarjeta = btn.closest('.fav-card');
                if (tarjeta) { tarjeta.remove(); }
                if (!document.querySelector('.fav-card')) { window.location.reload(); }
            }
        });
});