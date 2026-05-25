<?php
include("../data/conexion.php");
// Validar que se reciban datos
if (isset($_POST['noticia_id']) && isset($_POST['tiempo'])) {
    $noticia_id = intval($_POST['noticia_id']);
    $tiempo = intval($_POST['tiempo']);
    // Validar que la noticia exista y tiempo sea razonable
    if ($noticia_id > 0 && $tiempo > 0 && $tiempo <= 36000) { // máximo 10 horas
        $sql = "INSERT INTO noticias_stats (noticia_id, tiempo_segundos, fecha) VALUES (?, ?, NOW())";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("ii", $noticia_id, $tiempo);
        if ($stmt->execute()) {
            // Éxito
            http_response_code(200);
            echo json_encode(["status"=>"ok","message"=>"Tiempo guardado"]);
        } else {
            // Error de DB
            http_response_code(500);
            echo json_encode(["status"=>"error","message"=>$con->error]);
            error_log("Error al guardar tiempo noticia $noticia_id: " . $con->error);
        }
        $stmt->close();
    } else {
        http_response_code(400);
        echo json_encode(["status"=>"error","message"=>"Datos inválidos"]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status"=>"error","message"=>"Parámetros requeridos"]);
}
?>