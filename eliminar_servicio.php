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
// Verificar permisos para eliminar servicios de agenda
if (!tienePermiso('agenda', 'puede_eliminar')) {
    $_SESSION['mensaje_error'] = "No tienes permisos para eliminar servicios";
    header("Location: agenda.php");
    exit;
}

// Validar y obtener ID del servicio
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensaje_error'] = "ID de servicio no válido";
    header("Location: agenda.php");
    exit;
}

$id_servicio = intval($_GET['id']);

// Obtener información del servicio antes de eliminar (para mensaje de confirmación)
$sql_info = "SELECT s.id, c.nombre as cliente, s.fecha, s.hora 
             FROM servicios s
             JOIN clientes c ON s.cliente_id = c.id
             WHERE s.id = ?";
$stmt_info = $conexion->prepare($sql_info);
$stmt_info->bind_param("i", $id_servicio);
$stmt_info->execute();
$resultado = $stmt_info->get_result();

if ($resultado->num_rows === 0) {
    $_SESSION['mensaje_error'] = "El servicio no existe o ya fue eliminado";
    header("Location: agenda.php");
    exit;
}

$servicio = $resultado->fetch_assoc();
$stmt_info->close();

// Verificar que el usuario tiene permisos sobre este servicio específico
// (excepto para administradores que pueden eliminar cualquier servicio)
if ($_SESSION['id_cargo'] != 1) {
    $sql_permiso = "SELECT id FROM servicios WHERE id = ? AND usuario_id = ?";
    $stmt_permiso = $conexion->prepare($sql_permiso);
    $stmt_permiso->bind_param("ii", $id_servicio, $_SESSION['id']);
    $stmt_permiso->execute();
    
    if ($stmt_permiso->get_result()->num_rows === 0) {
        $_SESSION['mensaje_error'] = "Solo puedes eliminar tus propios servicios";
        header("Location: agenda.php");
        exit;
    }
    $stmt_permiso->close();
}

// Eliminar el servicio
$sql_eliminar = "DELETE FROM servicios WHERE id = ?";
$stmt_eliminar = $conexion->prepare($sql_eliminar);
$stmt_eliminar->bind_param("i", $id_servicio);

if ($stmt_eliminar->execute()) {
    // Registrar acción en bitácora
    $detalle = "Servicio eliminado: Cliente " . $servicio['cliente'] . 
               " para " . $servicio['fecha'] . " a las " . $servicio['hora'];
    registrarBitacora($_SESSION['id'], "eliminación_servicio", $detalle);
    
    $_SESSION['mensaje_exito'] = "✅ Servicio del " . htmlspecialchars($servicio['fecha']) . 
                                " con " . htmlspecialchars($servicio['cliente']) . " eliminado correctamente";
} else {
    $_SESSION['mensaje_error'] = "❌ Error al eliminar el servicio: " . $conexion->error;
}

$stmt_eliminar->close();
$conexion->close();
header("Location: agenda.php");
exit;

// Función para registrar en bitácora (si existe)
function registrarBitacora($usuario_id, $accion, $detalle) {
    if (function_exists('registrarEnBitacora')) {
        registrarEnBitacora($usuario_id, $accion, $detalle);
    }
}
?>