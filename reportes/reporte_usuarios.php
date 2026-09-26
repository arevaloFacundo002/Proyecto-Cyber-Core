 <?php

require_once '../auth/auth.php';

$desde = isset($_GET["desde"]) ? $_GET["desde"] : null;

// Si NO se enviaron fechas → mostrar formulario y salir
if (!isset($_GET["desde"]) || !isset($_GET["hasta"])) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Generar Reporte de Usuarios</title>

<link href="../css/theme.css" rel="stylesheet">

<style>
    body {
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
    }

    .volver {
        position: fixed;
        top: 22px;
        left: 22px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 18px;
        border-radius: 20px;
        background: var(--cc-superficie);
        border: 1px solid var(--cc-borde);
        color: var(--cc-texto);
        text-decoration: none;
        font-weight: bold;
        transition: 0.2s;
    }

    .volver:hover {
        border-color: var(--cc-primario);
        color: var(--cc-primario);
    }

    .container {
        max-width: 550px;
        width: 90%;
        margin: 40px auto;
        background: var(--cc-superficie);
        padding: 40px;
        border-radius: 18px;
        box-shadow: 0 0 20px rgba(0, 234, 255, 0.15);
        border: 1px solid var(--cc-borde);
        text-align: center;
    }

    h1 {
        margin-bottom: 25px;
        font-size: 26px;
        color: var(--cc-primario);
        text-shadow: 0 0 8px rgba(0, 234, 255, 0.4);
    }

    label {
        display: block;
        text-align: left;
        margin-top: 18px;
        font-weight: bold;
        color: var(--cc-texto);
    }

    input[type="date"] {
        width: 100%;
        box-sizing: border-box;
        padding: 12px;
        border-radius: 10px;
        border: 1px solid var(--cc-borde);
        background: var(--cc-fondo);
        color: var(--cc-texto);
        margin-top: 6px;
        font-size: 15px;
        cursor: pointer;
    }

    input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(80%) sepia(50%) saturate(200%) hue-rotate(160deg);
        cursor: pointer;
    }

    input[type="date"]:focus {
        outline: none;
        border-color: var(--cc-primario);
        box-shadow: 0 0 6px rgba(0, 234, 255, 0.5);
    }

    button {
        margin-top: 30px;
        width: 100%;
        padding: 15px;
        font-size: 17px;
        background: var(--cc-primario);
        border: none;
        border-radius: 12px;
        font-weight: bold;
        cursor: pointer;
        color: var(--cc-texto-sobre-primario);
        transition: 0.25s;
    }

    button:hover {
        background: var(--cc-primario-hover);
        box-shadow: 0 0 10px rgba(0, 234, 255, 0.5);
    }
</style>

</head>
<body>

<a href="../inicio.php" class="volver">← Volver a Módulos</a>

<div class="container">
    <h1>📊 Reporte de Usuarios y Clientes</h1>

    <form method="GET" action="reporte_usuarios.php" target="_blank">

        <label>Fecha desde:</label>
        <input type="date" name="desde" required>

        <label>Fecha hasta:</label>
        <input type="date" name="hasta" required>

        <button type="submit">📄 Generar Reporte / Descargar PDF</button>
    </form>
</div>

<script src="../js/theme-toggle.js"></script>

</body>
</html>

<?php
exit; // Detener ejecución para no generar PDF sin fechas
}
?>


<?php
require "../conexion.php";
require __DIR__ . "/FPDF/fpdf.php";

// ========= VALIDACIÓN =========
$desde = $_GET["desde"] ?? null;
$hasta = $_GET["hasta"] ?? null;

if (!$desde || !$hasta) {
    die("Error: Fechas no válidas.");
}

// =====================================================
//                CONSULTAS PRINCIPALES
// =====================================================
$usuarios_total      = $conexion->query("SELECT COUNT(*) AS t FROM usuarios")->fetch_assoc()['t'];
$clientes_total      = $conexion->query("SELECT COUNT(*) AS t FROM clientes")->fetch_assoc()['t'];

$usuarios_periodo = $conexion->query("
    SELECT COUNT(*) AS t 
    FROM usuarios
    WHERE DATE(fecha_registro) BETWEEN '$desde' AND '$hasta'
")->fetch_assoc()['t'];

$clientes_periodo = $conexion->query("
    SELECT COUNT(*) AS t 
    FROM clientes
    WHERE DATE(fecha_registro) BETWEEN '$desde' AND '$hasta'
")->fetch_assoc()['t'];

// usuarios → clientes
$usuarios_con_cliente = $conexion->query("
    SELECT COUNT(*) AS t 
    FROM usuarios 
    WHERE id_usuario IN (SELECT rela_id_usuario FROM clientes)
")->fetch_assoc()['t'];

$usuarios_sin_cliente = $usuarios_total - $usuarios_con_cliente;

// Roles (usuarios.rela_id_perfil -> perfiles.nombre_perfil)
$roles = [];
$q = $conexion->query("
    SELECT p.nombre_perfil AS rol, COUNT(*) AS c
    FROM usuarios u
    INNER JOIN perfiles p ON p.id_perfil = u.rela_id_perfil
    GROUP BY p.nombre_perfil
");
while ($r = $q->fetch_assoc()) $roles[$r['rol']] = $r['c'];

// Usuarios por estado (usuarios.estado: activo / inactivo / bloqueado)
$estados = [];
$q = $conexion->query("SELECT estado, COUNT(*) AS c FROM usuarios GROUP BY estado");
while ($e = $q->fetch_assoc()) $estados[$e['estado']] = $e['c'];

// Top 5 provincias con clientes
// clientes -> direcciones (rela_id_direccion) -> localidades (rela_id_localidad) -> provincias (rela_id_provincia)
$top_provincias = [];
$q = $conexion->query("
    SELECT p.nombre_provincia AS provincia, COUNT(*) AS total
    FROM clientes c
    INNER JOIN direcciones d ON d.id_direccion = c.rela_id_direccion
    INNER JOIN localidades l ON l.id_localidad = d.rela_id_localidad
    INNER JOIN provincias p ON p.id_provincia = l.rela_id_provincia
    GROUP BY p.id_provincia
    ORDER BY total DESC
    LIMIT 5
");
while ($t = $q->fetch_assoc()) $top_provincias[$t['provincia']] = $t['total'];

// clientes por mes
$clientes_mes = [];
$q = $conexion->query("
    SELECT DATE_FORMAT(fecha_registro, '%Y-%m') AS mes, COUNT(*) AS total
    FROM clientes
    GROUP BY mes
    ORDER BY mes ASC
");
while ($m = $q->fetch_assoc()) $clientes_mes[$m['mes']] = $m['total'];


// =====================================================
//                 GENERADOR DE GRÁFICOS
// =====================================================
function crearGrafico($datos, $titulo, $outfile) {

    $w = 650; $h = 350;
    $img = imagecreatetruecolor($w, $h);

    // colores CyberCore
    $blanco = imagecolorallocate($img, 255,255,255);
    $negro  = imagecolorallocate($img, 0,0,0);
    $celeste = imagecolorallocate($img, 0,189,255);

    imagefill($img, 0,0, $blanco);

    imagestring($img, 5, 10, 10, utf8_decode($titulo), $negro);

    if (!count($datos)) {
        imagestring($img, 5, 20, 50, "No hay datos suficientes.", $negro);
        imagepng($img, $outfile);
        return;
    }

    $maxVal = max($datos);
    $x = 60;
    $base = 300;

    foreach ($datos as $label => $value) {
        $barH = ($value / $maxVal) * 220;

        imagefilledrectangle($img, $x, $base - $barH, $x + 80, $base, $celeste);

        imagestring($img, 4, $x + 10, $base + 5, utf8_decode(substr($label,0,10)), $negro);
        imagestring($img, 5, $x + 30, $base - $barH - 15, $value, $negro);

        $x += 130;
    }

    imagepng($img, $outfile);
    imagedestroy($img);
}

$chart_roles      = __DIR__ . "/roles.png";
$chart_estados    = __DIR__ . "/estados.png";
$chart_topprov    = __DIR__ . "/provincias.png";
$chart_clientemes = __DIR__ . "/clientes_mes.png";

crearGrafico($roles, "Usuarios por Rol", $chart_roles);
crearGrafico($estados, "Usuarios por Estado", $chart_estados);
crearGrafico($top_provincias, "Top 5 Provincias con Clientes", $chart_topprov);
crearGrafico($clientes_mes, "Crecimiento de Clientes por Mes", $chart_clientemes);


// =====================================================
//                      PDF PREMIUM
// =====================================================
class PDF extends FPDF {

    function Header() {
        $this->SetFont("Arial","B",16);
        $this->SetTextColor(0,189,255);
        $this->Cell(0,12,"Reporte General de Usuarios & Clientes",0,1,"C");
        $this->Ln(3);
    }

    function Footer() {
        // SIN PIE
    }

    function titulo($txt) {
        $this->Ln(4);
        $this->SetFont("Arial","B",14);
        $this->SetTextColor(0,0,0);
        $this->Cell(0,10, utf8_decode($txt), 0,1);
    }

    function dato($txt) {
        $this->SetFont("Arial","",12);
        $this->SetTextColor(80,80,80);
        $this->Cell(0,8, utf8_decode("• $txt"), 0,1);
    }
}


// ===================
//     CREAR PDF
// ===================
$pdf = new PDF();
$pdf->AddPage();

// periodo
$pdf->SetFont("Arial","",12);
$pdf->SetTextColor(0,0,0);
$pdf->Cell(0,10,"Periodo seleccionado: $desde → $hasta",0,1);

// resumen GENERAL
$pdf->titulo("Resumen General");
$pdf->dato("Total usuarios (historico): $usuarios_total");
$pdf->dato("Usuarios en periodo: $usuarios_periodo");
$pdf->dato("Total clientes (historico): $clientes_total");
$pdf->dato("Clientes en periodo: $clientes_periodo");
$pdf->dato("Usuarios con cliente: $usuarios_con_cliente");
$pdf->dato("Usuarios sin cliente: $usuarios_sin_cliente");

// Gráfico roles
$pdf->titulo("Usuarios por Rol");
$pdf->Image($chart_roles, 15, $pdf->GetY(), 180);
$pdf->Ln(120);

// Gráfico estados
$pdf->titulo("Usuarios por Estado");
$pdf->Image($chart_estados, 15, $pdf->GetY(), 180);
$pdf->Ln(120);

// Top Provincias
$pdf->titulo("Top 5 Provincias con Clientes");
$pdf->Image($chart_topprov, 15, $pdf->GetY(), 180);
$pdf->Ln(120);

// clientes por mes
$pdf->titulo("Crecimiento de Clientes por Mes");
$pdf->Image($chart_clientemes, 15, $pdf->GetY(), 180);
$pdf->Ln(120);

$pdf->Output();

// borrar imágenes
unlink($chart_roles);
unlink($chart_estados);
unlink($chart_topprov);
unlink($chart_clientemes);

?>