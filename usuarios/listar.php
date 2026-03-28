<?php
session_start();
include "../conexion.php";

// Si no hay sesión → fuera
if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit;
}

// 1. Búsqueda y filtro
$busqueda = isset($_GET['buscar']) ? $_GET['buscar'] : "";
$estado   = isset($_GET['estado']) ? $_GET['estado'] : "";

// Consulta base
$sql = "SELECT 
            u.*, 
            c.id_cliente, 
            c.cliente_estado
        FROM usuarios u
        LEFT JOIN clientes c ON c.rela_id_usuario = u.id_usuario
        WHERE (u.nombre LIKE ? OR u.correo LIKE ? OR u.tipo_usuario LIKE ?)";

// Filtros opcionales
if ($estado == "no-cliente") {
    $sql .= " AND c.id_cliente IS NULL";
} elseif ($estado != "") {
    $allowed = ['activo','pausado','inactivo','bloqueado'];
    if (in_array($estado, $allowed, true)) {
        $sql .= " AND c.cliente_estado = '$estado'";
    }
}

$sql .= " ORDER BY u.id_usuario DESC";

// Preparar consulta
$stmt = mysqli_prepare($conexion, $sql);
$param = "%$busqueda%";
mysqli_stmt_bind_param($stmt, "sss", $param, $param, $param);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>ABM Usuarios</title>

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: #f4f4f4;
}

/* HEADER */
header {
    background: #0a0a0a;
    padding: 18px 40px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

header a {
    color: #00eaff;
    text-decoration: none;
    font-weight: bold;
}

/* TITULO */
h2 {
    text-align: center;
    margin-top: 25px;
    font-size: 28px;
    color: #222;
}

/* BUSCADOR */
.search-box {
    text-align: center;
    margin-top: 20px;
}

.search-box input,
.search-box select {
    padding: 12px;
    border-radius: 20px;
    border: 1px solid #aaa;
}

.search-box input {
    width: 300px;
}

.search-box button {
    padding: 12px 18px;
    border: none;
    border-radius: 20px;
    background: #00eaff;
    font-weight: bold;
    cursor: pointer;
}

.search-box button:hover {
    background: #0099bb;
}

/* TABLA */
table {
    width: 90%;
    margin: 30px auto;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 0 10px rgba(0,0,0,0.15);
}

th {
    background: #00b1cc;
    color: white;
    padding: 12px;
    text-transform: uppercase;
}

td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid #eee;
}

tr:hover {
    background: #f5ffff;
}

/* BOTONES */
.btn {
    padding: 6px 12px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: bold;
    background: #00eaff;
    color: black;
    transition: 0.3s;
    display: inline-block;
}

.btn:hover {
    background: #009ebd;
}

.btn-red {
    background: #ff4d4d;
    color: white;
}

.btn-red:hover {
    background: #c83737;
}

.btn-client {
    background: #ffd54d;
    color: black;
}

.btn-client:hover {
    background: #ffca28;
}

/* Acciones ordenadas */
.actions {
    display: flex;
    justify-content: center;
    gap: 8px;
}

/* Agregar usuario */
.add-user-box {
    text-align: center;
    margin-bottom: 25px;
}

.add-user-box a {
    background: #00eaff;
    padding: 10px 20px;
    border-radius: 20px;
    color: black;
    font-weight: bold;
    text-decoration: none;
}

.add-user-box a:hover {
    background: #0099bb;
}

.link-cliente {
    color: blue;
    font-weight: bold;
    text-decoration: underline;
}
</style>
</head>
<body>

<header>
    <div><strong>CyberCore - Panel Admin</strong></div>

    <div>
        <a href="../inicio.php">⬅ Volver al Panel</a>
        &nbsp; | &nbsp;
        <a href="../logout.php">Cerrar sesión</a>
    </div>
</header>

<h2>Gestión de Usuarios</h2>

<div class="search-box">
    <form method="GET" style="display:flex; justify-content:center; gap:10px;">

        <input type="text" name="buscar" placeholder="Buscar usuario..."
               value="<?= htmlspecialchars($busqueda) ?>">

        <select name="estado">
            <option value="">Estado Cliente (Todos)</option>
            <option value="activo"    <?= $estado=="activo"?"selected":"" ?>>Activo</option>
            <option value="pausado"   <?= $estado=="pausado"?"selected":"" ?>>Pausado</option>
            <option value="inactivo"  <?= $estado=="inactivo"?"selected":"" ?>>Inactivo</option>
            <option value="bloqueado" <?= $estado=="bloqueado"?"selected":"" ?>>Bloqueado</option>
            <option value="no-cliente" <?= $estado=="no-cliente"?"selected":"" ?>>Solo No Clientes</option>
        </select>

        <button type="submit">Buscar</button>
    </form>
</div>

<table>
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Correo</th>
        <th>Rol</th>
        <th>Fecha Registro</th>
        <th>Cliente</th>
        <th>Acciones</th>
    </tr>

<?php while ($fila = mysqli_fetch_assoc($result)) { 
    $uid = (int)$fila['id_usuario'];
    $uname = htmlspecialchars($fila['nombre']);
    $uemail = htmlspecialchars($fila['correo']);
    $urole = htmlspecialchars($fila['tipo_usuario']);
    $ureg = htmlspecialchars($fila['fecha_registro']);
    $cid = $fila['id_cliente'] ?? null;
    $cestado = $fila['cliente_estado'] ?? null;
?>
    <tr>
        <td><?= $uid ?></td>
        <td><?= $uname ?></td>
        <td><?= $uemail ?></td>
        <td><?= $urole ?></td>
        <td><?= $ureg ?></td>

        <td>
            <?php if ($cid !== null): 
                $color_map = [
                    'activo' => 'green',
                    'pausado' => 'orange',
                    'inactivo' => 'gray',
                    'bloqueado' => 'red'
                ];
                $color = $color_map[$cestado] ?? 'black';
            ?>
                <strong style="color:<?= $color ?>">
                    Cliente ID: <?= $cid ?> — <?= ucfirst($cestado) ?>
                </strong>
            <?php else: ?>
                <a class="link-cliente" href="../clientes/crear_cliente.php?u=<?= $uid ?>">Crear cliente</a>
            <?php endif; ?>
        </td>

        <td>
            <div class="actions">
                <a class="btn" href="editar.php?id=<?= $uid ?>">Editar</a>

                <?php if ($cid !== null): ?>
                    <a class="btn btn-client" href="../clientes/editar_cliente.php?id=<?= $cid ?>">Editar Cliente</a>
                <?php endif; ?>

                <?php if ($cid === null): ?>
                    <a class="btn btn-red"
                       href="eliminar.php?id=<?= $uid ?>"
                       onclick="return confirm('¿Eliminar usuario?')">
                        Eliminar
                    </a>
                <?php endif; ?>
            </div>
        </td>
    </tr>
<?php } ?>

</table>

<div class="add-user-box">
    <a href="agregar.php">➕ Agregar usuario</a>
</div>

</body>
</html>
