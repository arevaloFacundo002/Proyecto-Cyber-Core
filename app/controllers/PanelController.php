<?php
/**
 * Panel de administracion (inicio.php).
 * Solo accesible para administradores y empleados.
 */
class PanelController extends Controller
{
    // inicio.php
    public function index(): void
    {
        $this->requiereLogin();

        // Si el usuario NO es admin ni empleado -> a la tienda
        if ($_SESSION['rol'] != "administrador" && $_SESSION['rol'] != "empleado") {
            $this->redirect('home.php');
        }

        $this->render('panel/inicio');
    }
}
