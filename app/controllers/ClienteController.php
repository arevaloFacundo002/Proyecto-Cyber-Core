<?php
/**
 * Maneja los clientes (perfil extendido de un usuario) y la carga de
 * localidades por provincia (AJAX).
 */
class ClienteController extends Controller
{
    private Cliente $clienteModel;
    private Ubicacion $ubicacionModel;
    private Usuario $usuarioModel;

    public function __construct(string $base = '')
    {
        parent::__construct($base);
        $this->clienteModel = new Cliente();
        $this->ubicacionModel = new Ubicacion();
        $this->usuarioModel = new Usuario();
    }

    // clientes/crear_cliente.php
    public function crear(): void
    {
        $this->requiereLogin();

        // Validar ID de usuario
        if (!isset($_GET['id_user'])) {
            echo "Error: No se recibió el ID del usuario.";
            exit();
        }

        $id_usuario = intval($_GET['id_user']);

        // Obtener datos del usuario y validar que exista
        $usuario = $this->usuarioModel->obtener_usuario($id_usuario);
        if (!$usuario) {
            die('El Usuario no existe');
        }

        // verificar que no tenga un cliente asociado
        if ($this->clienteModel->existe_cliente($id_usuario)) {
            die('El usuario ya tiene un cliente asociado');
        }

        // Obtener provincias
        $provincias = $this->ubicacionModel->obtener_provincias();

        // PROCESO POST
        if (isset($_POST['guardar'])) {
            // Datos personales
            $nombre = trim($_POST['nombre']);
            $apellido = trim($_POST['apellido']);
            $cuil = trim($_POST['cuil']);
            $telefono = trim($_POST['telefono']);
            $tipo_contacto = 1; // por defecto es celular

            // Direccion
            $calle = trim($_POST['calle']);
            $numero = trim($_POST['numero']);
            $barrio = trim($_POST['barrio']);
            $piso = trim($_POST['piso']);
            $referencia = trim($_POST['referencia']);
            $localidad = trim($_POST['localidad']);
            $fecha = date("Y-m-d H:i:s");

            // validaciones
            if (!is_numeric($cuil)) {
                die('El cuil debe ser un numero valido');
            }
            if ($localidad <= 0) {
                die('Debe seleccionar una localidad valida');
            }

            // insertar direccion
            if (!$id_direccion = $this->ubicacionModel->insertar_direccion($calle, $numero, $barrio, $piso, $referencia, $localidad)) {
                die('Error al insertar direccion');
            }

            // Insertar cliente
            if ($id_cliente = $this->clienteModel->insertar_cliente($nombre, $apellido, $cuil, $fecha, $id_direccion, $id_usuario)
                and $this->clienteModel->insertar_contacto_cliente($telefono, $tipo_contacto, $id_cliente)) {
                $this->redirect('../usuarios/listar.php');
            } else {
                echo 'Error al insertar';
            }
        }

        $this->render('clientes/crear', [
            'usuario' => $usuario,
            'id_usuario' => $id_usuario,
            'provincias' => $provincias,
        ]);
    }

    // clientes/editar_cliente.php
    public function editar(): void
    {
        $this->requiereLogin();

        // Validar ID cliente
        if (!isset($_GET['id'])) {
            echo "Error: No se recibió el ID del cliente.";
            exit();
        }

        $id_cliente = intval($_GET['id']);

        // Obtener datos del cliente + contacto + provincia + localidad + direccion
        $cliente = $this->clienteModel->obtenerPerfilCompleto($id_cliente);

        if (!$cliente) {
            echo "Cliente no encontrado.";
            exit();
        }
        $rela_id_provincia = $cliente['rela_id_provincia'];

        // Obtener provincias
        $provincias = $this->ubicacionModel->obtener_provincias();

        // Obtener localidades segun la provincia actual del cliente
        $localidades = $this->ubicacionModel->obtenerLocalidadesPorProvincia($rela_id_provincia);

        // PROCESO POST del formulario
        if (isset($_POST['guardar'])) {

            // datos personales
            $nombre = $_POST['nombre'];
            $apellido = $_POST['apellido'];
            $telefono = $_POST['telefono'];
            $cuil = $_POST['cuil'];
            $tipo_contacto = 1;

            // direccion
            $calle = trim($_POST['calle']);
            $numero = trim($_POST['numero']);
            $barrio = trim($_POST['barrio']) ?: null; // Si el barrio esta vacio, se asigna null
            $localidad = intval($_POST['localidad']);

            // Actualizar cliente, direccion y contacto
            if ($this->clienteModel->actualizar_cliente($nombre, $apellido, $cuil, $id_cliente)
                and $this->ubicacionModel->actualizar_direccion($cliente['id_direccion'], $calle, $numero, $barrio, $localidad)
                and $this->clienteModel->actualizar_contacto_cliente($telefono, $tipo_contacto, $id_cliente)) {
                $this->redirect('../usuarios/listar.php');
            } else {
                echo 'Error en la actualizacion';
            }
        }

        $this->render('clientes/editar', [
            'cliente' => $cliente,
            'provincias' => $provincias,
            'localidades' => $localidades,
        ]);
    }

    // clientes/obtener_localidades.php (AJAX, devuelve JSON)
    public function obtenerLocalidades(): void
    {
        header('Content-Type: application/json');

        if (!isset($_GET['provincia'])) {
            echo json_encode([]);
            exit;
        }

        $id_provincia = intval($_GET['provincia']);

        $localidades = $this->ubicacionModel->obtenerLocalidadesPorProvincia($id_provincia);

        echo json_encode($localidades);
    }
}
