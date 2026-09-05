<?php

require_once 'C:\Users\areva\.vscode\cyber_core\models/inputs/Proveedor.php';

class ProveedorController
{
    private Proveedor $proveedor;

    public function __construct()
    {
        $this->proveedor = new Proveedor();
    }


    public function crear()
    {
        $razon_social = $_POST['razon_social'];
        $persona_contacto = $_POST['persona_contacto'];
        $email = $_POST['email'];
        $direccion = $_POST['direccion'];
        $telefono = $_POST['telefono'];

        $resultado = $this->proveedor->crear(
            $razon_social,
            $persona_contacto,
            $email,
            $direccion,
            $telefono
        );

        if ($resultado) {
            header("Location: ../../views/proveedores/listarProveedor.php?mensaje=creado");
            exit();
        }
        else {
            header("Location: ../../views/proveedores/listarProveedor.php?error=1");
            exit();
        }
    }


    public function editar()
    {
        $id_proveedor = $_POST['id_proveedor'];

        $razon_social = $_POST['razon_social'];
        $persona_contacto = $_POST['persona_contacto'];
        $email = $_POST['email'];
        $direccion = $_POST['direccion'];
        $telefono = $_POST['telefono'];

        $resultado = $this->proveedor->editar(
            $razon_social,
            $persona_contacto,
            $email,
            $direccion,
            $telefono,
            $id_proveedor
        );

        if ($resultado) {
            header("Location: ../../views/proveedores/listarProveedor.php?mensaje=editado");
            exit();
        }
        else {
            header("Location: ../../views/proveedores/listarProveedor.php?error=1");
            exit();
        }
    }


    public function eliminar()
    {
        $id_proveedor = $_GET['id'];

        $resultado = $this->proveedor->eliminar($id_proveedor);

        if ($resultado) {
            header("Location: ../../views/proveedores/listarProveedor.php?mensaje=eliminado");
            exit();
        }
        else {
            header("Location: ../../views/proveedores/listarProveedor.php?error=1");
            exit();
        }
    }

    public function activar()
    {
        $id_proveedor = $_GET['id'];

        $resultado = $this->proveedor->activar($id_proveedor);

        if ($resultado) {
            header(
                "Location: ../../views/proveedores/listarProveedor.php?mensaje=activado"
            );
            exit();

        } else {
            header(
                "Location: ../../views/proveedores/listarProveedor.php?error=1"
            );
            exit();
        }
    }
}


$proveedorController = new ProveedorController();

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {
    case 'crear':
        $proveedorController->crear();
        break;

    case 'editar':
        $proveedorController->editar();
        break;

    case 'eliminar':
        $proveedorController->eliminar();
        break;

    case 'activar':
        $proveedorController->activar();
        break;

    default:
        header("Location: ../../views/proveedores/listarProveedor.php");
        exit();
}