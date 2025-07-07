<?php

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
    $correo = $conexion->real_escape_string(trim($_POST['correo']));
    $telefono = $conexion->real_escape_string(trim($_POST['telefono']));
    $n_equipos = intval($_POST['n_equipos']);

    // Validaciones 
    if (empty($correo)) $errores[] = "El correo es obligatorio";
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = "Correo electrónico inválido";
    if (empty($telefono)) $errores[] = "El teléfono es obligatorio";
    if ($n_equipos <= 0) $errores[] = "El número de equipos debe ser mayor a cero";

    if (empty($errores)) {
        $sql = "UPDATE clientes SET 
                correo = ?, 
                telefono = ?, 
                n_equipos = ? 
                WHERE id = ?";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssii", 
            $correo,
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
        .campo-no-editable {
            background-color: #f0f0f0;
            padding: 8px 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
            margin-bottom: 10px;
        }
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
                    <div class="campo-no-editable"><?= htmlspecialchars($cliente['nombre']) ?></div>

                    <label>Ubicación:</label>
                    <div class="campo-no-editable"><?= htmlspecialchars($cliente['ubicacion']) ?></div>

                    <label>RIF:</label>
                    <div class="campo-no-editable"><?= htmlspecialchars($cliente['rif']) ?></div>

                    <label>Correo:</label>
                    <input type="email" name="correo" value="<?= htmlspecialchars($cliente['correo']) ?>" required>

                    <label>Teléfono:</label>
                    <input type="tel" name="telefono" value="<?= htmlspecialchars($cliente['telefono']) ?>" required>

                    <label>Número de Equipos:</label>
                    <input type="number" name="n_equipos" value="<?= htmlspecialchars($cliente['n_equipos']) ?>" min="1" required>

                    <button type="submit" name="actualizar">Actualizar Cliente</button>

                    <div class="btn-cancelar">
                       <a href="tabla_clientes.php" >Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
</body>
</html>