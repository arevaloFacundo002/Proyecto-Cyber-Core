<?php
require_once __DIR__ . '/../../auth/staff.php';
require_once __DIR__ . '/../../models/inputs/CompraProveedor.php';

class CompraProveedorController
{
    private CompraProveedor $compra;

    public function __construct()
    {
        $this->compra = new CompraProveedor();
    }

    // Guarda un mensaje para mostrar en la próxima pantalla y redirige
    private function volver(string $destino, string $tipo, string $texto): void
    {
        $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];
        header("Location: " . $destino);
        exit();
    }

    // GUARDAR BORRADOR o CONFIRMAR (viene del formulario "Cargar compra")
    public function guardar(): void
    {
        $id_compra = (int) ($_POST['id_compra'] ?? 0);
        $confirmar = (($_POST['guardar'] ?? '') === 'confirmar');

        $cab = [
            'id_proveedor' => (int) ($_POST['id_proveedor'] ?? 0),
            'comprobante' => trim($_POST['comprobante'] ?? ''),
            'fecha' => trim($_POST['fecha'] ?? ''),
            'bonificacion' => (float) str_replace(',', '.', $_POST['bonificacion'] ?? '0'),
        ];

        // Armar las líneas desde los arrays paralelos del formulario
        $ids = $_POST['producto_id'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];
        $costos = $_POST['costo'] ?? [];

        $lineas = [];
        foreach ($ids as $i => $id_producto) {
            $cant = (string) ($cantidades[$i] ?? '');
            $lineas[] = [
                'id_producto' => (int) $id_producto,
                // cantidad: solo enteros; si viene "2.5" o texto, queda 0 y la validación lo rechaza
                'cantidad' => ctype_digit($cant) ? (int) $cant : 0,
                'costo' => (float) str_replace(',', '.', (string) ($costos[$i] ?? '0')),
            ];
        }

        $resultado = $this->compra->guardar(
            $cab,
            $lineas,
            $confirmar,
            $_SESSION['id_usuario'] ?? null,
            $id_compra > 0 ? $id_compra : null
        );

        if (!$resultado['ok']) {
            // Se conserva lo cargado para no perder la grilla
            $_SESSION['compra_old'] = ['cab' => $cab, 'lineas' => $lineas, 'id_compra' => $id_compra];

            $destino = '../../views/compras_proveedor/cargarCompra.php' . ($id_compra > 0 ? '?id=' . $id_compra : '');
            $this->volver($destino, 'danger', $resultado['error']);
        }

        $texto = $confirmar
            ? "Compra confirmada correctamente. Se registraron {$resultado['movimientos']} movimientos de stock."
            : 'Borrador guardado correctamente. El stock todavía no se modificó.';

        $this->volver(
            '../../views/compras_proveedor/detalleCompra.php?id=' . $resultado['id_compra'],
            'success',
            $texto
        );
    }

    // CONFIRMAR un borrador ya guardado
    public function confirmar(): void
    {
        $id = (int) ($_POST['id_compra'] ?? 0);
        $r = $this->compra->confirmar($id, $_SESSION['id_usuario'] ?? null);
        $destino = '../../views/compras_proveedor/detalleCompra.php?id=' . $id;

        if ($r['ok']) {
            $this->volver($destino, 'success',
                "Compra confirmada correctamente. Se registraron {$r['movimientos']} movimientos de stock.");
        }
        $this->volver($destino, 'danger', $r['error']);
    }

    // ANULAR una compra confirmada
    public function anular(): void
    {
        $id = (int) ($_POST['id_compra'] ?? 0);
        $r = $this->compra->anular($id, $_SESSION['id_usuario'] ?? null);
        $destino = '../../views/compras_proveedor/detalleCompra.php?id=' . $id;

        if ($r['ok']) {
            $this->volver($destino, 'success',
                "Compra anulada correctamente. Se revirtieron {$r['movimientos']} movimientos de stock.");
        }
        $this->volver($destino, 'danger', $r['error']);
    }

    // ELIMINAR un borrador
    public function eliminar(): void
    {
        $id = (int) ($_POST['id_compra'] ?? 0);
        $r = $this->compra->eliminarBorrador($id);

        if ($r['ok']) {
            $this->volver('../../views/compras_proveedor/listarCompra.php', 'success',
                'Borrador eliminado correctamente.');
        }
        $this->volver('../../views/compras_proveedor/detalleCompra.php?id=' . $id, 'danger', $r['error']);
    }
}

// Solo se aceptan POST: ninguna de estas acciones modifica datos por un simple link
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../views/compras_proveedor/listarCompra.php');
    exit();
}

$controller = new CompraProveedorController();

switch ($_POST['accion'] ?? '') {
    case 'guardar':
        $controller->guardar();
        break;
    case 'confirmar':
        $controller->confirmar();
        break;
    case 'anular':
        $controller->anular();
        break;
    case 'eliminar':
        $controller->eliminar();
        break;
    default:
        header('Location: ../../views/compras_proveedor/listarCompra.php');
        exit();
}