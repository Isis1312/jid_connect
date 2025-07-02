<?php
require_once 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


   function tienePermiso($modulo, $accion) {
    // Permiso universal
    if ($accion == 'ver_todo' || $accion == 'generar_pdf') {
        return isset($_SESSION['id_cargo']);
    } 
    
    // Obtener conexión solo si es necesario
    $conexion = obtenerConexion();
    
    if (!isset($_SESSION['id_cargo'])) {
        return false;
    }
    
    // Admin tiene todos los permisos
    if ($_SESSION['id_cargo'] == 1) {
        return true;
    }
    
    try {
        $id_cargo = $_SESSION['id_cargo'];
        $query = "SELECT $accion, ver_todo FROM permisos WHERE id_cargo = ? AND modulo = ?";
        $stmt = $conexion->prepare($query);
        
        if (!$stmt) {
            error_log("Error en consulta permisos: " . $conexion->error);
            return false;
        }
        
        $stmt->bind_param("is", $id_cargo, $modulo);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
           
            if ($row['ver_todo'] == 1) {
                return true;
            }
            
            return (bool)$row[$accion];
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error en tienePermiso: " . $e->getMessage());
        return false;
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
        if (isset($conexion)) {
            $conexion->close();
        }
    }
}

function verificarPermisos($modulo, $accion) {
    if (!tienePermiso($modulo, $accion)) {
        header('Location: error.php');
        exit;
    }
}
?>