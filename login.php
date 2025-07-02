<?php
session_start();
if (isset($_SESSION['id'])) {
    header('Location: tabla_clientes.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - JIG Connect</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="logo-container">
        <img src="fotos/logo.PNG" alt="Logo JIG Connect" class="logo3">
    </div>

    <div class="contenedor">
        <div class="contenedor-6">
            <div class="mensaje">
                <h3>Iniciar sesión</h3>
            </div>
            
            <form action="validacion_sesion.php" method="post">
                <label>Usuario:</label>
                <input type="text" name="usuario">
                
                <label>Contraseña:</label>
                <input type="password" name="contraseña" required placeholder="••••••••">
                
                <button type="submit">Ingresar</button>
            </form>
            
            <?php if (isset($_GET['error'])): ?>
                <div style="color: #d9534f; text-align: center; margin-top: 10px;">
                    <?= ($_GET['error'] == 'credenciales') ? 'Usuario o contraseña incorrectos' : 'Error al iniciar sesión' ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

 <div class="version-badge">v5.8.7</div>
</body>
</html>