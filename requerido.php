<?php
session_start();

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

// Función para verificar permisos
function requiereRol($rolesPermitidos = []) {
    if (!in_array($_SESSION['id_cargo'], $rolesPermitidos)) {
        header('Location: login.php');
        exit;
    }
}
?>