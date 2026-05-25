<?php
date_default_timezone_set("America/Mexico_City");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require("./../../PHPMailer/src/PHPMailer.php");
require("./../../PHPMailer/src/Exception.php");
require("./../../PHPMailer/src/SMTP.php");
include("./../../data/conexion.php");

$nombre = $_POST['nombre'];
$email = $_POST['email'];
$mensaje = $_POST['razon'];
$cv = $_FILES['cv'];
// Crear objeto PHPMailer
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'catink.oficial@gmail.com';
    $mail->Password   = 'lamcszfwuoftmlpv';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('catink.oficial@gmail.com', 'CatInk News');

    // Destinatarios (puedes poner dinámicos desde base de datos)
    $mail->addAddress('arturo_mtz_h@hotmail.com', 'ING Arturo Matínez Hernández');

    $mail->isHTML(true);
    $mail->Subject = "Solicitud de $nombre para unirse a CatInk";

    // Adjuntar el archivo CV
    if (isset($_FILES['cv']) && $_FILES['cv']['error'] == 0) {
        $mail->addAttachment(
            $_FILES['cv']['tmp_name'],
            $_FILES['cv']['name']
        );
    }

    // Construir el contenido HTML
    $html = "
    <div style='font-family: Arial, sans-serif; max-width:600px; margin:auto; background:#f9f9f9; padding:20px; border-radius:10px;'>
        <h2 style='color:#EF3363;'>Solicitud de unión a CatInk</h2>
        <p style='color:#333;'><strong>Nombre:</strong> $nombre</p>
        <p style='color:#333;'><strong>Correo:</strong> $email</p>
        <p style='color:#333;'><strong>Motivo por el cual quiere unirse:</strong> $mensaje</p>
    </div>
    ";

    $mail->Body = $html;

    $mail->send();

    echo "Correo enviado correctamente.";
    header("Location: ./../unete.php?success=1");
    exit();

} catch (Exception $e) {
    // Eliminar temporal incluso si falla
    if(file_exists($tmpPng)) unlink($tmpPng);
    echo "Error al enviar: {$mail->ErrorInfo}";
}