<?php
require_once(__DIR__ . "/../data/conexion.php");
require_once(__DIR__ . "/../views/helpers/urlhelper.php");

header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if(strlen($q) < 2){
    echo json_encode([]);
    exit;
}

$stmt = $con->prepare("
    SELECT id, titulo, crop3 
    FROM noticias 
    WHERE titulo LIKE CONCAT('%', ?, '%')
    ORDER BY id DESC
    LIMIT 10
");

$stmt->bind_param("s", $q);
$stmt->execute();
$result = $stmt->get_result();

$data = [];

while($row = $result->fetch_assoc()){
    $data[] = [
        "titulo" => $row['titulo'],
        "imagen" => basePath().'/'.$row['crop3'],
        "url" => basePath() . "/noticia/" . $row['id']
    ];
}

echo json_encode($data);