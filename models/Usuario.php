<?php
require_once 'database.php';

class Usuario {
    private mysqli $conexion;  

    public function __construct() {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    function login(string $correo) {
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

    // Verificar si el correo ya existe en la base de datos -> exiteCorreo()
    function verificar_correo(string $correo){
        $sql_check = "SELECT id_usuario FROM usuarios WHERE correo = ?";
        $stmtCheck = $this->conexion->prepare($sql_check);
        $stmtCheck->bind_param("s", $correo);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();

        return $resCheck->num_rows > 0;
    }

    //registrar
    function registrar_usuario(string $nombre,string $correo,string $password,int $rela_id_perfil,string $fecha_registro,string $token) {
        $sql = "INSERT INTO usuarios (nombre,correo,password,rela_id_perfil,fecha_registro,token,validado)
            VALUES (?, ?, ?, ?, ?, ?, 0)";

        $stmt = $this->conexion->prepare($sql);
    
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt->bind_param("ssssss",$nombre,$correo,$password_hash,$rela_id_perfil,$fecha_registro,$token);
        $stmt->execute();

        return $this->conexion->insert_id;      # esta es una funcion que devuelve el ultimo id insertado.  
    }

    //
    function buscar_por_token(string $token){
        $sql = "SELECT * FROM usuarios WHERE token = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('s',$token);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }

    function validar_usuario(string $token){
        $sql = "UPDATE usuarios SET validado = 1, token = NULL
            WHERE token = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('s',$token);
        return $stmt->execute();
    }

    function actualizar_token(string $token, string $correo){
        $sql = 'UPDATE usuarios SET token = ? WHERE correo = ?';
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('ss',$token,$correo);
        return $stmt->execute();
    }

    function actualizar_password(string $token, string $password){
        $sql = "UPDATE usuarios 
                SET password = ?, token = NULL 
                WHERE token = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("ss", $password, $token);
        return $stmt->execute();
    }

    //crud -> listar
    function listar_usuarios(string $busqueda, string $estado=''){
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

    //crud -> agregar_desde_panel
    function agregar_usuario_panel(string $nombre, string $password, string $correo, string $fecha, int $rela_id_perfil) {
        $sql = "INSERT INTO usuarios(nombre, password, correo, fecha_registro, rela_id_perfil)
            VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bind_param('ssssi',$nombre,$password_hash,$correo,$fecha,$rela_id_perfil);
        $stmt->execute();

        return $this->conexion->insert_id;
    }


    function existe_usuario(int $id){   
        $sql_check = "SELECT id_usuario FROM usuarios WHERE id_usuario = ?";
        $stmt = $this->conexion->prepare($sql_check);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        return $resultado->num_rows > 0;
    }

    function cambiar_estado(int $id, string $estado){
        $sql = "UPDATE usuarios SET estado=?
            WHERE id_usuario= ? AND estado != ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('sis',$estado,$id,$estado);
        return $stmt->execute();
    }

//obtener_usuario_porID
    function obtener_usuario(int $id) {
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

    function editar_usuario(int $nombre, string $correo, int $rela_id_perfil, int $id){
        $sql = "UPDATE usuarios SET nombre = ?, correo = ?, rela_id_perfil = ?
            WHERE id_usuario = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("ssii",$nombre, $correo, $rela_id_perfil, $id);
        return $stmt->execute();
    }
}