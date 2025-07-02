<?php
// Iniciar sesión y verificar autenticación
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

require_once 'conexion.php';
require_once 'permisos.php';

$conexion = obtenerConexion();

// Verificar permisos para acceder a informes
if (!tienePermiso('informe', 'ver_todo')) {
    $_SESSION['mensaje'] = "No tienes permisos para acceder a esta sección";
    $_SESSION['tipo_mensaje'] = "error";
    header("Location: menu.php");
    exit;
}

// Función para generar número de informe
function generarNumeroInforme($conexion) {
    $query = "SELECT MAX(id) as max_id FROM informes";
    $result = $conexion->query($query);
    $row = $result->fetch_assoc();
    $next_id = $row['max_id'] ? $row['max_id'] + 1 : 1;
    return 'IN' . str_pad($next_id, 5, '0', STR_PAD_LEFT);
}

// Procesar formulario de nuevo informe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_informe'])) {
    if (!tienePermiso('informe', 'puede_crear')) {
        $_SESSION['mensaje'] = "No tienes permisos para crear informes";
        $_SESSION['tipo_mensaje'] = "error";
        header("Location: informes.php");
        exit;
    }

    $cliente_id = $conexion->real_escape_string($_POST['cliente_id']);
    $tecnico_nombre = $conexion->real_escape_string($_POST['tecnico_nombre']);
    $problema_reportado = $conexion->real_escape_string($_POST['problema_reportado']);
    $detalles_servicio = $conexion->real_escape_string($_POST['detalles_servicio']);
    $estado_resolucion = $conexion->real_escape_string($_POST['estado_resolucion']);
    $recomendaciones = $conexion->real_escape_string($_POST['recomendaciones']);
    $fecha_visita = $conexion->real_escape_string($_POST['fecha_visita']);
    $numero_informe = generarNumeroInforme($conexion);

    $stmt = $conexion->prepare("INSERT INTO informes (cliente_id, numero_informe, tecnico_nombre, problema_reportado, detalles_servicio, estado_resolucion, recomendaciones, fecha_visita) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssss", $cliente_id, $numero_informe, $tecnico_nombre, $problema_reportado, $detalles_servicio, $estado_resolucion, $recomendaciones, $fecha_visita);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "Informe creado correctamente";
        $_SESSION['tipo_mensaje'] = "success";
    } else {
        $_SESSION['mensaje'] = "Error al crear el informe: " . $conexion->error;
        $_SESSION['tipo_mensaje'] = "error";
    }
    $stmt->close();
}

// Obtener todos los informes para la tabla
$query_informes = "SELECT i.id, i.numero_informe, c.nombre as cliente_nombre, i.fecha_visita, i.estado_resolucion 
                   FROM informes i 
                   JOIN clientes c ON i.cliente_id = c.id 
                   ORDER BY i.fecha_creacion DESC";
$result_informes = $conexion->query($query_informes);

// Obtener clientes activos
$query_clientes = "SELECT id, nombre, rif, ubicacion, n_equipos FROM clientes WHERE estado = 'activo' ORDER BY nombre";
$result_clientes = $conexion->query($query_clientes);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Informes</title>
    <link rel="stylesheet" href="css/informe.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="menu-container">
        <?php include('menu.php'); ?>
    </div>

    <div class="main-content">
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?>">
                <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); unset($_SESSION['tipo_mensaje']); ?>
            </div>
        <?php endif; ?>

        <?php if (tienePermiso('informe', 'puede_crear')): ?>
        <div class="form-container">
            <h2><i class="fas fa-plus-circle"></i> Nuevo Informe</h2>
            <form id="formInforme" method="POST" class="form-entrevista">
                <div class="form-group">
                    <label for="cliente_id"><i class="fas fa-user"></i> Cliente:</label>
                    <select name="cliente_id" id="cliente_id" required>
                        <option value="">Seleccione un cliente</option>
                        <?php while ($cliente = $result_clientes->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($cliente['id']); ?>" 
                                    data-rif="<?php echo htmlspecialchars($cliente['rif']); ?>"
                                    data-ubicacion="<?php echo htmlspecialchars($cliente['ubicacion']); ?>"
                                    data-equipos="<?php echo htmlspecialchars($cliente['n_equipos']); ?>">
                                <?php echo htmlspecialchars($cliente['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="rif"><i class="fas fa-id-card"></i> RIF:</label>
                        <input type="text" id="rif" readonly>
                    </div>
                    <div class="form-group">
                        <label for="ubicacion"><i class="fas fa-map-marker-alt"></i> Ubicación:</label>
                        <input type="text" id="ubicacion" readonly>
                    </div>
                    <div class="form-group">
                        <label for="n_equipos"><i class="fas fa-desktop"></i> N° de Equipos:</label>
                        <input type="text" id="n_equipos" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_visita"><i class="fas fa-calendar-alt"></i> Fecha de Visita:</label>
                        <input type="date" name="fecha_visita" id="fecha_visita" required>
                    </div>
                    <div class="form-group">
                        <label for="tecnico_nombre"><i class="fas fa-user-cog"></i> Técnico:</label>
                        <input type="text" name="tecnico_nombre" id="tecnico_nombre" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="problema_reportado"><i class="fas fa-exclamation-triangle"></i> Problema Reportado:</label>
                    <textarea name="problema_reportado" id="problema_reportado" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label for="detalles_servicio"><i class="fas fa-tools"></i> Detalles del Servicio:</label>
                    <textarea name="detalles_servicio" id="detalles_servicio" rows="3" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="estado_resolucion"><i class="fas fa-check-circle"></i> Estado:</label>
                        <select name="estado_resolucion" id="estado_resolucion" required>
                            <option value="">Seleccione...</option>
                            <option value="Reparado">Reparado</option>
                            <option value="No reparado">No reparado</option>
                            <option value="Solucion temporal">Solución temporal</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="recomendaciones"><i class="fas fa-lightbulb"></i> Recomendaciones:</label>
                    <textarea name="recomendaciones" id="recomendaciones" rows="3"></textarea>
                </div>

                <div class="btn-submit-container">
                    <button type="submit" name="guardar_informe" class="btn-submit">
                        <i class="fas fa-save"></i> Guardar Informe
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="table-container">
            <h2><i class="fas fa-list"></i> Listado de Informes</h2>
            <div class="table-responsive">
                <table class="tabla-entrevistas">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> N° Informe</th>
                            <th><i class="fas fa-building"></i> Cliente</th>
                            <th><i class="fas fa-calendar-day"></i> Fecha Visita</th>
                            <th><i class="fas fa-check-double"></i> Estado</th>
                            <th><i class="fas fa-cogs"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($informe = $result_informes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($informe['numero_informe']); ?></td>
                            <td><?php echo htmlspecialchars($informe['cliente_nombre']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($informe['fecha_visita'])); ?></td>
                            <td><span class="estado-badge estado-<?php echo strtolower(str_replace(' ', '-', $informe['estado_resolucion'])); ?>">
                                <?php echo htmlspecialchars($informe['estado_resolucion']); ?>
                            </span></td>
                            <td>
                                <div class="contenedor-botones">
                                    <a href="ver_informe.php?id=<?php echo $informe['id']; ?>" class="boton-accion boton-ver" title="Ver informe">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
            $(document).ready(function() {
                // Cargar datos del cliente cuando se selecciona
                $('#cliente_id').change(function() {
                    var selectedOption = $(this).find('option:selected');
                    $('#rif').val(selectedOption.data('rif'));
                    $('#ubicacion').val(selectedOption.data('ubicacion'));
                    $('#n_equipos').val(selectedOption.data('equipos'));
                });

                // Configurar fecha de visita como hoy por defecto
                var today = new Date().toISOString().split('T')[0];
                $('#fecha_visita').val(today);

                // Animación para los mensajes de alerta
                $('.alert').fadeIn().delay(3000).fadeOut();
            });
        </script>
    </div>
</body>
</html>