<?php
include(__DIR__ . "/../data/conexion.php");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Método no permitido']));
}

$comentario_id = intval($_POST['comentario_id'] ?? 0);
$tipo = $_POST['tipo'] ?? '';

if (!$comentario_id || !in_array($tipo, ['like', 'dislike'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Parámetros inválidos']));
}

$ip = !empty($_SERVER['HTTP_X_FORWARDED_FOR'])
    ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]
    : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

// Verificar si ya reaccionó
$stmt = $con->prepare("SELECT id_reaccion, tipo FROM comentarios_reacciones WHERE comentario_id = ? AND ip = ?");
$stmt->bind_param("is", $comentario_id, $ip);
$stmt->execute();
$existente = $stmt->get_result()->fetch_assoc();

if ($existente) {
    if ($existente['tipo'] === $tipo) {
        // Quitar reacción (toggle off)
        $stmtDel = $con->prepare("DELETE FROM comentarios_reacciones WHERE id_reaccion = ?");
        $stmtDel->bind_param("i", $existente['id_reaccion']);
        $stmtDel->execute();
        
        // Decrementar contador
        $campo = $tipo === 'like' ? 'likes' : 'dislikes';
        $con->query("UPDATE comentarios SET {$campo} = GREATEST({$campo} - 1, 0) WHERE id_com = {$comentario_id}");
        
        $accion = 'removed';
    } else {
        // Cambiar reacción
        $stmtUp = $con->prepare("UPDATE comentarios_reacciones SET tipo = ?, fecha = NOW() WHERE id_reaccion = ?");
        $stmtUp->bind_param("si", $tipo, $existente['id_reaccion']);
        $stmtUp->execute();
        
        // Ajustar contadores
        $campoInc = $tipo === 'like' ? 'likes' : 'dislikes';
        $campoDec = $tipo === 'like' ? 'dislikes' : 'likes';
        $con->query("UPDATE comentarios SET {$campoInc} = {$campoInc} + 1, {$campoDec} = GREATEST({$campoDec} - 1, 0) WHERE id_com = {$comentario_id}");
        
        $accion = 'changed';
    }
} else {
    // Nueva reacción
    $stmtIns = $con->prepare("INSERT INTO comentarios_reacciones (comentario_id, tipo, ip) VALUES (?, ?, ?)");
    $stmtIns->bind_param("iss", $comentario_id, $tipo, $ip);
    $stmtIns->execute();
    
    // Incrementar contador
    $campo = $tipo === 'like' ? 'likes' : 'dislikes';
    $con->query("UPDATE comentarios SET {$campo} = {$campo} + 1 WHERE id_com = {$comentario_id}");
    
    $accion = 'added';
}

// Obtener contadores actualizados
$stmtCount = $con->prepare("SELECT likes, dislikes FROM comentarios WHERE id_com = ?");
$stmtCount->bind_param("i", $comentario_id);
$stmtCount->execute();
$counts = $stmtCount->get_result()->fetch_assoc();

echo json_encode([
    'success' => true,
    'accion' => $accion,
    'likes' => (int)$counts['likes'],
    'dislikes' => (int)$counts['dislikes']
]);
