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
    }

    /* Franja con el botón de volver, debajo del header (igual que en los otros módulos) */
    .subheader {
        background: var(--cc-superficie-2);
        padding: 16px 50px;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        border-bottom: 1px solid var(--cc-borde);
    }

    .subheader a {
        background: var(--cc-primario);
        padding: 10px 18px;
        border-radius: 20px;
        color: var(--cc-texto-sobre-primario);
        font-weight: bold;
        text-decoration: none;
        transition: 0.2s;
    }

    .subheader a:hover {
        background: var(--cc-primario-hover);
    }

    /* Ahora el centrado lo hace este contenedor, no el body */
    .centro {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 70vh;
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

    .container button {
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

    .container button:hover {
        background: var(--cc-primario-hover);
        box-shadow: 0 0 10px rgba(0, 234, 255, 0.5);
    }
</style>

</head>
<body>

<?php
$ruta_raiz = '../';
require __DIR__ . '/../includes/header_admin.php';
?>


<div class="subheader">
    <a href="../consultas/menu_consultas.php">← Volver a Consultas y Reportes</a>
</div>

<div class="centro">
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
require_once __DIR__ . "/ReportePDF.php";

$desde = $_GET["desde"] ?? '';
$hasta = $_GET["hasta"] ?? '';

$fechaValida = function ($f) {
    $d = DateTime::createFromFormat('Y-m-d', $f);
    return $d && $d->format('Y-m-d') === $f;
};

if (!$fechaValida($desde) || !$fechaValida($hasta) || $desde > $hasta) {
    http_response_code(400);
    die("Las fechas no son válidas. Volvé atrás y elegí un período correcto.");
}

function escalar(mysqli $c, string $sql, string $tipos = '', array $params = [])
{
    $st = $c->prepare($sql);
    if ($tipos !== '') {
        $st->bind_param($tipos, ...$params);
    }
    $st->execute();
    $fila = $st->get_result()->fetch_row();
    return $fila ? (int) $fila[0] : 0;
}

function pares(mysqli $c, string $sql, string $tipos = '', array $params = []): array
{
    $st = $c->prepare($sql);
    if ($tipos !== '') {
        $st->bind_param($tipos, ...$params);
    }
    $st->execute();
    $res = $st->get_result();
    $out = [];
    while ($f = $res->fetch_row()) {
        $out[(string) $f[0]] = (int) $f[1];
    }
    return $out;
}

$usuarios_total   = escalar($conexion, "SELECT COUNT(*) FROM usuarios");
$clientes_total   = escalar($conexion, "SELECT COUNT(*) FROM clientes");
$usuarios_periodo = escalar($conexion, "SELECT COUNT(*) FROM usuarios WHERE DATE(fecha_registro) BETWEEN ? AND ?", "ss", [$desde, $hasta]);
$clientes_periodo = escalar($conexion, "SELECT COUNT(*) FROM clientes WHERE DATE(fecha_registro) BETWEEN ? AND ?", "ss", [$desde, $hasta]);
$usuarios_con_cliente = escalar($conexion, "SELECT COUNT(*) FROM usuarios WHERE id_usuario IN (SELECT rela_id_usuario FROM clientes)");
$usuarios_sin_cliente = $usuarios_total - $usuarios_con_cliente;

$roles = pares($conexion, "
    SELECT p.nombre_perfil, COUNT(*)
    FROM usuarios u
    INNER JOIN perfiles p ON p.id_perfil = u.rela_id_perfil
    GROUP BY p.id_perfil, p.nombre_perfil
    ORDER BY COUNT(*) DESC");

$estados = pares($conexion, "SELECT estado, COUNT(*) FROM usuarios GROUP BY estado ORDER BY COUNT(*) DESC");

$provincias = pares($conexion, "
    SELECT p.nombre_provincia, COUNT(*) AS total
    FROM clientes c
    INNER JOIN direcciones d ON d.id_direccion = c.rela_id_direccion
    INNER JOIN localidades l ON l.id_localidad = d.rela_id_localidad
    INNER JOIN provincias p ON p.id_provincia = l.rela_id_provincia
    GROUP BY p.id_provincia, p.nombre_provincia
    ORDER BY total DESC, p.nombre_provincia ASC
    LIMIT 5");

$meses_raw = pares($conexion, "
    SELECT DATE_FORMAT(fecha_registro, '%Y-%m') AS mes, COUNT(*)
    FROM clientes
    WHERE DATE(fecha_registro) <= ?
    GROUP BY mes
    ORDER BY mes DESC
    LIMIT 12", "s", [$hasta]);
$clientes_mes = [];
for ($i = 11; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime($hasta . ' first day of -' . $i . ' month'));
    $clientes_mes[substr($mes, 5, 2) . '/' . substr($mes, 2, 2)] = $meses_raw[$mes] ?? 0;
}

$roles   = array_combine(array_map('ucfirst', array_keys($roles)), $roles);
$estados = array_combine(array_map('ucfirst', array_keys($estados)), $estados);

$rolesTabla = [];
foreach ($roles as $rol => $cant) {
    $pct = $usuarios_total > 0 ? number_format($cant / $usuarios_total * 100, 1, ',', '.') . '%' : '0%';
    $rolesTabla[] = [$rol, $cant, $pct];
}

$f = fn($x) => date('d/m/Y', strtotime($x));

$pdf = new ReportePDF("Reporte de usuarios y clientes");
$pdf->portada("Reporte de usuarios y clientes", "Período: " . $f($desde) . " al " . $f($hasta));

$pdf->seccion("Resumen general");
$pdf->kpis([
    ["Usuarios totales", $usuarios_total],
    ["Usuarios nuevos en el período", $usuarios_periodo],
    ["Clientes totales", $clientes_total],
    ["Clientes nuevos en el período", $clientes_periodo],
    ["Usuarios con cliente", $usuarios_con_cliente],
    ["Usuarios sin cliente", $usuarios_sin_cliente],
], 3);

$pdf->seccion("Usuarios por rol");
$pdf->torta($roles);
$pdf->tabla(["Rol", "Usuarios", "Porcentaje"], $rolesTabla, [90, 45, 45], ['L', 'R', 'R']);

$pdf->seccion("Usuarios por estado");
$pdf->barras($estados);

$pdf->seccion("Provincias con más clientes");
$pdf->nota("Las 5 provincias donde más clientes tienen registrada su dirección.");
$pdf->barras($provincias);

$pdf->seccion("Clientes registrados por mes");
$pdf->nota("Últimos 12 meses hasta la fecha final del período.");
$pdf->lineas($clientes_mes);

$pdf->Output('I', 'reporte_usuarios_' . $desde . '_' . $hasta . '.pdf');