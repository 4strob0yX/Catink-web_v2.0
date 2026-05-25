<?php
session_start();
include(__DIR__ . "/../data/conexion.php");
include(__DIR__ . "/../views/helpers/helper.php");
include(__DIR__ . "/../controllers/aclcontroller.php");
header('Content-Type: application/json');

proteger('noticias', 'editar');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Método no permitido']));
}

if (!isset($_POST['config']) || !is_array($_POST['config'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Datos de configuración requeridos']));
}

$stmt = $con->prepare("UPDATE moderacion_config SET valor = ? WHERE clave = ?");

foreach ($_POST['config'] as $clave => $valor) {
    $valorLimpio = trim($valor);
    $claveLimpia = trim($clave);
    $stmt->bind_param("ss", $valorLimpio, $claveLimpia);
    $stmt->execute();
}

echo json_encode(['success' => true, 'mensaje' => 'Configuración actualizada']);
