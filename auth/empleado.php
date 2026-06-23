<?php
require_once "auth.php";

if ($_SESSION['rol'] != "empleado") {
    header("Location: /home.php");
    exit();
}