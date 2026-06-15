<?php
//este modelo se encarga de las responsabilidades relacionadas con provincias, localidades y direcciones
require_once 'C:\Users\areva\.vscode\cyber_core\models/Database.php';

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


    //ABM LOCALIDADES
    public function listarLocalidades(){
        $sql = "SELECT * FROM localidades WHERE es_activo = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $localidades = [];

        while ($fila = $resultado->fetch_assoc()) {
            $localidades[] = $fila;
        }
        return $localidades;
    }

    public function obtenerLocalidadPorId(int $id){
        $sql = "SELECT * FROM localidades WHERE id_localidad = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }

    public function crearLocalidades(string $nombre_localidad, int $codigo_postal,string $tipo_zona, int $rela_id_provincia){
        $sql = "INSERT INTO localidades (nombre_localidad, codigo_postal, tipo_zona, rela_id_provincia) VALUES (?,?,?,?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('sisi',$nombre_localidad,$codigo_postal,$tipo_zona,$rela_id_provincia);
        return $stmt->execute();
    }

    public function editarLocalidades(string $nombre_localidad, int $codigo_postal,string $tipo_zona, int $rela_id_provincia, int $id_localidad){
        $sql = "UPDATE localidades SET nombre_localidad = ?, codigo_postal = ?, tipo_zona = ?, rela_id_provincia = ? WHERE id_localidad = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('sisii',$nombre_localidad,$codigo_postal,$tipo_zona,$rela_id_provincia,$id_localidad);
        return $stmt->execute();
    }

    public function eliminarLocalidades(int $id_localidad){
        $sql = "UPDATE localidades SET es_activo = 0 WHERE id_localidad = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id_localidad);
        return $stmt->execute();
    }

    //ABM PROVINCIAS
    public function listarProvincias(){
        $sql = "SELECT * FROM provincias WHERE es_activo = 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $provincias = [];

        while ($fila = $resultado->fetch_assoc()) {
            $provincias[] = $fila;
        }
        return $provincias;
    }

    public function obtenerProvinciaPorId(int $id){
        $sql = "SELECT * FROM provincias WHERE id_provincia = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }

    public function crearProvincias(string $nombre_provincia, string $codigo_iso, string $zona_tarifa, int $dias_transitos_base){
        $sql = "INSERT INTO provincias (nombre_provincia, codigo_iso, zona_tarifa, dias_transitos_base) VALUES (?,?,?,?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('sssi',$nombre_provincia,$codigo_iso,$zona_tarifa,$dias_transitos_base);
        return $stmt->execute();
    }

    public function editarProvincias(string $nombre_provincia, string $codigo_iso, string $zona_tarifa, int $dias_transitos_base, int $id_provincia){
        $sql = "UPDATE provincias SET nombre_provincia = ?, codigo_iso = ?, zona_tarifa = ?, dias_transitos_base = ? WHERE id_provincia = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('sssii',$nombre_provincia,$codigo_iso,$zona_tarifa,$dias_transitos_base,$id_provincia);
        return $stmt->execute();
    }


    public function eliminarProvincias(int $id_provincia){
        $sql = "UPDATE provincias SET es_activo = 0 WHERE id_provincia = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param('i',$id_provincia);
        return $stmt->execute();    
    }




    


}