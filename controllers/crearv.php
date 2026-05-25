<?php
ob_start();
header('Content-Type: application/json');
session_start();
include("./aclcontroller.php");
proteger('videos','crear');
include("../data/conexion.php");
$url = $_POST['url_v'] ?? '';
$activo = $_POST['activo'] ?? 0;
if (empty($url)) {
    echo json_encode([
        'success' => false,
        'error' => 'URL vacía'
    ]);
    exit;
}
$stmt = $con->prepare("INSERT INTO videos (url_v, activo) VALUES (?, ?)");
$stmt->bind_param("si", $url, $activo);
if($stmt->execute()){
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $stmt->error
    ]);
}
exit;