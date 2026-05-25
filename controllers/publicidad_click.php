<?php
include("../data/conexion.php");
header('Content-Type: application/json');

function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    return $_SERVER['REMOTE_ADDR'];
}

$publicidad_id = intval($_POST['publicidad_id'] ?? 0);
$ip = getUserIP();

if ($publicidad_id <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
    exit;
}

$pais = 'Desconocido';
$estado = 'Desconocido';

try {
    $geoJson = @file_get_contents("http://ip-api.com/json/$ip");
    if ($geoJson !== false) {
        $geo = json_decode($geoJson, true);
        $pais = $geo['country'] ?? 'Desconocido';
        $estado = $geo['regionName'] ?? 'Desconocido';
    }
} catch (Exception $e) {}

$stmt = $con->prepare("
    INSERT INTO publicidad_clicks (publicidad_id, ip, pais, estado)
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param("isss", $publicidad_id, $ip, $pais, $estado);
$stmt->execute();

echo json_encode(['ok' => true]);
