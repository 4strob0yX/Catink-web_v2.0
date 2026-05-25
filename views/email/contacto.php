<?php
date_default_timezone_set("America/Mexico_City");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require("./../../PHPMailer/src/PHPMailer.php");
require("./../../PHPMailer/src/Exception.php");
require("./../../PHPMailer/src/SMTP.php");
include("./../../data/conexion.php");

$nombre = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$mensaje = $_POST['message'] ?? '';

try {
    // === 1. Correo al administrador ===
    $mailAdmin = new PHPMailer(true);
    $mailAdmin->isSMTP();
    $mailAdmin->Host       = 'smtp.gmail.com';
    $mailAdmin->SMTPAuth   = true;
    $mailAdmin->Username   = 'catink.oficial@gmail.com';
    $mailAdmin->Password   = 'lamcszfwuoftmlpv'; // Considera usar variables de entorno
    $mailAdmin->SMTPSecure = 'tls';
    $mailAdmin->Port       = 587;

    $mailAdmin->setFrom('catink.oficial@gmail.com', 'Contacto CatInk');
    $mailAdmin->addAddress('arturo_mtz_h@hotmail.com', 'ING Arturo Matínez Hernández');

    $mailAdmin->isHTML(true);
    $mailAdmin->Subject = "Solicitud de asesoramiento por parte de CatInk";

    $htmlAdmin = "
    <div style='font-family: Arial, sans-serif; max-width:600px; margin:auto; background:#f9f9f9; padding:20px; border-radius:10px;'>
        <h2 style='color:#EF3363;'>Solicitud de asesoramiento por parte de CatInk</h2>
        <p style='color:#333;'><strong>Nombre:</strong> $nombre</p>
        <p style='color:#333;'><strong>Correo:</strong> $email</p>
        <p style='color:#333;'><strong>Mensaje:</strong> $mensaje</p>
    </div>
    ";

    $mailAdmin->Body = $htmlAdmin;
    $mailAdmin->send();

    // === 2. Correo de confirmación al usuario ===
    $mailUser = new PHPMailer(true);
    $mailUser->isSMTP();
    $mailUser->Host       = 'smtp.gmail.com';
    $mailUser->SMTPAuth   = true;
    $mailUser->Username   = 'catink.oficial@gmail.com';
    $mailUser->Password   = 'lamcszfwuoftmlpv';
    $mailUser->SMTPSecure = 'tls';
    $mailUser->Port       = 587;

    $mailUser->setFrom('catink.oficial@gmail.com', 'CatInk');
    $mailUser->addAddress($email, $nombre);

    $mailUser->isHTML(true);
    $mailUser->Subject = "Confirmación de recepción";

    $htmlUser = "
    <div style='font-family: Arial, sans-serif; max-width:600px; margin:auto; background:#f9f9f9; padding:20px; border-radius:10px;'>
        <h2 style='color:#EF3363;'>Hemos recibido tu información</h2>
        <p style='color:#333;'>Gracias <strong>$nombre</strong> por contactarnos. Pronto nos pondremos en contacto contigo.</p>
    </div>
    ";

    $mailUser->Body = $htmlUser;
    $mailUser->send();

    header("Location: ./../contactanos.php?success=1");
    exit();

} catch (Exception $e) {
    echo "Error al enviar: {$e->getMessage()}";
}