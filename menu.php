<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú - JID CONNECT</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/menu.css">
    <style>
        .hidden-item {
            display: none !important;
        }
    </style>
</head>
<body>
    <div class="menu-container">
        <img src="fotos/logo.PNG" class="menu-logo" alt="Logo JID CONNECT">
        
        <nav class="menu">
            <ul>
                <!-- Clientes -->
                <li class="<?= !tienePermiso('clientes', 'puede_ver') ? 'hidden-item' : '' ?>">
                    <a href="<?= tienePermiso('clientes', 'puede_ver') ? 'tabla_clientes.php' : '#' ?>">
                        <i class="fas fa-users"></i> Clientes
                    </a>
                </li>
                
                <li class="<?= !tienePermiso('clientes', 'puede_crear') ? 'hidden-item' : '' ?>">
                    <a href="<?= tienePermiso('clientes', 'puede_crear') ? 'nuevo_cliente.php' : '#' ?>">
                        <i class="fas fa-user-plus"></i> Nuevo cliente
                    </a>
                </li>
                
                <!-- Agenda -->
                <li class="<?= !tienePermiso('agenda', 'puede_crear') ? 'hidden-item' : '' ?>">
                    <a href="<?= tienePermiso('agenda', 'puede_crear') ? 'agenda.php' : '#' ?>">
                        <i class="fas fa-calendar-alt"></i> Agendar servicio
                    </a>
                </li>
                
                <!-- Entrevistas -->
                <li class="<?= !tienePermiso('entrevistas', 'puede_ver') ? 'hidden-item' : '' ?>">
                    <a href="<?= tienePermiso('entrevistas', 'puede_ver') ? 'entrevistas.php' : '#' ?>">
                        <i class="fas fa-comments"></i> Entrevistas
                    </a>
                </li>

                <!-- Informes -->
                <li class="<?= !tienePermiso('informe', 'puede_ver') ? 'hidden-item' : '' ?>">
                    <a href="<?= tienePermiso('informe', 'puede_ver') ? 'informe.php' : '#' ?>">
                        <i class="fas fa-file-alt"></i> Informe post-visita
                    </a>
                </li>
                
                <!-- Cerrar sesión -->
                <li>
                    <a href="cerrar_sesion.php">
                        <i class="fas fa-sign-out-alt"></i> Cerrar sesión
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <script>
    document.querySelectorAll('.menu a').forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.getAttribute('href') === '#') {
                e.preventDefault();
                alert('No tienes permisos para acceder a esta función');
            }
        });
    });
    </script>

    <div class="version-badge">v5.8.7</div>
</body>
</html>