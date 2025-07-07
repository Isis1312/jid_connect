<?php
session_start();
require_once 'conexion.php';
require_once 'permisos.php';

$conexion = obtenerConexion();
// Verificar si el usuario está logueado
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

// Verificar permisos para ver clientes
if (!tienePermiso('clientes', 'puede_ver')) {
    header("Location: acceso_denegado.php");
    exit;
}

// Procesar cambio de estado (solo si tiene permiso para editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    if (!tienePermiso('clientes', 'puede_editar')) {
        $_SESSION['mensaje_error'] = 'No tienes permisos para cambiar estados';
        header("Location: tabla_clientes.php");
        exit;
    }

    $cliente_id = $conexion->real_escape_string($_POST['cliente_id']);
    $nuevo_estado = $conexion->real_escape_string($_POST['nuevo_estado']);
    
    $sql = "UPDATE clientes SET estado = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $nuevo_estado, $cliente_id);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje_exito'] = 'Estado actualizado correctamente';
    } else {
        $_SESSION['mensaje_error'] = 'Error al actualizar estado';
    }
    $stmt->close();
    header("Location: tabla_clientes.php");
    exit;
}

// Obtener conteo de clientes activos e inactivos
$sqlConteo = "SELECT estado, COUNT(*) as total FROM clientes GROUP BY estado";
$resultConteo = mysqli_query($conexion, $sqlConteo);

$conteoClientes = [
    'activos' => 0,
    'inactivos' => 0
];

if ($resultConteo) {
    while ($fila = mysqli_fetch_assoc($resultConteo)) {
        if ($fila['estado'] == 'activo') {
            $conteoClientes['activos'] = (int)$fila['total'];
        } else if ($fila['estado'] == 'inactivo') {
            $conteoClientes['inactivos'] = (int)$fila['total'];
        }
    }
}

// Procesar búsqueda
$busqueda = '';
$sql = "SELECT * FROM clientes";

if (isset($_GET['buscar']) && !empty($_GET['buscar'])) {
    $busqueda = $conexion->real_escape_string($_GET['buscar']);
    $sql .= " WHERE nombre LIKE '%$busqueda%'";
    $_SESSION['ultima_busqueda'] = $busqueda;
}

$resultado = mysqli_query($conexion, $sql);
$total_resultados = mysqli_num_rows($resultado);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/estilos.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
   <div class="menu-container">
        <?php include('menu.php'); ?> 
    </div>

    <div class="main-content">
    <div class="content">
        <!-- Mensajes flash -->
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

        <h3>Clientes</h3>
        
        <!-- Contenedor de búsqueda y botón de gráfico -->
        <div class="busqueda-container">
            <form method="GET" action="">
                <input type="text" name="buscar" placeholder="Buscar por nombre..." 
                       value="<?= htmlspecialchars($busqueda) ?>">
                <button type="submit">Buscar</button>
                <?php if (!empty($busqueda)): ?>
                    <a href="tabla_clientes.php" class="ver-todos-btn">Ver todos</a>
                <?php endif; ?>
            </form>
            
            <!-- Botón para mostrar el gráfico -->
            <button id="btnGrafico" class="btn-grafico">
                <i class="bi bi-bar-chart"></i> Mostrar Gráfico
            </button>
        </div>
        
        <!-- Contenedor del gráfico -->
        <div id="graficoContainer" class="grafico-container">
            <div class="chart-container">
                <canvas id="clientesChart"></canvas>
            </div>
        </div>
        
        <!-- Mensaje de resultados de búsqueda -->
        <?php if (!empty($busqueda)): ?>
            <div class="mensaje-busqueda <?= $total_resultados > 0 ? 'mensaje-info' : 'mensaje-error' ?>">
                <?php if ($total_resultados > 0): ?>
                    Se encontraron <?= $total_resultados ?> cliente(s) con el nombre "<?= htmlspecialchars($busqueda) ?>"
                <?php else: ?>
                    No se encontraron clientes con el nombre "<?= htmlspecialchars($busqueda) ?>"
                <?php endif; ?>
            </div>
        <?php endif; ?>
            
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>RIF</th>
                    <th>Ubicación</th>
                    <th>Contacto</th>
                    <th>N. Equipos</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php if (mysqli_num_rows($resultado) > 0): ?>
                    <?php while ($filas = mysqli_fetch_assoc($resultado)): ?>
                        <tr>
                            <td><?= $filas['id'] ?></td>
                            <td><?= htmlspecialchars($filas['nombre']) ?></td>
                            <td><?= htmlspecialchars($filas['correo']) ?></td>
                            <td><?= htmlspecialchars($filas['rif']) ?></td>
                            <td><?= htmlspecialchars($filas['ubicacion']) ?></td>
                            <td><?= htmlspecialchars($filas['telefono']) ?></td>
                            <td><?= $filas['n_equipos'] ?></td>
                            <td>
                                <?php if (tienePermiso('clientes', 'puede_editar')): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="cliente_id" value="<?= $filas['id'] ?>">
                                    <select name="nuevo_estado" 
                                            class="estado-select <?= $filas['estado'] === 'activo' ? 'estado-activo' : 'estado-inactivo' ?>" 
                                            onchange="this.form.submit()">
                                        <option value="activo" <?= $filas['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                                        <option value="inactivo" <?= $filas['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                    </select>
                                    <input type="hidden" name="cambiar_estado" value="1">
                                </form>
                                <?php else: ?>
                                    <span class="badge bg-<?= $filas['estado'] === 'activo' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($filas['estado']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="action-buttons">
                                <?php if (tienePermiso('clientes', 'puede_editar')): ?>
                                    <a href="editar.php?id=<?= $filas['id'] ?>" class="btn-action btn-edit" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center;">
                            <?php if (!empty($busqueda)): ?>
                                No se encontraron clientes con ese nombre
                            <?php else: ?>
                                No hay clientes registrados
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php mysqli_close($conexion); ?>
    </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Auto-cierre de mensajes después de 5 segundos
    setTimeout(() => {
        const alerts = document.querySelectorAll('.mensaje-flash');
        alerts.forEach(alert => {
            alert.style.display = 'none';
        });
    }, 5000);
    
    // Datos para el gráfico 
    const datosGrafico = {
        activos: <?= $conteoClientes['activos'] ?>,
        inactivos: <?= $conteoClientes['inactivos'] ?>
    };
    
    // Elementos DOM
    const btnGrafico = document.getElementById('btnGrafico');
    const graficoContainer = document.getElementById('graficoContainer');
    let chartInstance = null;
    
    // Función para crear/actualizar el gráfico
    function crearGrafico() {
        if (chartInstance) {
            chartInstance.destroy();
        }
        
        const ctx = document.getElementById('clientesChart').getContext('2d');
        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Activos', 'Inactivos'],
                datasets: [{
                    label: 'Cantidad de Clientes',
                    data: [datosGrafico.activos, datosGrafico.inactivos],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.5)', // Azul para activos
                        'rgba(255, 99, 132, 0.5)'  // Rojo para inactivos
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Distribución de Clientes',
                        font: {
                            size: 16
                        }
                    }
                }
            }
        });
    }
    
    // Evento para mostrar/ocultar el gráfico
    btnGrafico.addEventListener('click', () => {
        if (graficoContainer.style.display === 'none') {
            graficoContainer.style.display = 'block';
            crearGrafico();
            btnGrafico.innerHTML = '<i class="bi bi-bar-chart"></i> Ocultar Gráfico';
        } else {
            graficoContainer.style.display = 'none';
            btnGrafico.innerHTML = '<i class="bi bi-bar-chart"></i> Mostrar Gráfico';
        }
    });
    
    // Verificar si hay mensaje de éxito para mostrar el gráfico
    <?php if (isset($_SESSION['mensaje_exito'])): ?>
        graficoContainer.style.display = 'block';
        crearGrafico();
        btnGrafico.innerHTML = '<i class="bi bi-bar-chart"></i> Ocultar Gráfico';
    <?php endif; ?>
    </script>
</body>
</html>