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
    // Siempre mostrar en modo diario - generar todos los días del rango
    $modo = 'diario';
    $groupBy = "DATE(ns.fecha)";
    $labelFormat = "DATE(ns.fecha)";
    
    // Generar array con todos los días del rango
    $allDias = [];
    $current = new DateTime($fechaInicio);
    $end = new DateTime($fechaFin);
    while ($current <= $end) {
        $allDias[] = $current->format('Y-m-d');
        $current->modify('+1 day');
    }
    // ============================
    // Consulta de estadísticas
    // ============================
    $sql = "
        SELECT
            c.nombre AS categoria,
            {$groupBy} AS periodo,
            {$labelFormat} AS label_fecha,
            COUNT(ns.id_s) AS vistas,
            SUM(ns.tiempo_segundos) AS tiempo
        FROM noticias_stats ns
        INNER JOIN noticias n ON n.id = ns.noticia_id
        INNER JOIN noticia_categoria nc ON nc.noticia_id = n.id
        INNER JOIN categorias c ON c.id_c = nc.categoria_id
        WHERE ns.fecha BETWEEN ? AND ?
        GROUP BY c.nombre, periodo
        ORDER BY label_fecha ASC
    ";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ss", $fechaInicioSql, $fechaFinSql);
    $stmt->execute();
    $result = $stmt->get_result();
    $labels = $allDias; // Usar todos los días generados
    $dataMap = [];
    
    // Mapear datos por categoría y fecha
    while ($row = $result->fetch_assoc()) {
        $label = $row['label_fecha'];
        $cat = $row['categoria'];
        if (!isset($dataMap[$cat])) {
            $dataMap[$cat] = [
                'vistas' => [],
                'tiempo' => []
            ];
        }
        $dataMap[$cat]['vistas'][$label] = (int)$row['vistas'];
        $dataMap[$cat]['tiempo'][$label] = (float)$row['tiempo']/60;
    }
    // ============================
    // Normalizar arrays (rellenar ceros para TODOS los días)
    // ============================
    $dataCategorias = [];
    foreach ($dataMap as $cat => $metrics) {
        $finalVistas = [];
        $finalTiempo = [];
        foreach ($labels as $l) {
            $finalVistas[] = $metrics['vistas'][$l] ?? 0;
            $finalTiempo[] = $metrics['tiempo'][$l] ?? 0;
        }
        $dataCategorias[$cat] = [
            'vistas' => $finalVistas,
            'tiempo' => $finalTiempo
        ];
    }
    echo json_encode([
        'modo' => $modo,
        'labels' => $labels,
        'categorias' => $dataCategorias
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>