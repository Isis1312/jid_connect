<?php
session_start();
require_once 'conexion.php';
require_once 'permisos.php';
$conexion = obtenerConexion();

// Verificar autenticación
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

// Verificar permisos para acceder a entrevistas
if (!tienePermiso('informe', 'ver_todo')) {
    header("Location: error.php");
    exit;
}

// Función para generar código de entrevista secuencial
function generarCodigoEntrevista($conexion) {
    $sql = "SELECT MAX(CAST(SUBSTRING(codigo_entrevista, 3) AS UNSIGNED)) as max_code FROM entrevistas";
    $result = mysqli_query($conexion, $sql);
    $row = mysqli_fetch_assoc($result);
    $next_num = ($row['max_code'] ?? 0) + 1;
    return 'EN' . str_pad($next_num, 5, '0', STR_PAD_LEFT);
}

// Procesar formulario de entrevista 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && tienePermiso('entrevistas', 'puede_crear')) {
    $id_cliente = $conexion->real_escape_string($_POST['cliente']);
    $marca_equipo = $conexion->real_escape_string($_POST['marca_equipo']);
    $descripcion_problema = $conexion->real_escape_string($_POST['descripcion_problema']);
    $tiene_garantia = $conexion->real_escape_string($_POST['tiene_garantia']);
    $numero_garantia = $tiene_garantia === 'Si' ? $conexion->real_escape_string($_POST['numero_garantia']) : null;
    $necesita_repuesto = $conexion->real_escape_string($_POST['necesita_repuesto']);
    $detalles_repuesto = $necesita_repuesto === 'Si' ? $conexion->real_escape_string($_POST['detalles_repuesto']) : null;
    $fecha_entrevista = $conexion->real_escape_string($_POST['fecha_entrevista']);
    $ejecutivo = $conexion->real_escape_string($_POST['ejecutivo']);
    $codigo_entrevista = generarCodigoEntrevista($conexion);
    
    // Validar  fecha 
    $hoy = date('Y-m-d');
    if (date('Y-m-d', strtotime($fecha_entrevista)) < $hoy) {
        $_SESSION['mensaje_error'] = 'La fecha de la entrevista no puede ser anterior al día actual';
        header("Location: entrevistas.php");
        exit;
    }
    
    $sql = "INSERT INTO entrevistas (id_cliente, marca_equipo, descripcion_problema, tiene_garantia, numero_garantia, necesita_repuesto, detalles_repuesto, fecha_entrevista, ejecutivo, codigo_entrevista) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("isssssssss", $id_cliente, $marca_equipo, $descripcion_problema, $tiene_garantia, $numero_garantia, $necesita_repuesto, $detalles_repuesto, $fecha_entrevista, $ejecutivo, $codigo_entrevista);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje_exito'] = 'Entrevista registrada correctamente con código: ' . $codigo_entrevista;
    } else {
        $_SESSION['mensaje_error'] = 'Error al registrar la entrevista';
    }
    $stmt->close();
    header("Location: entrevistas.php");
    exit;
}

// Obtener lista de clientes activos para el select
$sql_clientes = "SELECT id, nombre, rif, ubicacion FROM clientes WHERE estado = 'activo'";
$resultado_clientes = mysqli_query($conexion, $sql_clientes);

// Obtener lista de entrevistas
$sql_entrevistas = "SELECT e.id_entrevista, e.codigo_entrevista, c.nombre, e.fecha_entrevista 
                    FROM entrevistas e
                    JOIN clientes c ON e.id_cliente = c.id
                    ORDER BY e.fecha_entrevista DESC";
$resultado_entrevistas = mysqli_query($conexion, $sql_entrevistas);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Entrevistas</title>
    <link rel="stylesheet" href="css/entrevista.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
   <div class="menu-container">
        <?php include('menu.php'); ?> 
    </div>
    
    <div class="main-content">
        <?php if (isset($_SESSION['mensaje_exito'])): ?>
            <div class="mensaje-flash mensaje-exito">
                <?= $_SESSION['mensaje_exito'] ?>
                <?php unset($_SESSION['mensaje_exito']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['mensaje_error'])): ?>
            <div class="mensaje-flash mensaje-error">
                <?= $_SESSION['mensaje_error'] ?>
                <?php unset($_SESSION['mensaje_error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (tienePermiso('entrevistas', 'puede_crear')): ?>
        <h2>Registrar Nueva Entrevista</h2>
        
        <form class="form-entrevista" method="POST" action="">
            <div class="form-group">
                <label for="cliente">Cliente:</label>
                <select id="cliente" name="cliente" required>
                    <option value="">Seleccione un cliente</option>
                    <?php while ($cliente = mysqli_fetch_assoc($resultado_clientes)): ?>
                        <option value="<?= $cliente['id'] ?>">
                            <?= htmlspecialchars($cliente['nombre']) ?> - <?= htmlspecialchars($cliente['rif']) ?> - <?= htmlspecialchars($cliente['ubicacion']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="marca_equipo">Marca del Equipo:</label>
                <input type="text" id="marca_equipo" name="marca_equipo" required>
            </div>
            
            <div class="form-group">
                <label for="descripcion_problema">Descripción del Problema:</label>
                <textarea id="descripcion_problema" name="descripcion_problema" rows="4" required></textarea>
            </div>
            
            <div class="form-group">
                <label>¿Cuenta con garantía?</label>
                <div class="radio-group">
                    <label><input type="radio" name="tiene_garantia" value="Si" required> Sí</label>
                    <label><input type="radio" name="tiene_garantia" value="No"> No</label>
                </div>
                <div id="garantia-container" style="display: none;">
                    <label for="numero_garantia">Número de Garantía:</label>
                    <input type="text" id="numero_garantia" name="numero_garantia" pattern="[A-Za-z0-9]+" title="Solo letras y números permitidos">
                </div>
            </div>
            
            <div class="form-group">
                <label>¿Necesita repuesto?</label>
                <div class="radio-group">
                    <label><input type="radio" name="necesita_repuesto" value="Si" required> Sí</label>
                    <label><input type="radio" name="necesita_repuesto" value="No"> No</label>
                </div>
                <div id="repuesto-container" style="display: none;">
                    <label for="detalles_repuesto">Detalles del repuesto:</label>
                    <input type="text" id="detalles_repuesto" name="detalles_repuesto">
                    <button type="button" class="btn-notificar" onclick="alert('Notificación de repuesto enviada a proveedor')">Notificar a Proveedor</button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="fecha_entrevista">Fecha de Entrevista:</label>
                <input type="date" id="fecha_entrevista" name="fecha_entrevista" min="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="ejecutivo">Número del Ejecutivo:</label>
                <input type="text" id="ejecutivo" name="ejecutivo" required>
            </div>

            <button type="submit" class="btn-submit">Registrar</button>
        </form>
        <?php endif; ?>
        
        <h2>Listado de Entrevistas</h2>
        
        <table class="tabla-entrevistas">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Empresa</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($resultado_entrevistas) > 0): ?>
                    <?php while ($entrevista = mysqli_fetch_assoc($resultado_entrevistas)): ?>
                        <tr>
                            <td><?= htmlspecialchars($entrevista['codigo_entrevista']) ?></td>
                            <td><?= htmlspecialchars($entrevista['nombre']) ?></td>
                            <td><?= date('d/m/Y', strtotime($entrevista['fecha_entrevista'])) ?></td>
                            <td>
                                <div class="contenedor-botones">
                                    <a href="ver_entrevista.php?id=<?= $entrevista['id_entrevista'] ?>" class="boton-accion boton-ver">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                    <?php if (tienePermiso('entrevistas', 'puede_eliminar')): ?>
                                    <a href="eliminar_entrevista.php?id=<?= $entrevista['id_entrevista'] ?>" class="boton-accion boton-eliminar" onclick="return confirm('¿Está seguro de eliminar esta entrevista?')">
                                        <i class="fas fa-trash-alt"></i> Eliminar
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No hay entrevistas registradas</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <script>
        // Mostrar/ocultar campo de número de garantía
        document.querySelectorAll('input[name="tiene_garantia"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('garantia-container').style.display = 
                    this.value === 'Si' ? 'block' : 'none';
            });
        });
        
        // Mostrar/ocultar campo de detalles de repuesto
        document.querySelectorAll('input[name="necesita_repuesto"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('repuesto-container').style.display = 
                    this.value === 'Si' ? 'block' : 'none';
            });
        });
    </script>
</body>
</html>