<?php
include("../data/conexion.php");
header('Content-Type: application/json');

$pubId = intval($_GET['pub_id'] ?? 0);
if(!$pubId) exit(json_encode(['error'=>'pub_id requerido']));

$fi = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$ff = $_GET['fecha_fin'] ?? date('Y-m-d');
$fiSql = "$fi 00:00:00";
$ffSql = "$ff 23:59:59";

// CLICKS POR DIA
$sql = "
SELECT DATE(fecha) AS fecha, COUNT(*) AS clicks
FROM publicidad_clicks
WHERE publicidad_id = ?
AND fecha BETWEEN ? AND ?
GROUP BY DATE(fecha)
ORDER BY fecha
";
$stmt=$con->prepare($sql);
$stmt->bind_param("iss",$pubId,$fiSql,$ffSql);
$stmt->execute();
$r=$stmt->get_result();
$raw=[];
while($row=$r->fetch_assoc()){
    $raw[$row['fecha']] = $row['clicks'];
}

// NORMALIZAR FECHAS VACÍAS
$labels=[];$clicks=[];
$start=new DateTime($fi);
$end=new DateTime($ff);
$end->modify('+1 day');
foreach(new DatePeriod($start,new DateInterval('P1D'),$end) as $d){
    $f=$d->format('Y-m-d');
    $labels[]=$f;
    $clicks[]=$raw[$f] ?? 0;
}

// GEO
$sqlGeo="
SELECT COALESCE(pais,'Desconocido') AS pais, COUNT(*) total
FROM publicidad_clicks
WHERE publicidad_id = ?
AND fecha BETWEEN ? AND ?
GROUP BY pais
ORDER BY total DESC
";
$stmt=$con->prepare($sqlGeo);
$stmt->bind_param("iss",$pubId,$fiSql,$ffSql);
$stmt->execute();
$r=$stmt->get_result();
$paises=[];$values=[];
while($row=$r->fetch_assoc()){
    $paises[]=$row['pais'];
    $values[]=(int)$row['total'];
}

echo json_encode([
    'labels'=>$labels,
    'clicks'=>$clicks,
    'geo'=>[
        'paises'=>[
            'labels'=>$paises,
            'values'=>$values
        ]
    ]
]);
