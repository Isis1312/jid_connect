<?php
session_start();
require_once 'permisos.php';
$conexion = obtenerConexion();
require_once 'libreria/dompdf/autoload.inc.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

// Verificar permisos para el módulo de entrevistas
verificarPermisos('informe', 'ver_todo');

include("conexion.php");

// Validar el ID de la entrevista
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: entrevistas.php");
    exit;
}

use Dompdf\Dompdf;
use Dompdf\Options;

// Procesar generación de PDF si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_pdf'])) {
    $id_entrevista = $conexion->real_escape_string($_GET['id']);
    $sql = "SELECT e.*, c.nombre as nombre_cliente, c.rif, c.ubicacion, c.telefono, c.correo, c.n_equipos
            FROM entrevistas e
            JOIN clientes c ON e.id_cliente = c.id
            WHERE e.id_entrevista = ?";
            
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_entrevista);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $entrevista = $resultado->fetch_assoc();
    $stmt->close();

    // Formatear fechas
    $fecha_entrevista = date('d/m/Y H:i', strtotime($entrevista['fecha_entrevista']));
    $fecha_registro = date('d/m/Y H:i', strtotime($entrevista['fecha_registro']));
    $fecha_generacion = date('d/m/Y H:i');

    // Generar HTML para el PDF con nuevo estilo
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Entrevista Técnica #{$entrevista['id_entrevista']}</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            color: #333;
         
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
            background: #3498db;
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
        <h1>Entrevista Técnica</h1>
        <div class="subtitle">Detalles completos de la entrevista #{$entrevista['id_entrevista']}</div>
    </div>

    <div class="company-info">
        <strong>JID Connect</strong> | Servicios Tecnológicos Profesionales
    </div>

    <div class="section">
        <h2>Información del Cliente</h2>
        <div class="info-grid">
            <div class="info-item">
                <label>Cliente:</label>
                <div class="value">{$entrevista['nombre_cliente']}</div>
            </div>
            <div class="info-item">
                <label>RIF:</label>
                <div class="value">{$entrevista['rif']}</div>
            </div>
            <div class="info-item">
                <label>Ubicación:</label>
                <div class="value">{$entrevista['ubicacion']}</div>
            </div>
            <div class="info-item">
                <label>Teléfono:</label>
                <div class="value">{$entrevista['telefono']}</div>
            </div>
            <div class="info-item">
                <label>Correo:</label>
                <div class="value">{$entrevista['correo']}</div>
            </div>
            <div class="info-item">
                <label>N° de Equipos:</label>
                <div class="value">{$entrevista['n_equipos']}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Detalles de la Entrevista</h2>
        <div class="info-grid">
            <div class="info-item">
                <label>Código:</label>
                <div class="value">{$entrevista['codigo_entrevista']}</div>
            </div>
            <div class="info-item">
                <label>Fecha de Entrevista:</label>
                <div class="value">{$fecha_entrevista}</div>
            </div>
            <div class="info-item">
                <label>Ejecutivo:</label>
                <div class="value">{$entrevista['ejecutivo']}</div>
            </div>
            <div class="info-item">
                <label>Fecha de Registro:</label>
                <div class="value">{$fecha_registro}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Información Técnica</h2>
        <div class="info-grid">
            <div class="info-item">
                <label>Marca del Equipo:</label>
                <div class="value">{$entrevista['marca_equipo']}</div>
            </div>
            <div class="info-item">
                <label>Tiene Garantía:</label>
                <div class="value">{$entrevista['tiene_garantia']}</div>
            </div>
            <div class="info-item">
                <label>Necesita Repuesto:</label>
                <div class="value">{$entrevista['necesita_repuesto']}</div>
            </div>
        </div>
        
        <div class="info-item">
            <label>Descripción del Problema:</label>
            <div class="description-box">{$entrevista['descripcion_problema']}</div>
        </div>
HTML;

    if ($entrevista['tiene_garantia'] === 'Si' && !empty($entrevista['numero_garantia'])) {
        $html .= <<<HTML
        <div class="info-item">
            <label>Número de Garantía:</label>
            <div class="value">{$entrevista['numero_garantia']}</div>
        </div>
HTML;
    }

    if ($entrevista['necesita_repuesto'] === 'Si' && !empty($entrevista['detalles_repuesto'])) {
        $html .= <<<HTML
        <div class="info-item">
            <label>Detalles del Repuesto:</label>
            <div class="description-box">{$entrevista['detalles_repuesto']}</div>
        </div>
HTML;
    }

    $html .= <<<HTML
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
    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Enviar el PDF al navegador
    $dompdf->stream("entrevista_tecnica_{$entrevista['id_entrevista']}.pdf", ["Attachment" => true]);
    exit;
}

// Continuar con el código normal si no se está generando PDF
$id_entrevista = $conexion->real_escape_string($_GET['id']);
$sql = "SELECT e.*, c.nombre as nombre_cliente, c.rif, c.ubicacion, c.telefono, c.correo, c.n_equipos
        FROM entrevistas e
        JOIN clientes c ON e.id_cliente = c.id
        WHERE e.id_entrevista = ?";
        
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_entrevista);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    header("Location: entrevistas.php");
    exit;
}

$entrevista = $resultado->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Entrevista</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/ver_entrevista.css">
    
        
</head>
<body>
   <div class="menu-container">
        <?php include('menu.php'); ?> 
    </div>
    
    <div class="main-content">
        <div class="detalle-entrevista">
            <h2>Detalles de la Entrevista</h2>
            
            <div class="detalle-header">
                <span class="codigo">Código: <?= htmlspecialchars($entrevista['codigo_entrevista']) ?></span>
                <span class="fecha">Fecha: <?= date('d/m/Y H:i', strtotime($entrevista['fecha_entrevista'])) ?></span>
            </div>
            
            <div class="detalle-grid">
                <div class="detalle-item">
                    <h2>Información del Cliente</h2>
                    <p><strong>Nombre:</strong> <?= htmlspecialchars($entrevista['nombre_cliente']) ?></p>
                    <p><strong>RIF:</strong> <?= htmlspecialchars($entrevista['rif']) ?></p>
                    <p><strong>Ubicación:</strong> <?= htmlspecialchars($entrevista['ubicacion']) ?></p>
                    <p><strong>Teléfono:</strong> <?= htmlspecialchars($entrevista['telefono']) ?></p>
                    <p><strong>Correo:</strong> <?= htmlspecialchars($entrevista['correo']) ?></p>
                    <p><strong>N° Equipos:</strong> <?= htmlspecialchars($entrevista['n_equipos']) ?></p>
                </div>
                
                <div class="detalle-item">
                    <h3>Detalles Técnicos</h3>
                    <p><strong>Marca del Equipo:</strong> <?= htmlspecialchars($entrevista['marca_equipo']) ?></p>
                    <p><strong>Descripción del Problema:</strong></p>
                    <div class="descripcion"><?= nl2br(htmlspecialchars($entrevista['descripcion_problema'])) ?></div>
                    <p><strong>¿Tiene garantía?:</strong> <?= htmlspecialchars($entrevista['tiene_garantia']) ?></p>
                    <?php if ($entrevista['tiene_garantia'] === 'Si' && !empty($entrevista['numero_garantia'])): ?>
                        <p><strong>Número de Garantía:</strong> <?= htmlspecialchars($entrevista['numero_garantia']) ?></p>
                    <?php endif; ?>
                    <p><strong>¿Necesita repuesto?:</strong> <?= htmlspecialchars($entrevista['necesita_repuesto']) ?></p>
                    <?php if ($entrevista['necesita_repuesto'] === 'Si'): ?>
                    <p><strong>Detalles del repuesto:</strong></p>
                    <div class="descripcion"><?= nl2br(htmlspecialchars($entrevista['detalles_repuesto'])) ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="detalle-item">
                    <h3>Información de la Entrevista</h3>
                    <p><strong>Ejecutivo:</strong> <?= htmlspecialchars($entrevista['ejecutivo']) ?></p>
                    <p><strong>Fecha de Registro:</strong> <?= date('d/m/Y H:i', strtotime($entrevista['fecha_registro'])) ?></p>
                </div>
            </div>
            
            <div class="botones">
                <a href="entrevistas.php" class="btn-volver">
                    <i class="fas fa-arrow-left"></i> Volver al listado
                </a>
                <form method="POST" class="form-pdf">
                    <button type="submit" name="generar_pdf" class="btn-pdf">
                        <i class="fas fa-file-pdf"></i> Generar PDF
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>