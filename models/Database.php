<?php
class DataBase{
    private mysqli $conexion;
    private $servidor = 'localhost';
    private $usuario = 'root';
    private $password = 'jfa46064810';
    private $data_base = 'cyber_core';

    public function __construct() {
        try {
            $this->conexion = new mysqli($this->servidor,
            $this->usuario,
            $this->password,
            $this->data_base);
            
            if ($this->conexion->connect_error) {
                die("Error de conexion.".$this->conexion->connect_error);
            }
        } catch (Exception $ex) {
            echo "Error: ".$ex->getMessage();
        }
    }

    public function getConexion() {
        return $this->conexion;
    }
    

}