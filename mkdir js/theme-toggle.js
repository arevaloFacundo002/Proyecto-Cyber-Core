 (function () {
    "use strict";

    var CLAVE = "cc-theme";

    function obtenerTemaGuardado() {
        try {
            return localStorage.getItem(CLAVE);
        } catch (e) {
            return null;
        }
    }

    function guardarTema(tema) {
        try {
            localStorage.setItem(CLAVE, tema);
        } catch (e) {
            // Si el navegador bloquea localStorage, seguimos sin guardar.
        }
    }

    function aplicarTema(tema) {
        document.documentElement.setAttribute("data-theme", tema);

        var boton = document.getElementById("cc-theme-toggle");
        if (boton) {
            boton.innerHTML = tema === "dark" ? "&#9789;" : "&#9788;";
            boton.title = tema === "dark"
                ? "Cambiar a modo claro"
                : "Cambiar a modo oscuro";
        }
    }

    function alternarTema() {
        var actual = document.documentElement.getAttribute("data-theme");
        var nuevo = actual === "dark" ? "light" : "dark";
        guardarTema(nuevo);
        aplicarTema(nuevo);
    }

    function crearBoton() {
        var boton = document.createElement("button");
        boton.id = "cc-theme-toggle";
        boton.type = "button";
        boton.setAttribute("aria-label", "Cambiar entre modo claro y oscuro");
        boton.addEventListener("click", alternarTema);
        document.body.appendChild(boton);
    }

    // Tema inicial: el guardado, o "dark" por defecto.
    var temaInicial = obtenerTemaGuardado() || "dark";
    aplicarTema(temaInicial);

    document.addEventListener("DOMContentLoaded", function () {
        crearBoton();
        aplicarTema(document.documentElement.getAttribute("data-theme") || temaInicial);
    });
})();