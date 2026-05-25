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

$accion = $_POST['accion'] ?? '';

switch ($accion) {
    case 'crear':
        $palabra = mb_strtolower(trim($_POST['palabra'] ?? ''));
        $severidad = $_POST['severidad'] ?? 'media';
        $accionPalabra = $_POST['accion_palabra'] ?? 'censurar';
        
        if (!$palabra) {
            http_response_code(400);
            exit(json_encode(['error' => 'Palabra requerida']));
        }
        
        $stmt = $con->prepare("INSERT INTO palabras_prohibidas (palabra, severidad, accion) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $palabra, $severidad, $accionPalabra);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'mensaje' => 'Palabra agregada', 'id' => $con->insert_id]);
        } else {
            http_response_code(409);
            echo json_encode(['error' => 'La palabra ya existe']);
        }
        break;
        
    case 'editar':
        $id = intval($_POST['id'] ?? 0);
        $severidad = $_POST['severidad'] ?? 'media';
        $accionPalabra = $_POST['accion_palabra'] ?? 'censurar';
        $activo = intval($_POST['activo'] ?? 1);
        
        if (!$id) {
            http_response_code(400);
            exit(json_encode(['error' => 'ID requerido']));
        }
        
        $stmt = $con->prepare("UPDATE palabras_prohibidas SET severidad = ?, accion = ?, activo = ? WHERE id_palabra = ?");
        $stmt->bind_param("ssii", $severidad, $accionPalabra, $activo, $id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'mensaje' => 'Palabra actualizada']);
        break;
        
    case 'eliminar':
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            exit(json_encode(['error' => 'ID requerido']));
        }
        
        $stmt = $con->prepare("DELETE FROM palabras_prohibidas WHERE id_palabra = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'mensaje' => 'Palabra eliminada']);
        break;
        
    case 'listar':
        $result = $con->query("SELECT * FROM palabras_prohibidas ORDER BY severidad DESC, palabra ASC");
        $palabras = [];
        while ($row = $result->fetch_assoc()) {
            $palabras[] = $row;
        }
        echo json_encode(['palabras' => $palabras]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida (crear, editar, eliminar, listar)']);
}
