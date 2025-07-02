<?php
// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'conexion.php';
require_once 'permisos.php';

$conexion = obtenerConexion();
// Verificar permisos - Añadido al inicio del archivo
if (!tienePermiso('agenda', 'puede_editar')) {
    header("Location: error.php");
    exit;
}

$errores = [];
$servicio = [];

// Obtener ID del servicio
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: agenda.php");
    exit;
}

// Obtener datos del servicio
$sql = "SELECT s.id, c.nombre as cliente, s.fecha, s.hora, s.descripcion, s.estado 
        FROM servicios s 
        JOIN clientes c ON s.cliente_id = c.id 
        WHERE s.id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$servicio = $resultado->fetch_assoc();

if (!$servicio) {
    header("Location: agenda.php");
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar'])) {
    // Verificar permisos nuevamente antes de actualizar - Añadido
    if (!tienePermiso('agenda', 'puede_editar')) {
        $errores[] = "No tienes permisos para editar servicios";
    } else {
        $fecha = $conexion->real_escape_string(trim($_POST['fecha']));
        $hora = $conexion->real_escape_string(trim($_POST['hora']));
        $descripcion = $conexion->real_escape_string(trim($_POST['descripcion']));
        $estado = $conexion->real_escape_string(trim($_POST['estado']));

        // Validaciones
        if (empty($fecha) || empty($hora) || empty($descripcion) || empty($estado)) {
            $errores[] = "Todos los campos son obligatorios";
        } else {
            $sql = "UPDATE servicios SET 
                    fecha = ?, 
                    hora = ?, 
                    descripcion = ?, 
                    estado = ? 
                    WHERE id = ?";
            
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ssssi", $fecha, $hora, $descripcion, $estado, $id);

            if ($stmt->execute()) {
                $_SESSION['mensaje_exito'] = "Servicio actualizado correctamente";
                header("Location: agenda.php");
                exit;
            } else {
                $errores[] = "Error al actualizar el servicio: " . $conexion->error;
            }
        }
    }
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Servicio - JID Connect</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .error { color: red; margin: 10px 0; padding: 10px; background: #ffeeee; }
        .mensaje-exito { color: green; margin: 10px 0; padding: 10px; background: #eeffee; }
    </style>
</head>
<body>
    <?php include('menu.php'); ?>
    
    <div class="main-content">

    <main class="main">
        <div class="container">
            <div class="card">
                <h2 class="card-title">Editar Servicio #<?= htmlspecialchars($servicio['id']) ?></h2>
                
                <?php if (!empty($errores)): ?>
                    <div class="error">
                        <?php foreach ($errores as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group">
                        <label class="form-label">Cliente:</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($servicio['cliente']) ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha" class="form-label">Fecha:</label>
                        <input type="date" id="fecha" name="fecha" class="form-control" 
                               value="<?= htmlspecialchars($servicio['fecha']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="hora" class="form-label">Hora:</label>
                        <input type="time" id="hora" name="hora" class="form-control" 
                               value="<?= htmlspecialchars($servicio['hora']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion" class="form-label">Descripción:</label>
                        <textarea id="descripcion" name="descripcion" class="form-control" rows="4" required><?= 
                            htmlspecialchars($servicio['descripcion']) 
                        ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="estado" class="form-label">Estado:</label>
                        <select id="estado" name="estado" class="form-control" required>
                            <option value="pendiente" <?= $servicio['estado'] == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="en_progreso" <?= $servicio['estado'] == 'en_progreso' ? 'selected' : '' ?>>En progreso</option>
                            <option value="completado" <?= $servicio['estado'] == 'completado' ? 'selected' : '' ?>>Completado</option>
                            <option value="cancelado" <?= $servicio['estado'] == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="actualizar" class="btn btn-primary">Actualizar Servicio</button>
                    <a href="agenda.php" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    </main>
    </div>
</body>
</html>