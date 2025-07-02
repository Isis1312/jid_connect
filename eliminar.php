<?php
session_start();
require_once 'conexion.php';
require_once 'permisos.php';

$conexion = obtenerConexion();
// Verificar permisos 
if (!tienePermiso('clientes', 'puede_eliminar')) {
    $_SESSION['mensaje_error'] = "No tienes permisos para esta acción";
    header("Location: tabla_clientes.php");
    exit;
}


$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    $_SESSION['mensaje_error'] = "ID de cliente inválido";
    header("Location: tabla_clientes.php");
    exit;
}

$sql_verificar = "SELECT id, nombre FROM clientes WHERE id = ?";
$stmt = $conexion->prepare($sql_verificar);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows == 0) {
    $_SESSION['mensaje_error'] = "El cliente no existe";
    header("Location: tabla_clientes.php");
    exit;
}

$cliente = $resultado->fetch_assoc();
$stmt->close();

$sql_eliminar = "DELETE FROM clientes WHERE id = ?";
$stmt = $conexion->prepare($sql_eliminar);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['mensaje_exito'] = "✅ Cliente eliminado correctamente";
} else {
    $_SESSION['mensaje_error'] = "❌ Error al eliminar el cliente: " . $conexion->error;
}

$conexion->close();
header("Location: tabla_clientes.php");
exit;
?>