<?php
session_start();
require_once 'conexion.php';
$conexion = obtenerConexion();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $conexion->real_escape_string($_POST['usuario']);
    $contraseña = $conexion->real_escape_string($_POST['contraseña']);

    $sql = "SELECT u.id, u.usuario, u.contraseña, u.id_cargo, c.cargo 
            FROM usuario u
            JOIN cargo c ON u.id_cargo = c.id
            WHERE u.usuario = '$usuario' LIMIT 1";
    
    $resultado = $conexion->query($sql);

    if ($resultado && $resultado->num_rows == 1) {
        $usuario = $resultado->fetch_assoc();
        
        if ($contraseña === $usuario['contraseña']) {
            $_SESSION['id'] = $usuario['id'];
            $_SESSION['usuario'] = $usuario['usuario'];
            $_SESSION['id_cargo'] = $usuario['id_cargo'];
            $_SESSION['cargo'] = $usuario['cargo'];

            
            header('Location: tabla_clientes.php');
            exit;
        }
    }
    
    header('Location: login.php?error=credenciales');
    exit;
}

header('Location: login.php');
exit;
?>