<?php
session_start();
require_once 'conexion.php';
require_once 'permisos.php';
$conexion = obtenerConexion();
// Verificar permisos para agregar clientes
if (!tienePermiso('clientes', 'puede_crear')) {
    header("Location: tabla_clientes.php");
    exit;
}

$mensaje_exito = '';
$mensaje_error = '';

if (isset($_POST['registrar'])) {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $ubicacion = $_POST['ubicacion'];
    $rif = $_POST['rif'];
    $telefono = $_POST['telefono'];
    $n_equipos = $_POST['n_equipos'];

    // Validaciones básicas
    if (empty($nombre) || empty($correo) || empty($ubicacion) || empty($rif) || empty($telefono) || empty($n_equipos)) {
        $mensaje_error = "Todos los campos son obligatorios";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje_error = "El formato del correo electrónico no es válido";
    } elseif (!is_numeric($n_equipos) || $n_equipos < 1) {
        $mensaje_error = "El número de equipos debe ser un valor numérico mayor a cero";
    } else {
        $sql = "INSERT INTO clientes (nombre, correo, ubicacion, rif, telefono, n_equipos) VALUES (?,?,?,?,?,?)";
        $stmt = $conexion->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ssssss", $nombre, $correo, $ubicacion, $rif, $telefono, $n_equipos);
            $resultado = $stmt->execute();

            if ($resultado) {
                $mensaje_exito = "Cliente agregado correctamente";
                // Limpiar campos después de éxito
                $_POST = array();
            } else {
                $mensaje_error = "Error al registrar: " . $conexion->error;
            }
            $stmt->close();
        } else {
            $mensaje_error = "Error al preparar la consulta: " . $conexion->error;
        }
    }
    mysqli_close($conexion);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/estilos.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Cliente</title>
    <style>
        /* Estilos para los mensajes */
        .mensaje-exito {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin: 15px 0;
            border-radius: 5px;
            text-align: center;
        }
        
        .mensaje-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin: 15px 0;
            border-radius: 5px;
            text-align: center;
        }
        
        /* Animación para mensajes */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .mensaje-exito, .mensaje-error {
            animation: fadeIn 0.3s ease-out;
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
                <!-- Mostrar mensajes de éxito/error -->
                <?php if (!empty($mensaje_exito)): ?>
                    <div class="mensaje-exito">
                        <?php echo $mensaje_exito; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($mensaje_error)): ?>
                    <div class="mensaje-error">
                        <?php echo $mensaje_error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="mensaje">
                    <h3>Agregar nuevo cliente</h3>
                </div>
                
                <form action="<?=$_SERVER['PHP_SELF']?>" method="post">
                    <label>Nombre de la empresa:</label>
                    <input type="text" name="nombre" value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>" required><br>
                    
                    <label>Correo:</label>
                    <input type="email" name="correo" value="<?= isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : '' ?>" required><br>
                    
                    <label>Ubicación:</label>
                    <input type="text" name="ubicacion" value="<?= isset($_POST['ubicacion']) ? htmlspecialchars($_POST['ubicacion']) : '' ?>" required><br>
                    
                    <label>RIF:</label>
                    <input type="text" name="rif" value="<?= isset($_POST['rif']) ? htmlspecialchars($_POST['rif']) : '' ?>" required><br>
                    
                    <label>Número de contacto:</label>
                    <input type="tel" name="telefono" value="<?= isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : '' ?>" required><br>
                    
                    <label>Número de equipos en la empresa:</label>
                    <input type="number" name="n_equipos" value="<?= isset($_POST['n_equipos']) ? htmlspecialchars($_POST['n_equipos']) : '' ?>" min="1" required><br>
                    
                    <button type="submit" name="registrar">Registrar</button>
                </form>
            </div>   
        </div>   
    </div>
    </div>
</body>
</html>