<?php
date_default_timezone_set("America/Mexico_City");
echo "Cron ejecutado: " . date("Y-m-d H:i:s") . "\n";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require(__DIR__."/../../PHPMailer/src/PHPMailer.php");
require(__DIR__."/../../PHPMailer/src/Exception.php");
require(__DIR__."/../../PHPMailer/src/SMTP.php");
include(__DIR__."/../../data/conexion.php");

// Obtenemos la hora programada
$hora = "SELECT hora FROM programacion_correos LIMIT 1";
$stmtHora = $con->prepare($hora);
$stmtHora->execute();
$resultHora = $stmtHora->get_result();
$rowHora = $resultHora->fetch_assoc();
$horaProgramada = $rowHora['hora'];

// Solo ejecutar si estamos dentro del rango de +-60s de la hora programada
if (abs(strtotime(date("H:i:s")) - strtotime($horaProgramada)) <= 60) {

    $hoy = date("Y-m-d H:i:s");
    $ayerMismoHorario = date("Y-m-d H:i:s", strtotime("-24 hours"));

    // Seleccionamos solo noticias del último día
    $sql = "SELECT * FROM noticias WHERE fecha_publicacion BETWEEN ? AND ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ss", $ayerMismoHorario, $hoy);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $noticias = [];
    while ($row = $resultado->fetch_assoc()) {
        $noticias[] = $row;
    }

    if (empty($noticias)) {
        die("No se encontraron noticias para el día $hoy");
    } else {
        echo "Noticias encontradas: " . count($noticias) . "\n";
    }

    // Preparar PHPMailer
    $mail = new PHPMailer(true);
    $contenidoNoticias = '';
    $mail->addEmbeddedImage(__DIR__ . '/logo_alt.png', 'banner', 'logo_alt.png');

    foreach ($noticias as $index => $noticia) {
        $descripcion = strip_tags($noticia['descripcion']);
        $descripcion = mb_strimwidth($descripcion, 0, 100, '...');

        $webp = 'https://catink.com.mx/' . $noticia['crop3'];
        $png = __DIR__ . "/logo_temp_{$index}.png";

        // Convertir WebP a PNG
        $image = imagecreatefromwebp($webp);
        imagepng($image, $png);
        imagedestroy($image);

        $mail->addEmbeddedImage($png, "logo{$index}", "logo.png");

        $contenidoNoticias .= "
        <table width='100%' cellpadding='0' cellspacing='0' border='0' 
            style='background:#ffffff;margin-bottom:15px;border-radius:10px;overflow:hidden;'>
        <tr class='stack-column'>
        <td width='240' valign='top' class='card-padding' style='padding:14px;'>
            <img src='cid:logo{$index}' width='220' class='stack-img' 
                style='width:100%;max-width:220px;height:auto;display:block;border-radius:10px;border:0;margin:0;'>
        </td>
        <td valign='top' class='card-padding' style='padding:14px;font-family:Arial,sans-serif;'>
            <a href='https://catink.com.mx/views/news.php?id={$noticia['id']}' 
               style='display:block;margin:14px;text-decoration:none;color:#EF3363;'>
                <h3 style='margin:0;font-family:Arial,sans-serif;color:#EF3363;'>{$noticia['titulo']}</h3>
            </a>
            <p style='margin:14px;'>{$descripcion}</p>
        </td>
        </tr>
        </table>";
    }

    // Plantilla de correo
    $plantillaPath = __DIR__ . "/diarias.html";
    if (!file_exists($plantillaPath)) {
        die("Error: plantilla no encontrada en $plantillaPath");
    }
    $plantilla = file_get_contents($plantillaPath);
    $plantilla = str_replace("{{noticias}}", $contenidoNoticias, $plantilla);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'news@catink.com.mx';
        $mail->Password = '6n+Z^6Ys*3kS';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('news@catink.com.mx', 'Noticias del día');

        // Destinatarios
        $sqlUsuarios = "SELECT correo, nombre_completo FROM suscripciones";
        $resUsuarios = $con->query($sqlUsuarios);

        $mail->isHTML(true);
        $mail->Subject = 'Resumen diario de noticias';

        while($user = $resUsuarios->fetch_assoc()){
            $mail->clearAddresses();
            $mail->addAddress($user['correo'], $user['nombre_completo']);

            $unsubscribeUrl = 'https://www.catink.com.mx/views/email/unsubscribe.php?email=' . urlencode($user['correo']);
            $body = str_replace('{{unsubscribe_url}}', $unsubscribeUrl, $plantilla);

            $mail->Body = $body;
            $mail->send();
        }

        // Limpiar archivos temporales
        foreach ($noticias as $index => $noticia) {
            $png = __DIR__ . "/logo_temp_{$index}.png";
            if (file_exists($png)) unlink($png);
        }

        echo "Correo enviado correctamente a todos los usuarios suscritos.\n";

    } catch (Exception $e) {
        echo "Error al enviar: {$mail->ErrorInfo}";
    }
}