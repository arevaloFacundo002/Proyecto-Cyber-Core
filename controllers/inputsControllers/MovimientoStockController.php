<?php
require_once "../../auth/auth.php";
require_once 'C:\Users\areva\.vscode\cyber_core\models\inputs\MovimientoStock.php';

class MovimientoStockController
{
    private MovimientoStock $movimientoStock;

    public function __construct()
    {
        $this->movimientoStock = new MovimientoStock();
    }

    // REGISTRAR MOVIMIENTO
    public function registrar()
    {
        $id_producto = (int) $_POST['id_producto'];
        $id_concepto = (int) $_POST['id_concepto'];
        $cantidad = (int) $_POST['cantidad'];

        $fecha_movimiento = !empty($_POST['fecha_movimiento'])
            ? $_POST['fecha_movimiento']
            : date('Y-m-d');

        $referencia_ext = !empty($_POST['referencia_ext'])
            ? trim($_POST['referencia_ext'])
            : null;

        $comentario = !empty($_POST['comentario'])
            ? trim($_POST['comentario'])
            : null;

        $id_usuario = $_SESSION['id_usuario'] ?? null;

        // Validación básica
        if (
            $id_producto <= 0 ||
            $id_concepto <= 0 ||
            $cantidad === 0
        ) {
            header(
                "Location: ../../views/movimientos/registrarMovimiento.php?error=datos"
            );
            exit();
        }

        $resultado = $this->movimientoStock->registrar(
            $id_producto,
            $id_concepto,
            $cantidad,
            $fecha_movimiento,
            $referencia_ext,
            $comentario,
            $id_usuario
        );

        if ($resultado) {

            header(
                "Location: ../../views/movimientos/listarMovimiento.php?mensaje=registrado"
            );

        } else {

            header(
                "Location: ../../views/movimientos/registrarMovimiento.php?error=1"
            );
        }

        exit();
    }

    // OBTENER MOVIMIENTO
    public function obtener()
    {
        $id_movimiento = (int) ($_GET['id'] ?? 0);

        if ($id_movimiento <= 0) {
            header(
                "Location: ../../views/movimientos/listarMovimiento.php?error=1"
            );
            exit();
        }

        $movimiento = $this->movimientoStock->obtenerPorId(
            $id_movimiento
        );

        if ($movimiento === null) {
            header(
                "Location: ../../views/movimientos/listarMovimiento.php?error=no_encontrado"
            );
            exit();
        }

        return $movimiento;
    }
}


// INSTANCIA DEL CONTROLLER

$movimientoStockController = new MovimientoStockController();

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {

    case 'registrar':
        $movimientoStockController->registrar();
        break;

    case 'obtener':
        $movimientoStockController->obtener();
        break;

    default:
        header(
            "Location: ../../views/movimientos/listarMovimiento.php"
        );
        exit();
}