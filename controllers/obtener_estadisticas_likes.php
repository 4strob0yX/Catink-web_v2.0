<?php
include("../data/conexion.php");
header('Content-Type: application/json');
try {
    // ============================
    // Parámetros de fecha
    // ============================
    $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
    $fechaFin    = $_GET['fecha_fin'] ?? date('Y-m-d');
    $fechaInicioSql = $fechaInicio . ' 00:00:00';
    $fechaFinSql    = $fechaFin . ' 23:59:59';
    // ============================
    // Siempre modo diario - generar todos los días
    // ============================
    $modo = 'diario';
    $groupBy = "DATE(nl.fecha)";
    $labelFormat = "DATE(nl.fecha)";
    
    // Generar array con todos los días del rango
    $allDias = [];
    $current = new DateTime($fechaInicio);
    $end = new DateTime($fechaFin);
    while ($current <= $end) {
        $allDias[] = $current->format('Y-m-d');
        $current->modify('+1 day');
    }
    // ============================
    // Likes por categoría
    // ============================
    $sql = "
        SELECT
            c.nombre AS categoria,
            {$groupBy} AS periodo,
            {$labelFormat} AS label_fecha,
            COUNT(nl.id_l) AS likes
        FROM noticia_likes nl
        INNER JOIN noticias n ON n.id = nl.noticia_id
        INNER JOIN noticia_categoria nc ON nc.noticia_id = n.id
        INNER JOIN categorias c ON c.id_c = nc.categoria_id
        WHERE nl.fecha BETWEEN ? AND ?
        GROUP BY c.nombre, periodo
        ORDER BY label_fecha ASC
    ";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ss", $fechaInicioSql, $fechaFinSql);
    $stmt->execute();
    $result = $stmt->get_result();
    // ============================
    // Likes por región
    // ============================
    $sqlGeo = "
        SELECT 
            COALESCE(pais, 'Desconocido') AS pais,
            COALESCE(estado, 'Desconocido') AS estado,
            COUNT(*) AS total
        FROM noticia_likes
        WHERE fecha BETWEEN ? AND ?
        GROUP BY pais, estado
        ORDER BY total DESC
    ";
    $stmtGeo = $con->prepare($sqlGeo);
    $stmtGeo->bind_param("ss", $fechaInicioSql, $fechaFinSql);
    $stmtGeo->execute();
    $resultGeo = $stmtGeo->get_result();
    // ============================
    // Procesar datos
    // ============================
    $labels = $allDias; // Usar todos los días generados
    $dataMap = [];
    $paises = [];
    $estados = [];
    // Geolocalización
    while ($row = $resultGeo->fetch_assoc()) {
        $pais = $row['pais'];
        $estado = $row['estado'];
        $paises[$pais] = ($paises[$pais] ?? 0) + (int)$row['total'];
        $estados[$estado] = ($estados[$estado] ?? 0) + (int)$row['total'];
    }
    // Likes por categoría - mapear datos
    while ($row = $result->fetch_assoc()) {
        $label = $row['label_fecha'];
        $cat = $row['categoria'];
        if (!isset($dataMap[$cat])) {
            $dataMap[$cat] = ['likes' => []];
        }
        $dataMap[$cat]['likes'][$label] = (int)$row['likes'];
    }
    // ============================
    // Normalizar (rellenar ceros para TODOS los días)
    // ============================
    $dataCategorias = [];
    foreach ($dataMap as $cat => $metrics) {
        $finalLikes = [];
        foreach ($labels as $l) {
            $finalLikes[] = $metrics['likes'][$l] ?? 0;
        }
        $dataCategorias[$cat] = [
            'likes' => $finalLikes
        ];
    }
    // ============================
    // Preparar datos GEO
    // ============================
    $geoData = [
        'paises' => [
            'labels' => array_keys($paises),
            'values' => array_values($paises)
        ],
        'estados' => [
            'labels' => array_keys($estados),
            'values' => array_values($estados)
        ]
    ];
    // ============================
    // Respuesta JSON
    // ============================
    echo json_encode([
        'modo' => $modo,
        'labels' => $labels,
        'categorias' => $dataCategorias,
        'geo' => $geoData
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>