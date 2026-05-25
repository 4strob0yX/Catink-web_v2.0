<?php
session_start();
include(__DIR__ . "/../data/conexion.php");
include(__DIR__ . "/../views/helpers/helper.php");
include(__DIR__ . "/../controllers/aclcontroller.php");
header('Content-Type: application/json');

// Proteger — solo usuarios con permiso de noticias (editar)
proteger('noticias', 'editar');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Método no permitido']));
}

$comentario_id = intval($_POST['comentario_id'] ?? 0);
$accion = $_POST['accion'] ?? '';
$motivo = trim($_POST['motivo'] ?? '');

$accionesValidas = ['aprobar', 'rechazar', 'censurar', 'eliminar', 'restaurar'];
if (!$comentario_id || !in_array($accion, $accionesValidas)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Parámetros inválidos']));
}

$usuario_id = $_SESSION['id_u'] ?? null;

if ($accion === 'eliminar') {
    // Eliminar comentario y cascada
    $stmt = $con->prepare("DELETE FROM comentarios WHERE id_com = ?");
    $stmt->bind_param("i", $comentario_id);
    $stmt->execute();
} else {
    // Mapear acción a estado
    $estadoMap = [
        'aprobar' => 'aprobado',
        'rechazar' => 'rechazado',
        'censurar' => 'censurado',
        'restaurar' => 'aprobado'
    ];
    $nuevoEstado = $estadoMap[$accion];
    
    $stmt = $con->prepare("UPDATE comentarios SET estado = ? WHERE id_com = ?");
    $stmt->bind_param("si", $nuevoEstado, $comentario_id);
    $stmt->execute();
    
    // Si se aprueba, marcar reportes como revisados
    if ($accion === 'aprobar' || $accion === 'restaurar') {
        $stmtRep = $con->prepare("UPDATE comentarios_reportes SET estado = 'revisado' WHERE comentario_id = ?");
        $stmtRep->bind_param("i", $comentario_id);
        $stmtRep->execute();
    }
    if ($accion === 'rechazar') {
        $stmtRep = $con->prepare("UPDATE comentarios_reportes SET estado = 'revisado' WHERE comentario_id = ?");
        $stmtRep->bind_param("i", $comentario_id);
        $stmtRep->execute();
    }
}

// Registrar en log de moderación
$stmtLog = $con->prepare("INSERT INTO moderacion_log (comentario_id, usuario_id, accion, motivo, automatico) VALUES (?, ?, ?, ?, 0)");
$stmtLog->bind_param("iiss", $comentario_id, $usuario_id, $accion, $motivo);
$stmtLog->execute();

echo json_encode([
    'success' => true,
    'mensaje' => "Comentario: acción '{$accion}' aplicada correctamente"
]);
