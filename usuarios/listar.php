<?php
session_start();
require_once "../conexion.php";
$dao = new UserDao();


if (!isset($_SESSION['usuario'])) {
    header("Location: ../index.php");
    exit;
}

// Búsqueda y filtro
$busqueda = $_GET['buscar'] ?? "";
$estado   = $_GET['estado'] ?? "";

$usuarios = $dao->listar_usuarios($busqueda,$estado);

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

        <input type="text" name="buscar" placeholder="Buscar por usuario, nombre, correo, rol..."
               value="<?php echo htmlspecialchars($busqueda) ?>">

        <select name="estado">
            <option value="">Estado Usuario (Todos)</option>
            <option value="activo"    <?php echo $estado=="activo"?"selected":"" ?>>Activo</option>
            <option value="inactivo"  <?php echo $estado=="inactivo"?"selected":"" ?>>Inactivo</option>
            <option value="bloqueado" <?php echo $estado=="bloqueado"?"selected":"" ?>>Bloqueado</option>
            <option value="no-cliente" <?php echo $estado=="no-cliente"?"selected":"" ?>>Solo No Clientes</option>
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

<?php foreach($usuarios as $fila) { 
        $u_id = (int)$fila['id_usuario'];
        $u_name = htmlspecialchars($fila['nombre']);
        $u_email = htmlspecialchars($fila['correo']);
        $u_rol = htmlspecialchars($fila['tipo_usuario']);
        $u_registro = htmlspecialchars($fila['fecha_registro']);
        $c_id = $fila['id_cliente'] ?? null;
        $u_estado = $fila['estado'] ?? null;
?>
    <tr>
        <td><?php echo $u_id ?></td>
        <td><?php echo $u_name ?></td>
        <td><?php echo $u_email ?></td>
        <td><?php echo $u_rol ?></td>
        <td><?php echo $u_registro ?></td>

        <td>
            <?php 
                $color_map = [
                    'activo' => 'green',
                    'inactivo' => 'orange',
                    'bloqueado' => 'red'
                ];

                $color = $color_map[$u_estado] ?? 'black';
            ?>

            <strong style="color:<?php echo $color ?>">
                <?php echo ucfirst($u_estado ?? 'sin estado') ?>
            </strong>

            <br>

            <?php if ($c_id !== null): ?>
                <Strong>Cliente ID: </Strong> <?php echo $c_id ?>
            <?php else: ?>
                <a class="link-cliente" href="../clientes/crear_cliente.php?id_user=<?php echo $u_id ?>">
                    Crear cliente
                </a>
            <?php endif; ?>
        </td>


        <td>
            <div class="actions">
                <?php if ($c_id == null): ?>
                <a class="btn" href="editar.php?id=<?php echo $u_id ?>">Editar</a>

                <?php  else: ?>
                    <a class="btn btn-client" href="../clientes/editar_cliente.php?id=<?php echo $c_id ?>">
                        Editar Cliente</a>
                <?php endif; ?>

                <form action="cambiar_estado.php" method="POST" style="display:flex; gap:5px; align-items:center;">
                    <input type="hidden" name="id" value="<?= $u_id ?>">

                    <select name="estado"
                            class="form-select"
                            style="color: <?= $color ?>; font-weight: bold; border-color: <?= $color ?>;">

                        <option value="activo" <?= $u_estado == 'activo' ? 'selected' : '' ?>>
                            🟢 Activo
                        </option>
                        <option value="inactivo" <?= $u_estado == 'inactivo' ? 'selected' : '' ?>>
                            🟡 Inactivo
                        </option>
                        <option value="bloqueado" <?= $u_estado == 'bloqueado' ? 'selected' : '' ?>>
                            🔴 Bloqueado
                        </option>
                    </select>

                    <button class="btn btn-primary">
                        Guardar
                    </button>
                </form>
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
