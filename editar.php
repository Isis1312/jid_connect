<?php
// Control de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'conexion.php';
require_once 'permisos.php';
$conexion = obtenerConexion();
// Verificar permisos
if (!tienePermiso('clientes', 'puede_editar')) {
    header("Location: error.php");
    exit;
}

$errores = [];
$cliente = [];

// Obtener ID del cliente
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: tabla_clientes.php");
    exit;
}

// Obtener datos del cliente
$sql = "SELECT * FROM clientes WHERE id = ? LIMIT 1";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$cliente = $resultado->fetch_assoc();

if (!$cliente) {
    header("Location: tabla_clientes.php");
    exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar'])) {
    // Sanitizar entradas
    $nombre = $conexion->real_escape_string(trim($_POST['nombre']));
    $correo = $conexion->real_escape_string(trim($_POST['correo']));
    $ubicacion = $conexion->real_escape_string(trim($_POST['ubicacion']));
    $rif = $conexion->real_escape_string(trim($_POST['rif']));
    $telefono = $conexion->real_escape_string(trim($_POST['telefono']));
    $n_equipos = intval($_POST['n_equipos']);

    // Validaciones
    if (empty($nombre)) $errores[] = "El nombre es obligatorio";
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = "Correo electrónico inválido";
    if ($n_equipos <= 0) $errores[] = "El número de equipos debe ser mayor a cero";

    if (empty($errores)) {
        $sql = "UPDATE clientes SET 
                nombre = ?, 
                correo = ?, 
                ubicacion = ?, 
                rif = ?, 
                telefono = ?, 
                n_equipos = ? 
                WHERE id = ?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssssii", 
            $nombre,
            $correo,
            $ubicacion,
            $rif,
            $telefono,
            $n_equipos,
            $id
        );

        if ($stmt->execute()) {
            $_SESSION['mensaje_exito'] = "Cliente actualizado correctamente";
            header("Location: tabla_clientes.php");
            exit;
        } else {
            $errores[] = "Error al actualizar el cliente: " . $conexion->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        .error { color: red; margin: 10px 0; padding: 10px; background: #ffeeee; }
        .mensaje-exito { color: green; margin: 10px 0; padding: 10px; background: #eeffee; }
    </style>
</head>
<body>
      <div class="menu-container">
        <?php include('menu.php'); ?> 
    </div>

    <div class="main-content">
    <div class="content">
        <div class="contenedor">
            <div class="contenedor-7">
                <div class="mensaje">
                    <h3>Editar Cliente</h3>
                </div>

                <?php if (isset($_SESSION['mensaje_exito'])): ?>
                    <div class="mensaje-exito"><?= $_SESSION['mensaje_exito'] ?></div>
                    <?php unset($_SESSION['mensaje_exito']); ?>
                <?php endif; ?>

                <?php if (!empty($errores)): ?>
                    <div class="error">
                        <?php foreach ($errores as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($cliente['id']) ?>">

                    <label>Nombre:</label>
                    <input type="text" name="nombre" value="<?= htmlspecialchars($cliente['nombre']) ?>" required>

                    <label>Correo:</label>
                    <input type="email" name="correo" value="<?= htmlspecialchars($cliente['correo']) ?>" required>

                    <label>Ubicación:</label>
                    <input type="text" name="ubicacion" value="<?= htmlspecialchars($cliente['ubicacion']) ?>" required>

                    <label>RIF:</label>
                    <input type="text" name="rif" value="<?= htmlspecialchars($cliente['rif']) ?>" required>

                    <label>Teléfono:</label>
                    <input type="tel" name="telefono" value="<?= htmlspecialchars($cliente['telefono']) ?>" required>

                    <label>Número de Equipos:</label>
                    <input type="number" name="n_equipos" value="<?= htmlspecialchars($cliente['n_equipos']) ?>" min="1" required>

                    <button type="submit" name="actualizar">Actualizar Cliente</button>
                    <a href="tabla_clientes.php" class="btn">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
    </div>
</body>
</html>