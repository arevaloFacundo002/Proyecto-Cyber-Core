<?php
require_once 'database.php';

class Cliente {
    private mysqli $conexion;  

    public function __construct() {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    function existe_cliente(int $id_usuario) {
        $sql = "SELECT id_cliente FROM clientes WHERE rela_id_usuario = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('s',$id_usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->num_rows > 0;
    }

    function insertar_cliente(string $nombre,string $apellido,string $cuil,string $fecha,int $id_direccion,int $id_usuario){
        $sql = "INSERT INTO clientes (nombre, apellido, 
            cuil_cuit, fecha_registro, rela_id_direccion, rela_id_usuario)
            VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("ssssii",
            $nombre,
            $apellido,
            $cuil,
            $fecha,
            $id_direccion,
            $id_usuario
        );
        $stmt->execute();
        return $this->conexion->insert_id;
    }

    function insertar_contacto_cliente(string $telefono,int $tipo_contacto,int $id_cliente){
        $sql="INSERT INTO 
        contactos (valor, rela_id_tipo_contacto, rela_id_cliente)
            VALUES (?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('sii',$telefono,$tipo_contacto,$id_cliente);
        return $stmt->execute();
    }

    function actualizar_cliente(string $nombre,string $apellido,string $cuil,int $id_cliente){
        $sql = "UPDATE clientes
                SET nombre=?,
                    apellido=?,
                    cuil_cuit=?
                WHERE id_cliente=?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param(
            "sssi",
            $nombre,
            $apellido,
            $cuil,
            $id_cliente
        );

        return $stmt->execute();
    }

    function actualizar_contacto_cliente(string $telefono,int $tipo_contacto,int $id_cliente){
        $update = "UPDATE contactos SET valor=?, rela_id_tipo_contacto=?, rela_id_cliente=?
            WHERE rela_id_cliente=?";
        $stmt = $this->conexion->prepare($update);
        $stmt->bind_param('siii',$telefono,$tipo_contacto,$id_cliente,$id_cliente);
        return $stmt->execute();
    }

  // Obtener datos del cliente + contacto + provincia + localidad + direccion.  editar_cliente.php
    function obtenerPerfilCompleto(int $id_cliente){
        $sql = "SELECT
                    c.*,
                    u.correo,
                    co.valor as telefono,

                    d.id_direccion,
                    d.calle,
                    d.numero_exterior,
                    d.barrio_colonia,
                    d.piso_departamento,
                    d.referencia_adicionales,

                    l.id_localidad,
                    l.nombre_localidad,
                    l.rela_id_provincia

                FROM clientes c

                INNER JOIN usuarios u
                    ON u.id_usuario = c.rela_id_usuario

                INNER JOIN direcciones d
                    ON d.id_direccion = c.rela_id_direccion

                INNER JOIN localidades l
                    ON l.id_localidad = d.rela_id_localidad

                INNER JOIN contactos co
                    ON co.rela_id_cliente = c.id_cliente

                WHERE c.id_cliente = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i",$id_cliente);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

}