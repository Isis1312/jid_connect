<?php
session_start();
require_once 'permisos.php';
require_once 'conexion.php';

$conexion = obtenerConexion();
// Verificar permisos
if (!tienePermiso('reportes', 'puede_ver')) {
    header("Location: error.php");
    exit;
}

// Obtener servicios para reporte
$servicios = [];
if ($_SESSION['id_cargo'] == 1) {
    $sql = "SELECT s.id, c.nombre as cliente, s.fecha, s.hora, s.descripcion, s.estado 
            FROM servicios s 
            JOIN clientes c ON s.cliente_id = c.id 
            ORDER BY s.fecha DESC, s.hora DESC";
} else {
    $sql = "SELECT s.id, c.nombre as cliente, s.fecha, s.hora, s.descripcion, s.estado 
            FROM servicios s 
            JOIN clientes c ON s.cliente_id = c.id 
            WHERE s.usuario_id = {$_SESSION['id']}
            ORDER BY s.fecha DESC, s.hora DESC";
}

$result = $conexion->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $servicios[] = $row;
    }
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - JID Connect</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
      <div class="menu-container">
        <?php include('menu.php'); ?> 
    </div>
    
    <div class="main-content">
    <main class="main">
        <div class="container">
            <div class="card">
                <h2 class="card-title">Generar Reportes</h2>
                
                <div class="table-responsive">
                    <table class="table">
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
                                        <?php 
                                        $estadoClase = str_replace('_', '-', $servicio['estado']);
                                        echo "<span class='badge badge-$estadoClase'>" . 
                                             ucfirst(htmlspecialchars($servicio['estado'])) . 
                                             "</span>";
                                        ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="agenda.php" style="display: inline;">
                                            <input type="hidden" name="servicio_id" value="<?= $servicio['id'] ?>">
                                            <button type="submit" name="generar_pdf" class="btn btn-primary">Generar PDF</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    </div>

   
</body>

</html>