<?php
//este modelo se encarga de las responsabilidades relacionadas con provincias, localidades y direcciones
require_once __DIR__ . '/Database.php';

class Ubicacion{
    private mysqli $conexion;

    public function __construct() {
        $db = new DataBase();
        $this->conexion = $db->getConexion();
    }

    function obtener_provincias(){
        $prov_q = "SELECT * FROM provincias ORDER BY nombre_provincia ASC";
        $resultado = mysqli_query($this->conexion, $prov_q);

        $provincias =[];
        while ($fila = $resultado->fetch_assoc()) {
            $provincias[] = $fila;
        }
        return $provincias;
    }


    function insertar_direccion(string $calle,string $numero,string $barrio,string $piso,string $referencia,int $localidad){
        $sql = "INSERT INTO direcciones
        (calle, numero_exterior, barrio_colonia, piso_departamento, referencia_adicionales, rela_id_localidad)
        VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param(
            "sssssi",
            $calle,
            $numero,
            $barrio,
            $piso,
            $referencia,
            $localidad
        );

        $stmt->execute();
        return $this->conexion->insert_id;
    }

    function actualizar_direccion(int $id_direccion,string $calle,string $numero,string $barrio,int $localidad){
        $sql = "UPDATE direcciones
                SET calle=?,
                    numero_exterior=?,
                    barrio_colonia=?,
                    rela_id_localidad=?
                WHERE id_direccion=?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param(
            "sssii",
            $calle,
            $numero,
            $barrio,
            $localidad,
            $id_direccion
        );
        return $stmt->execute();
    }
     
    public function obtenerLocalidadesPorProvincia(int $idProvincia){
        $sql = "SELECT *
                FROM localidades
                WHERE rela_id_provincia = ?
                ORDER BY nombre_localidad ASC";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $idProvincia);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $localidades = [];

        while($fila = $resultado->fetch_assoc()){
            $localidades[] = $fila;
        }
        return $localidades;
    }

}