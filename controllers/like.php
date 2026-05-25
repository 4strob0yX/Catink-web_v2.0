<?php
include("../data/conexion.php");
header('Content-Type: application/json');
// ============================
// OBTENER IP DEL USUARIO
// ============================
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    return $_SERVER['REMOTE_ADDR'];
}
$noticia_id = intval($_POST['noticia_id'] ?? 0);
$ip = getUserIP();
if ($noticia_id <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
    exit;
}
// ============================
// GEOLOCALIZACION OPCIONAL
// ============================
$pais = 'Desconocido';
$estado = 'Desconocido';
try {
    $geoJson = @file_get_contents("http://ip-api.com/json/$ip");
    if ($geoJson !== false) {
        $geo = json_decode($geoJson, true);
        $pais = $geo['country'] ?? 'Desconocido';
        $estado = $geo['regionName'] ?? 'Desconocido';
    }
} catch (Exception $e) {
    // Ignorar errores de geolocalización
}
// ============================
// REGISTRAR LIKE EN BD
// ============================
try {
    $con->begin_transaction();
    $stmt = $con->prepare("INSERT INTO noticia_likes (noticia_id, ip, pais, estado) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $noticia_id, $ip, $pais, $estado);
    if ($stmt->execute()) {
        // Incrementar contador de likes
        $upd = $con->prepare("UPDATE noticias SET likes = likes + 1 WHERE id = ?");
        $upd->bind_param("i", $noticia_id);
        $upd->execute();
        $con->commit();
        echo json_encode(['ok' => true, 'msg' => 'Like registrado']);
    }
} catch (mysqli_sql_exception $e) {
    $con->rollback();
    // Código 1062 = clave duplicada → ya dio like
    if ($e->getCode() == 1062) {
        echo json_encode(['ok' => false, 'msg' => 'Ya diste like']);
    } else {
        error_log("Error al guardar like noticia $noticia_id: " . $e->getMessage());
        echo json_encode(['ok' => false, 'msg' => 'Error interno']);
    }
}
?>