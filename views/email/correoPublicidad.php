<?php
date_default_timezone_set("America/Mexico_City");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require(__DIR__."/../../PHPMailer/src/PHPMailer.php");
require(__DIR__."/../../PHPMailer/src/Exception.php");
require(__DIR__."/../../PHPMailer/src/SMTP.php");
include(__DIR__."/../../data/conexion.php");
$hoy = date("Y-m-d H:i:s");
// Consulta para obtener informacion de correos a enviar
$sql="SELECT * FROM correos_publicitarios WHERE envio = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param('s', $hoy);
$stmt->execute();
$resultado = $stmt->get_result();
$correo = $resultado->fetch_assoc();
if(!$correo){
    die("No hay infromacion");
}
// Datos de ejemplo que se enviarían dinámicamente
$titulo   = $correo['titulo']; // Título de la noticia
$contenido = $correo['contenido']; // Contenido de la noticia
$webpPath  = $correo['imagen']; // URL WebP
$urlBoton  = $correo['url_c'];
// Nombre de archivo temporal único para PNG
$tmpPng = __DIR__ . "/temp_" . uniqid() . ".png";

// Convertir WebP a PNG
$image = imagecreatefromwebp("https://www.catink.com.mx/img/correo/".$webpPath);
if(!$image) {
    die("Error al cargar la imagen WebP");
}
imagepng($image, $tmpPng);
imagedestroy($image);


// Crear objeto PHPMailer
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'news@catink.com.mx';
    $mail->Password   = '6n+Z^6Ys*3kS';
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;

    $mail->setFrom('news@catink.com.mx', 'CatInk News');

    // Adjuntar la imagen convertida
    $mail->addEmbeddedImage($tmpPng, 'imagenNoticia', 'imagen.png');

    $mail->isHTML(true);
    $mail->Subject = $titulo;

    // Destinatarios
    $sqlUsuarios = "SELECT correo, nombre_completo FROM suscripciones";
    $resUsuarios = $con->query($sqlUsuarios);

    while($user = $resUsuarios->fetch_assoc()){
        $mail->clearAddresses();
        $mail->addAddress($user['correo'], $user['nombre_completo']);

        $unsubscribeUrl = 'https://www.catink.com.mx/views/email/unsubscribe.php?email=' . urlencode($user['correo']);

        $html = "
    <div style='font-family: Arial, sans-serif; max-width:600px; margin:auto; background:#f9f9f9; padding:20px; border-radius:10px;'>
        <h2 style='color:#EF3363;'>$titulo</h2>
        <p style='color:#333;'>$contenido</p>
        <img src='cid:imagenNoticia' style='width:100%; max-width:500px; border-radius:10px; margin:15px 0;' />
        <a href='$urlBoton' 
           style='display:inline-block; padding:10px 20px; background:#EF3363; color:#fff; text-decoration:none; border-radius:5px; margin-top:10px;'>
           Ver promocion
        </a>
        <div style='margin-top:20px; padding:15px; background:#333333; color:#ffffff; border-radius:10px;'>
            <h3 style='margin:0 0 10px;'>Síguenos</h3>
            <p style='margin:0 0 10px;'>
                <a href='https://www.facebook.com/TheCatink?locale=es_LA' style='color:#ffffff; text-decoration:none; margin-right:8px;'>Facebook</a>
                <a href='https://x.com/The_Catink/' style='color:#ffffff; text-decoration:none; margin-right:8px;'>Twitter / X</a>
                <a href='https://www.instagram.com/the.catink/' style='color:#ffffff; text-decoration:none; margin-right:8px;'>Instagram</a>
                <a href='https://www.youtube.com/@thecatink' style='color:#ffffff; text-decoration:none; margin-right:8px;'>YouTube</a>
                <a href='https://www.tiktok.com/@thecatink' style='color:#ffffff; text-decoration:none;'>TikTok</a>
            </p>
            <p style='margin:10px 0;'>
                <a href='https://www.catink.com.mx/terminos-condiciones' style='display:inline-block; margin:0 6px 6px; padding:8px 12px; background:#EF3363; color:#ffffff; border-radius:6px; text-decoration:none;'>Términos y condiciones</a>
                <a href='https://www.catink.com.mx/privacidad' style='display:inline-block; margin:0 6px 6px; padding:8px 12px; background:#EF3363; color:#ffffff; border-radius:6px; text-decoration:none;'>Política de privacidad</a>
                <a href='{$unsubscribeUrl}' style='display:inline-block; margin:0 6px 6px; padding:8px 12px; background:#EF3363; color:#ffffff; border-radius:6px; text-decoration:none;'>Cancelar suscripción</a>
            </p>
            <p style='margin:8px 0 0; font-size:12px; color:#dddddd;'>En caso de requerir acalaraciones, dudas o reclamaciones, favor de contactarte al siguiente correo: help@catink.com.mx</p>
        </div>
    </div>
    ";

        $mail->Body = $html;
        $mail->send();
    }

    // Eliminar la imagen temporal
    if(file_exists($tmpPng)) {
        unlink($tmpPng);
    }

    echo "Correo enviado correctamente a todos los usuarios suscritos.\n";

} catch (Exception $e) {
    // Eliminar temporal incluso si falla
    if(file_exists($tmpPng)) unlink($tmpPng);
    echo "Error al enviar: {$mail->ErrorInfo}";
}