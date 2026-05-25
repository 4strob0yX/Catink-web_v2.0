<?php
include(__DIR__ . "/../data/conexion.php");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Método no permitido']));
}

$comentario_id = intval($_POST['comentario_id'] ?? 0);
$motivo = $_POST['motivo'] ?? '';
$descripcion = trim($_POST['descripcion'] ?? '');

$motivosValidos = ['spam', 'ofensivo', 'irrelevante', 'acoso', 'otro'];
if (!$comentario_id || !in_array($motivo, $motivosValidos)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Parámetros inválidos']));
}

$ip = !empty($_SERVER['HTTP_X_FORWARDED_FOR'])
    ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]
    : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

// Verificar que no haya reportado ya este comentario
$stmtCheck = $con->prepare("SELECT id_reporte FROM comentarios_reportes WHERE comentario_id = ? AND ip = ?");
$stmtCheck->bind_param("is", $comentario_id, $ip);
$stmtCheck->execute();
if ($stmtCheck->get_result()->num_rows > 0) {
    http_response_code(409);
    exit(json_encode(['error' => 'Ya reportaste este comentario']));
}

// Insertar reporte
$stmt = $con->prepare("INSERT INTO comentarios_reportes (comentario_id, motivo, descripcion, ip) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $comentario_id, $motivo, $descripcion, $ip);
$stmt->execute();

// Verificar si se alcanzó el umbral de auto-ocultación
$stmtConfig = $con->prepare("SELECT valor FROM moderacion_config WHERE clave = 'max_reportes_auto'");
$stmtConfig->execute();
$maxReportes = (int)($stmtConfig->get_result()->fetch_assoc()['valor'] ?? 3);

$stmtCount = $con->prepare("SELECT COUNT(*) AS total FROM comentarios_reportes WHERE comentario_id = ? AND estado = 'pendiente'");
$stmtCount->bind_param("i", $comentario_id);
$stmtCount->execute();
$totalReportes = (int)$stmtCount->get_result()->fetch_assoc()['total'];

if ($totalReportes >= $maxReportes) {
    // Ocultar automáticamente
    $stmtHide = $con->prepare("UPDATE comentarios SET estado = 'pendiente' WHERE id_com = ? AND estado = 'aprobado'");
    $stmtHide->bind_param("i", $comentario_id);
    $stmtHide->execute();
    
    // Log de moderación automática
    $stmtLog = $con->prepare("INSERT INTO moderacion_log (comentario_id, accion, motivo, automatico) VALUES (?, 'rechazar', ?, 1)");
    $motivoLog = "Auto-ocultado por alcanzar {$maxReportes} reportes";
    $stmtLog->bind_param("is", $comentario_id, $motivoLog);
    $stmtLog->execute();
}

echo json_encode([
    'success' => true,
    'mensaje' => 'Reporte enviado. Gracias por ayudar a mantener la comunidad.'
]);
