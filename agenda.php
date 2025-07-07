<?php

require_once 'conexion.php';
require_once 'permisos.php';
require_once 'enviar_correo.php';
require_once 'libreria/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//  VERIFICACIÓN DE PERMISOS
if (!isset($_SESSION['id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

verificarPermisos('agenda', 'puede_ver');

// CONFIGURACIÓN DE MENSAJES
$error = '';
$success = '';
$warning = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conexion = obtenerConexion(); 
    
    if (isset($_POST['agendar'])) {
        if (!tienePermiso('agenda', 'puede_crear')) {
            $error = 'No tienes permisos para agendar servicios';
        } else {
    
            $cliente_id = (int)$_POST['cliente_id'];
            $fecha = $conexion->real_escape_string($_POST['fecha']);
            $hora = $conexion->real_escape_string($_POST['hora']);
            $descripcion = $conexion->real_escape_string($_POST['descripcion']);
            
            if (empty($cliente_id) || empty($fecha) || empty($hora) || empty($descripcion)) {
                $error = 'Todos los campos son obligatorios';
            } elseif ($fecha < date('Y-m-d')) {
                $error = 'La fecha debe ser igual o posterior a hoy';
            } else {
                // Validar rango horario (9AM a 4PM)
                $hora_minutos = (int)substr($hora, 0, 2) * 60 + (int)substr($hora, 3, 2);
                if ($hora_minutos < 540 || $hora_minutos > 960) { // 540 minutos = 9AM, 960 minutos = 4PM
                    $error = 'La hora debe estar entre 9:00 AM y 4:00 PM';
                } else {
                    try {
                        // Insertar servicio
                        $sql = "INSERT INTO servicios (cliente_id, usuario_id, fecha, hora, descripcion, estado) 
                                VALUES (?, ?, ?, ?, ?, 'pendiente')";
                        $stmt = $conexion->prepare($sql);
                        
                        if ($stmt) {
                            $stmt->bind_param("issss", $cliente_id, $_SESSION['id'], $fecha, $hora, $descripcion);
                            
                            if ($stmt->execute()) {
                                $servicio_id = $conexion->insert_id;
                                
                                // Obtener datos del cliente para el correo
                                $sql_cliente = "SELECT nombre, correo FROM clientes WHERE id = ?";
                                $stmt_cliente = $conexion->prepare($sql_cliente);
                                $stmt_cliente->bind_param("i", $cliente_id);
                                $stmt_cliente->execute();
                                $result = $stmt_cliente->get_result();
                                
                                if ($result->num_rows > 0) {
                                    $cliente = $result->fetch_assoc();
                                    
                                    // Enviar correo de confirmación
                                    $resultadoCorreo = enviarCorreoConfirmacion(
                                        $cliente['correo'],
                                        $cliente['nombre'],
                                        $servicio_id,
                                        $fecha,
                                        $hora,
                                        $descripcion,
                                        $_SESSION['nombre'] ?? 'Usuario'
                                    );
                                    
                                    if ($resultadoCorreo['status']) {
                                        $success = 'Servicio agendado y correo enviado correctamente';
                                    } else {
                                        $warning = "Servicio agendado,  error al enviar correo: " . $resultadoCorreo['message'];
                                    }
                                }
                                $stmt_cliente->close();
                            } else {
                                $error = "Error al agendar: " . $stmt->error;
                            }
                            $stmt->close();
                        } else {
                            $error = "Error preparando la consulta: " . $conexion->error;
                        }
                    } catch (Exception $e) {
                        $error = "Error al procesar el servicio: " . $e->getMessage();
                    }
                }
            }
        }
    }
    elseif (isset($_POST['cambiar_estado'])) {
        // Verificar permiso para editar
        if (!tienePermiso('agenda', 'puede_editar')) {
            $error = 'No tienes permisos para cambiar estados';
        } else {
            try {
                $servicio_id = (int)$_POST['servicio_id'];
                $nuevo_estado = $conexion->real_escape_string($_POST['nuevo_estado']);
                
                $sql = "UPDATE servicios SET estado = ? WHERE id = ?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("si", $nuevo_estado, $servicio_id);
                
                if ($stmt->execute()) {
                    $success = 'Estado actualizado correctamente';
                } else {
                    $error = 'Error al actualizar estado';
                }
                $stmt->close();
            } catch (Exception $e) {
                $error = "Error al cambiar estado: " . $e->getMessage();
            }
        }
    }
    elseif (isset($_POST['generar_pdf'])) {
        // Verificar permiso para ver
        if (!tienePermiso('agenda', 'puede_ver')) {
            $error = 'No tienes permisos para generar PDFs';
        } else {
            try {
                $servicio_id = (int)$_POST['servicio_id'];

                // Obtener datos del servicio 
                $sql = "SELECT s.id, c.nombre as cliente, s.fecha, s.hora, s.descripcion, s.estado, 
                               c.ubicacion, c.telefono, c.rif, c.n_equipos
                        FROM servicios s 
                        JOIN clientes c ON s.cliente_id = c.id 
                        WHERE s.id = ?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("i", $servicio_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    die('Servicio no encontrado');
                }

                $servicio = $result->fetch_assoc();
                $stmt->close();

                // Formatear el estado para mostrarlo
                $estado_formateado = ucfirst(str_replace('_', ' ', $servicio['estado']));

                // Formatear fechas
                $fecha_formateada = date('d/m/Y', strtotime($servicio['fecha']));
                $hora_formateada = date('H:i', strtotime($servicio['hora']));
                $fecha_generacion = date('d/m/Y H:i');

                // Generar HTML para el PDF con diseño mejorado
                $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Reporte de Servicio #{$servicio['id']}</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            color: #333;
            background-color: #f8f9fa;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            padding-bottom: 15px;
            border-bottom: 3px solid #2c3e50;
        }
        .header h1 { 
            color: #2c3e50; 
            margin: 0; 
            font-size: 28px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header .subtitle {
            color: #7f8c8d;
            font-size: 18px;
            margin-top: 5px;
        }
        .company-info {
            background: #00CED1;
            color: white;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 25px;
        }
        .section {
            margin-bottom: 25px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .section h2 {
            color: #2c3e50;
            border-bottom: 2px solid #00CED1;
            padding-bottom: 8px;
            margin-top: 0;
            font-size: 20px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .info-item {
            margin-bottom: 12px;
        }
        .info-item label {
            display: block;
            font-weight: bold;
            color: #7f8c8d;
            margin-bottom: 5px;
            font-size: 14px;
        }
        .info-item .value {
            font-size: 16px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 3px solid #00CED1;
        }
        .description-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 3px solid #00CED1;
            margin-top: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
        }
        .status-pendiente {
            background-color: #f39c12;
            color: white;
        }
        .status-en_progreso {
            background-color: #3498db;
            color: white;
        }
        .status-completado {
            background-color: #27ae60;
            color: white;
        }
        .status-cancelado {
            background-color: #e74c3c;
            color: white;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        .logo {
            text-align: center;
            margin-bottom: 15px;
        }
        .logo img {
            max-height: 70px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Servicio Técnico</h1>
        <div class="subtitle">Detalles completos del servicio #{$servicio['id']}</div>
    </div>

    <div class="company-info">
        <strong>JID Connect</strong> | Servicios Tecnológicos Profesionales
    </div>

    <div class="section">
        <h2>Información del Cliente</h2>
        <div class="info-grid">
            <div class="info-item">
                <label>Cliente:</label>
                <div class="value">{$servicio['cliente']}</div>
            </div>
            <div class="info-item">
                <label>RIF:</label>
                <div class="value">{$servicio['rif']}</div>
            </div>
            <div class="info-item">
                <label>Ubicación:</label>
                <div class="value">{$servicio['ubicacion']}</div>
            </div>
            <div class="info-item">
                <label>Teléfono:</label>
                <div class="value">{$servicio['telefono']}</div>
            </div>
            <div class="info-item">
                <label>N° de Equipos:</label>
                <div class="value">{$servicio['n_equipos']}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Detalles del Servicio</h2>
        <div class="info-grid">
            <div class="info-item">
                <label>Fecha:</label>
                <div class="value">{$fecha_formateada}</div>
            </div>
            <div class="info-item">
                <label>Hora:</label>
                <div class="value">{$hora_formateada}</div>
            </div>
            <div class="info-item">
                <label>Estado:</label>
                <div class="value">
                    <span class="status-badge status-{$servicio['estado']}">
                        {$estado_formateado}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Descripción del Servicio</h2>
        <div class="description-box">
            {$servicio['descripcion']}
        </div>
    </div>

    <div class="footer">
        <p>Documento generado el {$fecha_generacion} | JID Connect - Todos los derechos reservados</p>
    </div>
</body>
</html>
HTML;

                // Configurar y generar PDF
                $options = new Options();
                $options->set('isRemoteEnabled', true);
                $options->set('isHtml5ParserEnabled', true);
                $options->set('defaultFont', 'Arial');
                $dompdf = new Dompdf($options);

                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                // Enviar el PDF al navegador
                $dompdf->stream("reporte_servicio_{$servicio['id']}.pdf", ["Attachment" => true]);
                exit;

            } catch (Exception $e) {
                $error = "Error al generar PDF: " . $e->getMessage();
            }
        }
    }
}


$conexion = obtenerConexion(); // Nueva conexión para las consultas

try {
    // Obtener lista de clientes
    $clientes = [];
    $sql_clientes = "SELECT id, nombre FROM clientes ORDER BY nombre";
    $result_clientes = $conexion->query($sql_clientes);
    if ($result_clientes && $result_clientes->num_rows > 0) {
        while ($row = $result_clientes->fetch_assoc()) {
            $clientes[] = $row;
        }
    }

    // Obtener servicios agendados
    $servicios = [];

$sql_servicios = "SELECT s.id, c.nombre as cliente, s.fecha, s.hora, s.descripcion, s.estado 
                 FROM servicios s 
                 JOIN clientes c ON s.cliente_id = c.id 
                 ORDER BY s.fecha DESC, s.hora DESC";

$result_servicios = $conexion->query($sql_servicios);

if ($result_servicios && $result_servicios->num_rows > 0) {
    while ($row = $result_servicios->fetch_assoc()) {
        $servicios[] = $row;
    }
}

} catch (Exception $e) {
    $error = "Error al obtener datos: " . $e->getMessage();
}


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda de Servicios - JID Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/agenda.css">
</head>
<body>
    <div class="menu-container">
        <?php include('menu.php'); ?> 
    </div>
    
    <div class="main-content">
        <div class="container">
        <div class="main-content">
            <div class="content-wrapper">
            <!-- Mensajes de estado -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($warning)): ?>
                <div class="alert alert-warning alert-dismissible fade show">
                    <?= htmlspecialchars($warning) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (tienePermiso('agenda', 'puede_crear')): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="h5 mb-0"><i class="bi bi-calendar-plus"></i> Agendar Nuevo Servicio</h2>
                </div>
                <div class="card-body">
                    <form method="POST" id="formAgendar">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="cliente_id" class="form-label">Cliente:</label>
                                <select id="cliente_id" name="cliente_id" class="form-select" required>
                                    <option value="">Seleccione un cliente</option>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?= htmlspecialchars($cliente['id']) ?>">
                                            <?= htmlspecialchars($cliente['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="fecha" class="form-label">Fecha:</label>
                                <input type="date" id="fecha" name="fecha" class="form-control" 
                                       min="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="hora" class="form-label">Hora:</label>
                                <input type="time" id="hora" name="hora" class="form-control" 
                                       min="09:00" max="16:00" required>
                            </div>
                            <div class="col-12">
                                <label for="descripcion" class="form-label">Descripción:</label>
                                <textarea id="descripcion" name="descripcion" class="form-control" 
                                          rows="3" required></textarea>
                            </div>
                            <div class="col-12 text-center">
                                <button type="submit" name="agendar" class="btn btn-primary btn-lg">
                                    <i class="bi bi-send-check"></i> Agendar Servicio
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
            
            <!-- TABLA -->
            <div class="card">
                <div class="card-header">
                    <h2 class="h5 mb-0"><i class="bi bi-list-task"></i> Servicios Agendados</h2>
                </div>
                <div class="card-body">
                    <?php if (count($servicios) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Fecha</th>
                                        <th>Hora</th>
                                        <th>Descripción</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($servicios as $servicio): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($servicio['id']) ?></td>
                                            <td><?= htmlspecialchars($servicio['cliente']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($servicio['fecha'])) ?></td>
                                            <td><?= date('H:i', strtotime($servicio['hora'])) ?></td>
                                            <td><?= htmlspecialchars($servicio['descripcion']) ?></td>
                                            
                                            <td>
                                                <?php if (tienePermiso('agenda', 'puede_editar')): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="servicio_id" value="<?= $servicio['id'] ?>">
                                                    <select name="nuevo_estado" 
                                                            class="estado-select estado-<?= str_replace(' ', '_', strtolower($servicio['estado'])) ?>" 
                                                            onchange="this.form.submit()"
                                                            data-estado="<?= $servicio['estado'] ?>">
                                                        <option value="pendiente" <?= $servicio['estado'] == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                                        <option value="en_progreso" <?= $servicio['estado'] == 'en_progreso' ? 'selected' : '' ?>>En progreso</option>
                                                        <option value="completado" <?= $servicio['estado'] == 'completado' ? 'selected' : '' ?>>Completado</option>
                                                        <option value="cancelado" <?= $servicio['estado'] == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                                    </select>
                                                    <input type="hidden" name="cambiar_estado" value="1">
                                                </form>
                                                <?php else: ?>
                                                    <span class="badge bg-<?= 
                                                        $servicio['estado'] == 'pendiente' ? 'warning' : 
                                                        ($servicio['estado'] == 'en_progreso' ? 'info' : 
                                                        ($servicio['estado'] == 'completado' ? 'success' : 'danger')) 
                                                    ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $servicio['estado'])) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="action-buttons">
                                                <!-- Botón PDF -->
                                                <form method="POST" class="d-inline-block">
                                                    <input type="hidden" name="servicio_id" value="<?= $servicio['id'] ?>">
                                                    <button type="submit" name="generar_pdf" class="btn-action btn-pdf" title="Generar PDF">
                                                        <i class="bi bi-file-earmark-pdf"></i>
                                                    </button>
                                                </form>
                                                
                                                <!-- Botón Editar -->
                                                <?php if (tienePermiso('agenda', 'puede_editar')): ?>
                                                    <a href="editar_servicio.php?id=<?= $servicio['id'] ?>" class="btn-action btn-edit" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <!-- Botón Eliminar -->
                                                <?php if (tienePermiso('agenda', 'puede_eliminar')): ?>
                                                    <a href="eliminar_servicio.php?id=<?= $servicio['id'] ?>" class="btn-action btn-delete" title="Eliminar"
                                                    onclick="return confirm('¿Está seguro de eliminar este servicio?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center mb-0">No hay servicios agendados</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Validación de fecha y hora en el cliente
    document.getElementById('formAgendar')?.addEventListener('submit', function(e) {
        const fecha = document.getElementById('fecha').value;
        const hora = document.getElementById('hora').value;
        const hoy = new Date().toISOString().split('T')[0];
        
        // Validar fecha
        if (fecha < hoy) {
            alert('La fecha debe ser igual o posterior a hoy');
            e.preventDefault();
            return;
        }
        
        // Validar hora (entre 9:00 y 16:00)
        const [horas, minutos] = hora.split(':').map(Number);
        const totalMinutos = horas * 60 + minutos;
        
        if (totalMinutos < 540 || totalMinutos > 960) { // 9:00 AM = 540 min, 4:00 PM = 960 min
            alert('La hora debe estar entre 9:00 AM y 4:00 PM');
            e.preventDefault();
        }
    });

    // Cierre automático de alertas después de 5 segundos
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            new bootstrap.Alert(alert).close();
        });
    }, 5000);
    </script>
</body>
</html>