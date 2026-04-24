<?php

class UserDao{
    private $conexion;
    private $servidor = 'localhost';
    private $usuario = 'root';
    private $password = 'jfa46064810';
    private $data_base = 'cyber_core';

    public function __construct() {
        try {
            $this->conexion = new mysqli($this->servidor,
            $this->usuario,
            $this->password,
            $this->data_base);
            
            if ($this->conexion->connect_error) {
                die("Error de conexion.".$this->conexion->connect_error);
            }
        } catch (Exception $ex) {
            echo "Error: ".$ex->getMessage();
        }
    }

    function login($correo) {
        $sql = "SELECT u.*,p.nombre_perfil FROM usuarios u
            join perfiles p
            on u.rela_id_perfil = p.id_perfil
            WHERE correo = ? ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado ->num_rows == 1) {
            return $resultado->fetch_assoc();
        }
        return null;
    }

    function listar_productos($buscar){
        $sql = "SELECT p.id_producto, p.nombre, p.precio, p.imagen_url,
                p.stock,
                c.nombre AS categoria,
                m.nombre_marca
            FROM productos p
            INNER JOIN categorias c ON p.rela_id_categoria = c.id_categoria
            INNER JOIN marcas m ON p.rela_id_marca = m.id_marca
            WHERE p.nombre LIKE ? OR c.nombre LIKE ? OR m.nombre_marca LIKE ?
            ORDER BY p.id_producto DESC";

        $stmt = $this->conexion->prepare($sql);
        $param = "%$buscar%";
        $stmt->bind_param("sss", $param, $param, $param);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $productos = [];

        while ($fila = $resultado->fetch_assoc()) {
            $productos[] = $fila;
        }
        return $productos;
    }

    function verificar_correo($correo){
        $sql_check = "SELECT id_usuario FROM usuarios WHERE correo = ?";
        $stmtCheck = $this->conexion->prepare($sql_check);
        $stmtCheck->bind_param("s", $correo);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();

        return $resCheck->num_rows > 0;
        
    }

    function registrar_usuario($nombre,$correo,$password,$rela_id_perfil,$fecha_registro) {
        $sql = "INSERT INTO usuarios (nombre, correo, password, rela_id_perfil, fecha_registro)
            VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);
    
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt->bind_param("sssss",$nombre,$correo,$password_hash,$rela_id_perfil,$fecha_registro);
        $stmt->execute();

        return $this->conexion->insert_id;      # esta es una funcion que devuelve el ultimo id insertado.  
    }

    function busqueda_de_producto($id){
        $sql = "SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.imagen_url,
               p.stock,
               c.nombre AS categoria,
               m.nombre_marca
        FROM productos p
        INNER JOIN categorias c ON p.rela_id_categoria = c.id_categoria
        INNER JOIN marcas m ON p.rela_id_marca = m.id_marca
        WHERE p.id_producto = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($producto = $resultado->fetch_assoc()) {
            return $producto;
        }
        return null;
    }

    function consulta_producto_stock($id){
        $sql = "SELECT id_producto, nombre, precio, stock, imagen_url 
        FROM productos 
        WHERE id_producto = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if($producto = $resultado->fetch_assoc()){ 
            return $producto;
        }
        return null;
    }

    function consultar_reseñas($id){
        $sqlreseñas = "SELECT comentario, calificacion 
                    FROM reseñas 
                    WHERE rela_id_producto = ?
                    ORDER BY id_reseña DESC";

        $stmt = $this->conexion->prepare($sqlreseñas);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $reseñas = [];

        while ($fila = $resultado->fetch_assoc()) {
            $reseñas[] = $fila;
        }
        return $reseñas;
    }

    function insertar_resenias($comentario,$calificacion, $id_producto){
        $sql = "INSERT INTO reseñas(comentario,calificacion,rela_id_producto)
            VALUES(?,?,?) ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('sii',$comentario, $calificacion,$id_producto);
        $resultado = $stmt->execute();
        return $resultado;
    }

    //CRUD
    function listar_usuarios($busqueda, $estado=''){
        $allowed = ['activo','inactivo','bloqueado'];

        $sql = "SELECT 
            u.*, 
            c.id_cliente,
            p.nombre_perfil
        FROM usuarios u
        LEFT JOIN clientes c ON c.rela_id_usuario = u.id_usuario
        join perfiles p on u.rela_id_perfil = p.id_perfil
        WHERE 1=1"; // truco PRO

        $params = [];
        $types = "";

        // 🔍 BUSQUEDA
        if (!empty($busqueda)) {
            $sql .= " AND (u.nombre LIKE ? OR u.correo LIKE ? OR p.nombre_perfil LIKE ?)";
            $param = "%$busqueda%";
            $params[] = $param;
            $params[] = $param;
            $params[] = $param;
            $types .= "sss";
        }

        // FILTROS
        if ($estado == 'no-cliente') {
            $sql .= " AND c.id_cliente IS NULL";

        } elseif ($estado == 'cliente') {
            $sql .= " AND c.id_cliente IS NOT NULL";

        } elseif (in_array($estado, $allowed, true)) {
            $sql .= " AND u.estado = ?";
            $params[] = $estado;
            $types .= "s";
        }

        $sql .= " ORDER BY u.id_usuario DESC";

        $stmt = $this->conexion->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();

        $usuarios = [];
        while ($fila = $result->fetch_assoc()) {
            $usuarios[] = $fila;
        }
        return $usuarios;
    }

    function agregar_usuario_panel($nombre,$password,$correo,$fecha,$rela_id_perfil) {
        $sql = "INSERT INTO usuarios(nombre, password, correo, fecha_registro, rela_id_perfil)
            VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bind_param('ssssi',$nombre,$password_hash,$correo,$fecha,$rela_id_perfil);
        $stmt->execute();

        return $this->conexion->insert_id;
    }

    function existe_usuario($id){
        $sql_check = "SELECT id_usuario FROM usuarios WHERE id_usuario = ?";
        $stmt = $this->conexion->prepare($sql_check);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        return $resultado->num_rows > 0;
    }

    function cambiar_estado($id,$estado){
        $sql = "UPDATE usuarios SET estado=?
            WHERE id_usuario= ? AND estado != ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('sis',$estado,$id,$estado);
        return $stmt->execute();
    }

    function obtener_usuario($id) {
        $sql = "SELECT u.*, p.nombre_perfil FROM usuarios u
            join perfiles p 
            on p.id_perfil = u.rela_id_perfil
            where u.id_usuario = ? ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuario = $resultado->fetch_assoc();
        return $usuario;
    }

    function editar_usuario($nombre, $correo, $rela_id_perfil, $id){
        $sql = "UPDATE usuarios SET nombre = ?, correo = ?, rela_id_perfil = ?
            WHERE id_usuario = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("ssii",$nombre, $correo, $rela_id_perfil, $id);
        return $stmt->execute();
    }

    
    //CLIENTES: 
    function existe_cliente($id_usuario) {
        $sql = "SELECT id_cliente FROM clientes WHERE rela_id_usuario = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('s',$id_usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->num_rows > 0;
    }

    function insertar_cliente($nombre,$apellido,$direccion,$telefono,$cuil,$fecha,$localidad,$id_usuario){
        $sql = "INSERT INTO clientes (nombre, apellido, direccion, telefono, 
            cuil_cuit, fecha_registro, rela_id_localidades, rela_id_usuario)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("ssssssii",
            $nombre,
            $apellido,
            $direccion,
            $telefono,
            $cuil,
            $fecha,
            $localidad,
            $id_usuario
        );
        return $stmt->execute();
    }

    // Obtener datos del cliente + provincia .editar_cliente.php
    function cliente_provincia($id_cliente){
        $sql = "SELECT c.*, u.correo, l.rela_id_provincia
                FROM clientes c
                INNER JOIN usuarios u ON u.id_usuario = c.rela_id_usuario
                INNER JOIN localidades l ON l.id_localidad = c.rela_id_localidades
                WHERE c.id_cliente = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id_cliente);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $cliente = $resultado->fetch_assoc();
        return $cliente;
    }

    // Obtener localidades según la provincia actual del cliente .editar_cliente.php
    function localidades_provincia_del_cliente($rela_id_provincia){
        $sql = "SELECT * FROM localidades WHERE rela_id_provincia = ? ORDER BY nombre_localidad ASC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $rela_id_provincia);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $localidades = [];
        while ($fila = $resultado->fetch_assoc()) {
            $localidades[]=$fila;
        }
        return $localidades;
    }

    function actualizar_cliente($nombre, $apellido, $direccion, $telefono,$cuil, $localidad, $id_cliente){
        $update = "UPDATE clientes 
            SET nombre=?, apellido=?, direccion=?, telefono=?, 
                cuil_cuit=?, rela_id_localidades=?
            WHERE id_cliente=?";

        $stmt = $this->conexion->prepare($update);
        $stmt->bind_param(
            "sssssii",
            $nombre, $apellido, $direccion, $telefono,
            $cuil, $localidad, $id_cliente
        );

        return $stmt->execute();
    }

    // PROVINCIAS Y LOCALIDADES:
        function obtener_provincias(){
        $prov_q = "SELECT * FROM provincias ORDER BY nombre_provincia ASC";
        $resultado = mysqli_query($this->conexion, $prov_q);

        $provincias =[];
        while ($fila = $resultado->fetch_assoc()) {
            $provincias[] = $fila;
        }
        return $provincias;
    }

    function obtener_localidades($id_provincia){
        $sql = "SELECT id_localidad, nombre_localidad FROM localidades
            WHERE rela_id_provincia = ?
            ORDER BY nombre_localidad ASC ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id_provincia);
        $stmt->execute();
        $resultado= $stmt->get_result();

        $localidades=[];

        while($fila = $resultado->fetch_assoc()){
            $localidades[]=$fila;
        }
        return $localidades;
    }
}

?>
