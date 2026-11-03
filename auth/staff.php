<?php
// Deja pasar solo a administrador o empleado (las pantallas de carga de stock).
require_once __DIR__ . "/auth.php";
 
if (!in_array($_SESSION['rol'] ?? '', ['administrador', 'empleado'], true)) {
    header("Location: /home.php");
    exit();
}