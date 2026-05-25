<?php
include("../data/conexion.php");
header('Content-Type: application/json');

$pubId = intval($_GET['pub_id'] ?? 0);
if(!$pubId) exit(json_encode(['error'=>'pub_id requerido']));

$fi = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$ff = $_GET['fecha_fin'] ?? date('Y-m-d');
$fiSql = "$fi 00:00:00";
$ffSql = "$ff 23:59:59";

// Determinar agrupación según rango
$dias = (strtotime($ff) - strtotime($fi)) / 86400;
if($dias <= 15){
    $group = "DATE(fecha)";
} elseif($dias <= 60){
    $group = "YEARWEEK(fecha,1)";
} else {
    $group = "CONCAT(YEAR(fecha), '-', CEIL(DAY(fecha)/15))";
}

$sql = "
SELECT 
    $group AS periodo,
    MIN(DATE(fecha)) AS label_fecha,
    COUNT(*) AS vistas,
    AVG(tiempo_segundos) AS tiempo_promedio
FROM publicidad_views
WHERE publicidad_id = ?
AND fecha BETWEEN ? AND ?
GROUP BY periodo
ORDER BY label_fecha
";

$stmt = $con->prepare($sql);
$stmt->bind_param("iss",$pubId,$fiSql,$ffSql);
$stmt->execute();
$r = $stmt->get_result();

$labels=[];$vistas=[];$tiempo=[];
while($row=$r->fetch_assoc()){
    $labels[] = $row['label_fecha'];
    $vistas[] = (int)$row['vistas'];
    $tiempo[] = round($row['tiempo_promedio'],1);
}

echo json_encode([
    'labels'=>$labels,
    'vistas'=>$vistas,
    'tiempoPromedio'=>$tiempo
]);
