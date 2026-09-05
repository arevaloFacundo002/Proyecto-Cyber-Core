<?php
require_once "auth.php";

if ($_SESSION['rol'] != "administrador") {
    header("Location:  /home.php");
    exit();
}