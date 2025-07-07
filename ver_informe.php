<?php
session_start();
require_once 'permisos.php';
require_once 'conexion.php';

$conexion = obtenerConexion();
// Verificar autenticación
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

// Verificar permisos para el módulo de informes
verificarPermisos('informe', 'ver_todo');

// Verificar ID de informe
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID de informe no válido');
}

$id = $_GET['id'];

// Procesar generación de PDF si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_pdf'])) {
    require_once 'libreria/dompdf/autoload.inc.php';
    
    // Consulta para obtener los datos del informe
    $query = "SELECT i.*, c.nombre as cliente_nombre, c.rif, c.ubicacion, c.n_equipos
              FROM informes i
              JOIN clientes c ON i.cliente_id = c.id
              WHERE i.id = ?";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $informe = $result->fetch_assoc();
    $stmt->close();

    // Formatear fechas
    $fecha_visita = date('d/m/Y', strtotime($informe['fecha_visita']));
    $fecha_generacion = date('d/m/Y H:i');

    // Generar HTML para el PDF con nuevo estilo
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Informe {$informe['numero_informe']}</title>
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
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Informe Técnico</h1>
        <div class="subtitle">Número: {$informe['numero_informe']}</div>
    </div>

    <div class="company-info">
        <strong>JID Connect</strong> | Servicios Tecnológicos Profesionales
    </div>

    <div class="section">
        <h2>Información del Cliente</h2>
        <div class="info-grid">
            <div class="info-item">
                <label>Cliente:</label>
                <div class="value">{$informe['cliente_nombre']}</div>
            </div>
            <div class="info-item">
                <label>RIF:</label>
                <div class="value">{$informe['rif']}</div>
            </div>
            <div class="info-item">
                <label>Ubicación:</label>
                <div class="value">{$informe['ubicacion']}</div>
            </div>
            <div class="info-item">
                <label>N° de Equipos:</label>
                <div class="value">{$informe['n_equipos']}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Detalles de la Visita</h2>
        <div class="info-grid">
            <div class="info-item">
                <label>Fecha de Visita:</label>
                <div class="value">{$fecha_visita}</div>
            </div>
            <div class="info-item">
                <label>Técnico:</label>
                <div class="value">{$informe['tecnico_nombre']}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Problema Reportado</h2>
        <div class="description-box">{$informe['problema_reportado']}</div>
    </div>

    <div class="section">
        <h2>Detalles del Servicio</h2>
        <div class="description-box">{$informe['detalles_servicio']}</div>
    </div>

    <div class="section">
        <h2>Estado de Resolución</h2>
        <div class="description-box">{$informe['estado_resolucion']}</div>
    </div>

    <div class="section">
        <h2>Recomendaciones</h2>
        <div class="description-box">
HTML;

    $html .= !empty($informe['recomendaciones']) ? $informe['recomendaciones'] : 'Ninguna';

    $html .= <<<HTML
        </div>
    </div>

    <div class="footer">
        <p>Documento generado el {$fecha_generacion} | JID Connect - Todos los derechos reservados</p>
    </div>
</body>
</html>
HTML;

    // Configurar y generar PDF
    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'Arial');
    $dompdf = new \Dompdf\Dompdf($options);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Enviar el PDF al navegador
    $dompdf->stream("informe_{$informe['numero_informe']}.pdf", ["Attachment" => true]);
    exit;
}

// Consulta para obtener los datos del informe (para la vista normal)
$query = "SELECT i.*, c.nombre as cliente_nombre, c.rif, c.ubicacion, c.n_equipos
          FROM informes i
          JOIN clientes c ON i.cliente_id = c.id
          WHERE i.id = ?";

$stmt = $conexion->prepare($query);
if (!$stmt) {
    die('Error al preparar la consulta: ' . $conexion->error);
}

$stmt->bind_param("i", $id);
if (!$stmt->execute()) {
    die('Error al ejecutar la consulta: ' . $stmt->error);
}

$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die('Informe no encontrado');
}

$informe = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe <?php echo htmlspecialchars($informe['numero_informe']); ?></title>
    <link rel="stylesheet" href="css/ver_informe.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="menu-container">
        <?php include('menu.php'); ?> 
    </div>
    
    <div class="main-content">
        <div class="informe-container">
            <h1>Informe: <?php echo htmlspecialchars($informe['numero_informe']); ?></h1>
            
            <div class="informe-section">
                <h2><i class="fas fa-user"></i> Información del Cliente</h2>
                <p><strong>Cliente:</strong> <?php echo htmlspecialchars($informe['cliente_nombre']); ?></p>
                <p><strong>RIF:</strong> <?php echo htmlspecialchars($informe['rif']); ?></p>
                <p><strong>Ubicación:</strong> <?php echo htmlspecialchars($informe['ubicacion']); ?></p>
                <p><strong>N° de Equipos:</strong> <?php echo htmlspecialchars($informe['n_equipos']); ?></p>
            </div>
            
            <div class="informe-section">
                <h2><i class="fas fa-calendar-alt"></i> Detalles de la Visita</h2>
                <p><strong>Fecha de Visita:</strong> <?php echo date('d/m/Y', strtotime($informe['fecha_visita'])); ?></p>
                <p><strong>Técnico:</strong> <?php echo htmlspecialchars($informe['tecnico_nombre']); ?></p>
            </div>
            
            <div class="informe-section">
                <h2><i class="fas fa-exclamation-triangle"></i> Problema Reportado</h2>
                <p><?php echo nl2br(htmlspecialchars($informe['problema_reportado'])); ?></p>
            </div>
            
            <div class="informe-section">
                <h2><i class="fas fa-tools"></i> Detalles del Servicio</h2>
                <p><?php echo nl2br(htmlspecialchars($informe['detalles_servicio'])); ?></p>
            </div>
            
            <div class="informe-section">
                <h2><i class="fas fa-check-circle"></i> Estado de Resolución</h2>
                <p><?php echo htmlspecialchars($informe['estado_resolucion']); ?></p>
            </div>
            
            <div class="informe-section">
                <h2><i class="fas fa-lightbulb"></i> Recomendaciones</h2>
                <p><?php echo !empty($informe['recomendaciones']) ? nl2br(htmlspecialchars($informe['recomendaciones'])) : 'Ninguna'; ?></p>
            </div>
            
            <div class="informe-actions">
                <!-- Botón de PDF visible para todos -->
                <form method="POST">
                    <button type="submit" name="generar_pdf" class="btn-print"><i class="fas fa-file-pdf"></i> Generar PDF</button>
                </form>
                <button onclick="window.history.back()" class="btn-back"><i class="fas fa-arrow-left"></i> Volver</button>
            </div>
        </div>
    </div>
</body>
</html>