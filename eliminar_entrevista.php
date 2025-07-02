<?php
// Iniciar sesión y verificar autenticación
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

// Incluir archivos necesarios
require_once 'conexion.php';
require_once 'permisos.php';
$conexion = obtenerConexion();

// Verificar permisos para eliminar entrevistas
if (!tienePermiso('entrevistas', 'puede_eliminar')) {
    $_SESSION['mensaje_error'] = 'No tienes permisos para eliminar entrevistas';
    header("Location: entrevistas.php");
    exit;
}

// Verificar parámetro ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensaje_error'] = 'ID de entrevista no válido';
    header("Location: entrevistas.php");
    exit;
}

// Obtener y sanitizar ID de entrevista
$id_entrevista = $conexion->real_escape_string($_GET['id']);

// Verificar que la entrevista existe
$sql_verificar = "SELECT codigo_entrevista FROM entrevistas WHERE id_entrevista = ?";
$stmt_verificar = $conexion->prepare($sql_verificar);
$stmt_verificar->bind_param("i", $id_entrevista);
$stmt_verificar->execute();
$resultado = $stmt_verificar->get_result();

if ($resultado->num_rows === 0) {
    $_SESSION['mensaje_error'] = 'La entrevista no existe';
    header("Location: entrevistas.php");
    exit;
}

$entrevista = $resultado->fetch_assoc();
$stmt_verificar->close();

// Eliminar la entrevista
$sql_eliminar = "DELETE FROM entrevistas WHERE id_entrevista = ?";
$stmt_eliminar = $conexion->prepare($sql_eliminar);
$stmt_eliminar->bind_param("i", $id_entrevista);

if ($stmt_eliminar->execute()) {
    $_SESSION['mensaje_exito'] = 'Entrevista ' . htmlspecialchars($entrevista['codigo_entrevista']) . ' eliminada correctamente';
} else {
    $_SESSION['mensaje_error'] = 'Error al eliminar la entrevista: ' . $conexion->error;
}

$stmt_eliminar->close();
$conexion->close();
header("Location: entrevistas.php");
exit;
?>