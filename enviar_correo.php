<?php
require 'libreria/PHPMailer/src/PHPMailer.php';
require 'libreria/PHPMailer/src/SMTP.php';
require 'libreria/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function enviarCorreoConfirmacion($correo, $nombre, $servicio_id, $fecha, $hora, $descripcion, $usuario_id) {
    $mail = new PHPMailer(true);
    
    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jose00pg2@gmail.com';
        $mail->Password = 'mzui cite iysy rujr';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Configuración del correo
        $mail->setFrom('no-reply@jidconnect.com', 'JID CONNECT');
        $mail->addAddress($correo, $nombre);
        $mail->isHTML(true);
        $mail->Subject = "Confirmación de servicio #$servicio_id";
        
        $mail->Body = "
        <h2>Confirmación de Servicio Técnico</h2>
        <p>Hola $nombre,</p>
        <p>Su servicio técnico ha sido agendado con los siguientes detalles:</p>
        <ul>
            <li><strong>ID Servicio:</strong> $servicio_id</li>
            <li><strong>Fecha:</strong> $fecha</li>
            <li><strong>Hora:</strong> $hora</li>
            <li><strong>Descripción:</strong> $descripcion</li>
        </ul>
        <p>Técnico asignado: $usuario_id</p>
        <p>Gracias por confiar en JID CONNECT.</p>
        ";
        
        $mail->send();
        return ['status' => true, 'message' => 'Correo enviado'];
    } catch (Exception $e) {
        return ['status' => false, 'message' => $e->getMessage()];
    }
}
?>

