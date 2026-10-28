<?php
require_once __DIR__ . '/../config/env.php';

class DataBase
{
    private mysqli $conexion;
    private $servidor;
    private $usuario;
    private $password;
    private $data_base;

    public function __construct()
    {
        $this->servidor  = env_valor('DB_HOST', 'localhost');
        $this->usuario   = env_valor('DB_USER', 'root');
        $this->password  = env_valor('DB_PASS', '');
        $this->data_base = env_valor('DB_NAME', 'cyber_core');

        try {
            $this->conexion = new mysqli(
                $this->servidor,
                $this->usuario,
                $this->password,
                $this->data_base
            );

            if ($this->conexion->connect_error) {
                die("Error de conexion." . $this->conexion->connect_error);
            }
        } catch (Exception $ex) {
            echo "Error: " . $ex->getMessage();
        }
    }

    public function getConexion()
    {
        return $this->conexion;
    }
}