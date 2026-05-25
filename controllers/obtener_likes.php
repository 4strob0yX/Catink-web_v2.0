<?php
include("../data/conexion.php");
header('Content-Type: application/json');
//validar noticia_id
$noticiaId = intval($_GET['noticia_id'] ?? 0);
if(!$noticiaId) exit(json_encode(['error'=>'noticia_id requerido']));
//validar fechas
$fi = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$ff = $_GET['fecha_fin'] ?? date('Y-m-d');
$fiSql = "$fi 00:00:00";
$ffSql = "$ff 23:59:59";
// LIKES POR DIA
$sql = "
SELECT DATE(fecha) AS fecha, COUNT(*) AS likes
FROM noticia_likes
WHERE noticia_id = ?
AND fecha BETWEEN ? AND ?
GROUP BY DATE(fecha)
ORDER BY fecha
";
$stmt=$con->prepare($sql);
$stmt->bind_param("iss",$noticiaId,$fiSql,$ffSql);
$stmt->execute();
$r=$stmt->get_result();
$raw=[];
while($row=$r->fetch_assoc()){
    $raw[$row['fecha']] = $row['likes'];
}
// NORMALIZAR FECHAS VACÍAS
$labels=[];$likes=[];
$start=new DateTime($fi);
$end=new DateTime($ff);
$end->modify('+1 day');
foreach(new DatePeriod($start,new DateInterval('P1D'),$end) as $d){
    $f=$d->format('Y-m-d');
    $labels[]=$f;
    $likes[]=$raw[$f] ?? 0;
}
// GEO
$sqlGeo="
SELECT COALESCE(pais,'Desconocido') AS pais, COUNT(*) total
FROM noticia_likes
WHERE noticia_id = ?
AND fecha BETWEEN ? AND ?
GROUP BY pais
ORDER BY total DESC
";
$stmt=$con->prepare($sqlGeo);
$stmt->bind_param("iss",$noticiaId,$fiSql,$ffSql);
$stmt->execute();
$r=$stmt->get_result();
$paises=[];$values=[];
while($row=$r->fetch_assoc()){
    $paises[]=$row['pais'];
    $values[]=(int)$row['total'];
}
echo json_encode([
    'labels'=>$labels,
    'likes'=>$likes,
    'geo'=>[
        'paises'=>[
            'labels'=>$paises,
            'values'=>$values
        ]
    ]
]);
