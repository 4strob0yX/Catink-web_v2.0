<?php
include("../data/conexion.php"); // tu conexión PDO o mysqli
// =========================
// OBTENER DATOS DEL FORM
// =========================
$nombre = $_POST['nombre'] ?? '';
$correo = $_POST['email'] ?? '';
$sexo   = $_POST['sexo'] ?? '';
// Validación básica
if(empty($nombre) || empty($correo)){
    header("Location: ../suscribirse.php?error=1");
    exit();
}
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    return $_SERVER['REMOTE_ADDR'];
}
$ip = getUserIP();
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
// =========================
// INSERTAR EN BD
// =========================
$stmt = $con->prepare("INSERT INTO suscripciones 
(nombre_completo, correo, sexo, ip, pais, estado)
VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $nombre, $correo, $sexo, $ip, $pais, $estado);
if($stmt->execute()){
    header("Location: ./../views/suscripcion.php?success=1");
} else {
    header("Location:./../views/suscripcion.php?error=2");
}
