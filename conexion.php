<?php

if (!function_exists('obtenerConexion')) {
    function obtenerConexion() {
        $host = "localhost";
        $dbname = "jid_bd";
        $username = "root";
        $password = "";
        
        try {
            $conexion = new mysqli($host, $username, $password, $dbname);
            
            if ($conexion->connect_error) {
                die("Error de conexión: " . $conexion->connect_error);
            }
            
            return $conexion;
        } catch (Exception $e) {
            die("Error al conectar: " . $e->getMessage());
        }
    }
}
?>