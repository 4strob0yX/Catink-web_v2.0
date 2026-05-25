<?php
include(__DIR__ . "/../data/conexion.php");
header('Content-Type: application/json');

$noticia_id = intval($_GET['noticia_id'] ?? 0);
if (!$noticia_id) {
    http_response_code(400);
    exit(json_encode(['error' => 'noticia_id requerido']));
}

// Solo mostrar comentarios aprobados o censurados (con texto filtrado)
$stmt = $con->prepare("
    SELECT 
        c.id_com,
        c.parent_id,
        c.nombre,
        c.contenido,
        c.estado,
        c.likes,
        c.dislikes,
        c.editado,
        c.fecha
    FROM comentarios c
    WHERE c.noticia_id = ? 
      AND c.estado IN ('aprobado', 'censurado')
    ORDER BY c.fecha ASC
");
$stmt->bind_param("i", $noticia_id);
$stmt->execute();
$result = $stmt->get_result();

$comentarios = [];
while ($row = $result->fetch_assoc()) {
    $row['likes'] = (int)$row['likes'];
    $row['dislikes'] = (int)$row['dislikes'];
    $row['editado'] = (bool)$row['editado'];
    $row['fecha_formateada'] = formatearFecha($row['fecha']);
    $comentarios[] = $row;
}

// Organizar en estructura de hilos
$hilos = organizarHilos($comentarios);

echo json_encode([
    'total' => count($comentarios),
    'comentarios' => $hilos
]);

function organizarHilos($comentarios) {
    $padres = [];
    $hijos = [];
    
    foreach ($comentarios as $c) {
        if ($c['parent_id'] === null) {
            $c['respuestas'] = [];
            $padres[$c['id_com']] = $c;
        } else {
            $hijos[] = $c;
        }
    }
    
    foreach ($hijos as $hijo) {
        if (isset($padres[$hijo['parent_id']])) {
            $padres[$hijo['parent_id']]['respuestas'][] = $hijo;
        }
    }
    
    return array_values($padres);
}

function formatearFecha($fecha) {
    $ts = strtotime($fecha);
    $diff = time() - $ts;
    
    if ($diff < 60) return 'Hace un momento';
    if ($diff < 3600) return 'Hace ' . floor($diff / 60) . ' min';
    if ($diff < 86400) return 'Hace ' . floor($diff / 3600) . ' h';
    if ($diff < 604800) return 'Hace ' . floor($diff / 86400) . ' días';
    return date('d/m/Y H:i', $ts);
}
