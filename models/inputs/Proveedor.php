  <?php
require_once __DIR__ . '/../Database.php';

class Proveedor {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConexion();
    }

    public function listar($busqueda = '', $estado = 'todos', $limite = 6, $offset = 0) {
        $sql = "SELECT * FROM proveedores WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($busqueda)) {
            $sql .= " AND (nombre_apellido LIKE ? OR contacto LIKE ? OR email LIKE ? OR telefono LIKE ?)";
            $term = "%" . $busqueda . "%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $types .= "ssss";
        }

        $sql .= " ORDER BY id_proveedores DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limite;
        $params[] = (int)$offset;
        $types .= "ii";

        $stmt = $this->db->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $resultado = $stmt->get_result();

        return $resultado->fetch_all(MYSQLI_ASSOC);
    }

    public function contar($busqueda = '', $estado = 'todos') {
        $sql = "SELECT COUNT(*) as total FROM proveedores WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($busqueda)) {
            $sql .= " AND (nombre_apellido LIKE ? OR contacto LIKE ? OR email LIKE ? OR telefono LIKE ?)";
            $term = "%" . $busqueda . "%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $types .= "ssss";
        }

        $stmt = $this->db->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $resultado = $stmt->get_result();
        $row = $resultado->fetch_assoc();

        return $row['total'] ?? 0;
    }

    public function crear($razon_social, $persona_contacto, $email, $direccion, $telefono) {
        $sql = "INSERT INTO proveedores (nombre_apellido, contacto, email, direccion, telefono) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sssss", $razon_social, $persona_contacto, $email, $direccion, $telefono);
        return $stmt->execute();
    }

    public function eliminar($id_proveedor) {
        $sql = "DELETE FROM proveedores WHERE id_proveedores = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_proveedor);

        try {
            return $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1451) {
                return 'tiene_relacion';
            }
            throw $e;
        }
    }

    public function editar($razon_social, $persona_contacto, $email, $direccion, $telefono, $id_proveedor) {
        $sql = "UPDATE proveedores SET nombre_apellido = ?, contacto = ?, email = ?, direccion = ?, telefono = ? WHERE id_proveedores = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sssssi", $razon_social, $persona_contacto, $email, $direccion, $telefono, $id_proveedor);
        return $stmt->execute();
    }

    public function obtenerPorId($id_proveedor) {
        $sql = "SELECT * FROM proveedores WHERE id_proveedores = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id_proveedor);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }
}