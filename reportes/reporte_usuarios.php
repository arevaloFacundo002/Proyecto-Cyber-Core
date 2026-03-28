

<?php
$desde = isset($_GET["desde"]) ? $_GET["desde"] : null;
// Si NO se enviaron fechas → mostrar formulario y salir
if (!isset($_GET["desde"]) || !isset($_GET["hasta"])) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Generar Reporte de Usuarios</title>

<style>
    body {
        margin: 0;
        padding: 0;
        background: #0a0a0a;
        font-family: 'Segoe UI', sans-serif;
        color: white;
    }

    .container {
        max-width: 550px;
        margin: 80px auto;
        background: #111;
        padding: 40px;
        border-radius: 18px;
        box-shadow: 0 0 20px rgba(0,189,255,0.25);
        border: 1px solid #00bdff33;
        text-align: center;
    }

    h1 {
        margin-bottom: 25px;
        color: #00bdff;
        text-shadow: 0 0 8px #00bdff;
    }

    label {
        display: block;
        text-align: left;
        margin-top: 18px;
        font-weight: bold;
        color: #ccefff;
    }

    input[type="date"] {
        width: 100%;
        padding: 12px;
        border-radius: 10px;
        border: 1px solid #00bdff55;
        background: #0d0d0d;
        color: white;
        margin-top: 6px;
        font-size: 15px;
        cursor: pointer;
    }

    /* Icono de calendario personalizado */
    input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(80%) sepia(50%) saturate(200%) hue-rotate(160deg);
        cursor: pointer;
    }

    input[type="date"]:focus {
        outline: none;
        border-color: #00bdff;
        box-shadow: 0 0 6px #00bdff;
    }

    button {
        margin-top: 30px;
        width: 100%;
        padding: 15px;
        font-size: 17px;
        background: #00bdff;
        border: none;
        border-radius: 12px;
        font-weight: bold;
        cursor: pointer;
        color: #000;
        transition: 0.25s;
    }

    button:hover {
        background: #009ac7;
        box-shadow: 0 0 10px #00bdff;
    }
</style>

</head>
<body>

<div class="container">
    <h1>Reporte de Usuarios y Clientes</h1>

    <form method="GET" action="reporte_usuarios.php" target="_blank">

        <label>Fecha desde:</label>
        <input type="date" name="desde" required>

        <label>Fecha hasta:</label>
        <input type="date" name="hasta" required>

        <button type="submit">📄 Generar Reporte / Descargar PDF</button>
    </form>
</div>

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

// Roles
$roles = [];
$q = $conexion->query("SELECT tipo_usuario, COUNT(*) AS c FROM usuarios GROUP BY tipo_usuario");
while ($r = $q->fetch_assoc()) $roles[$r['tipo_usuario']] = $r['c'];

// Estados cliente
$estados = [];
$q = $conexion->query("SELECT cliente_estado, COUNT(*) AS c FROM clientes GROUP BY cliente_estado");
while ($e = $q->fetch_assoc()) $estados[$e['cliente_estado']] = $e['c'];

// Top 5 provincias con clientes
$top_provincias = [];
$q = $conexion->query("
    SELECT p.nombre_provincia AS provincia, COUNT(*) AS total
    FROM clientes c
    JOIN localidades l ON l.id_localidades = c.rela_id_localidades
    JOIN provincias p ON p.id_provincias = l.rela_id_provincias
    GROUP BY p.id_provincias
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
crearGrafico($estados, "Clientes por Estado", $chart_estados);
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
$pdf->titulo("Clientes por Estado");
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
