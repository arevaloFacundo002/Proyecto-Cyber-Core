 <?php
require_once "../models/Usuario.php";
$user = new Usuario();

require_once "../auth/auth.php";

// Búsqueda y filtro
$busqueda = $_GET['buscar'] ?? "";
$estado   = $_GET['estado'] ?? "";

$usuarios = $user->listar_usuarios($busqueda,$estado);

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta http-equiv="Cache-Control" content="no-store" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />

<title>ABM Usuarios</title>

<link href="../css/theme.css" rel="stylesheet">

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
}

/* HEADER */
header {
    background: var(--cc-superficie-2);
    padding: 18px 40px;
    color: var(--cc-texto);
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--cc-borde);
}

header a {
    color: var(--cc-primario);
    text-decoration: none;
    font-weight: bold;
}

/* TITULO */
h2 {
    text-align: center;
    margin-top: 25px;
    font-size: 28px;
    color: var(--cc-texto);
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
    border: 1px solid var(--cc-borde);
    background: var(--cc-superficie);
    color: var(--cc-texto);
}

.search-box input {
    width: 300px;
}

.search-box button {
    padding: 12px 18px;
    border: none;
    border-radius: 20px;
    background: var(--cc-primario);
    color: var(--cc-texto-sobre-primario);
    font-weight: bold;
    cursor: pointer;
}

.search-box button:hover {
    background: var(--cc-primario-hover);
}

/* TABLA */
table {
    width: 90%;
    margin: 30px auto;
    border-collapse: collapse;
    background: var(--cc-superficie);
    border: 1px solid var(--cc-borde);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 0 10px rgba(0,0,0,0.35);
}

th {
    background: rgba(0, 234, 255, 0.15);
    color: var(--cc-primario);
    padding: 12px;
    text-transform: uppercase;
}

td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid var(--cc-borde);
    color: var(--cc-texto);
}

tr:hover {
    background: rgba(0, 234, 255, 0.07);
}

/* BOTONES */
.btn {
    padding: 6px 12px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: bold;
    background: var(--cc-primario);
    color: var(--cc-texto-sobre-primario);
    transition: 0.3s;
    display: inline-block;
}

.btn:hover {
    background: var(--cc-primario-hover);
}

.btn-red {
    background: var(--cc-peligro);
    color: white;
}

.btn-red:hover {
    filter: brightness(1.15);
}

.btn-client {
    background: var(--cc-advertencia);
    color: #1a1400;
}

.btn-client:hover {
    filter: brightness(1.1);
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
    background: var(--cc-primario);
    padding: 10px 20px;
    border-radius: 20px;
    color: var(--cc-texto-sobre-primario);
    font-weight: bold;
    text-decoration: none;
}

.add-user-box a:hover {
    background: var(--cc-primario-hover);
}

.link-cliente {
    color: var(--cc-primario);
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
        $u_name = htmlspecialchars($fila['nombre'] ?? '');
        $u_email = htmlspecialchars($fila['correo'] ?? '');
        $u_rol = htmlspecialchars($fila['nombre_perfil'] ?? '');
        $u_registro = htmlspecialchars($fila['fecha_registro'] ?? '');
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
                    'activo' => 'var(--cc-exito)',
                    'inactivo' => 'var(--cc-advertencia)',
                    'bloqueado' => 'var(--cc-peligro)'
                ];

                $color = $color_map[$u_estado] ?? 'var(--cc-texto)';
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
                            style="color: <?= $color ?>; font-weight: bold; border-color: <?= $color ?>; background:var(--cc-superficie-2);">

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

<script src="../js/theme-toggle.js"></script>

</body>
</html>