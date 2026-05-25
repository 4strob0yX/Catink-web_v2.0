<?php
include("./../layout/headerAdmin.php");
include("./../data/conexion.php");
if (empty($ACL['leer'])) {
    header("Location: admin.php");
    exit();
}
// ============================
// Cálculo de semanas
// ============================
$weekOffset = isset($_GET['week']) ? (int)$_GET['week'] : 0;
$startOfWeek = new DateTime();
$startOfWeek->modify(($weekOffset * 7) . ' days');
$startOfWeek->modify('monday this week');
$endOfWeek = clone $startOfWeek;
$endOfWeek->modify('sunday this week');
$fechaInicio = $startOfWeek->format('Y-m-d 00:00:00');
$fechaFin = $endOfWeek->format('Y-m-d 23:59:59');
// Inicializamos array por día
$newsByDate = [];
$period = new DatePeriod(
    $startOfWeek,
    new DateInterval('P1D'),
    (clone $endOfWeek)->modify('+1 day')
);
foreach ($period as $day) {
    $newsByDate[$day->format('Y-m-d')] = [];
}
// Obtener noticias de la semana
$sql = "SELECT id, titulo, descripcion, fecha_publicacion, vistas, likes, crop3 
        FROM noticias 
        WHERE fecha_publicacion BETWEEN ? AND ?
        ORDER BY fecha_publicacion ASC";
$stmt = $con->prepare($sql);
$stmt->bind_param("ss", $fechaInicio, $fechaFin);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $newsByDate[date('Y-m-d', strtotime($row['fecha_publicacion']))][] = $row;
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestión de Contenidos</h1>
    </div>
    <?php if (!empty($ACL['crear'])): ?>
        <a href="crear.php" class="btn btn-success"><i class="bi bi-plus-lg"></i> Nueva Noticia</a>
    <?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'eliminado'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Noticia eliminada correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="calendar-days">
                <?php foreach ($newsByDate as $date => $newsList): ?>
                    <div class="day-column">
                        <div class="day-header"><?= date("d/m/Y", strtotime($date)) ?></div>
                        <?php if (empty($newsList)): ?>
                            <div class="text-muted text-center py-4">Sin publicaciones</div>
                        <?php else: ?>
                            <div class="day-news">
                                <?php foreach ($newsList as $row): 
                                    $img = !empty($row['crop3']) ? "./../".$row['crop3'] : "https://via.placeholder.com/300x200";
                                    $ahora = new DateTime();
                                    $fechaPublicacion = new DateTime($row['fecha_publicacion']);
                                    $estado = ($fechaPublicacion < $ahora) ? 
                                        '<span><i class="bi bi-check-circle"></i> Publicado</span>' :
                                        (($fechaPublicacion->format('Y-m-d') === $ahora->format('Y-m-d') && $fechaPublicacion > $ahora) ? 
                                            '<span><i class="bi bi-calendar-event-fill"></i> Por publicar</span>' :
                                            '<span><i class="bi bi-calendar-event"></i> Programado</span>');
                                ?>
                                <div class="noticias-card">
                                    <div class="card-header d-flex justify-content-between">
                                        <?= $estado ?>
                                        <span><i class="bi bi-clock"></i> <?= $fechaPublicacion->format('H:i') ?></span>
                                    </div>
                                    <img src="<?= htmlspecialchars($img) ?>" alt="" class="card-img-top">
                                    <h6><?= htmlspecialchars($row['titulo']) ?></h6>
                                    <small class="text-muted">
                                        👁 <?= number_format($row['vistas']) ?> | ❤️ <?= number_format($row['likes']) ?>
                                    </small>
                                    <?php if (!empty($ACL['editar']) || !empty($ACL['eliminar']) || !empty($ACL['leer'])): ?>
                                    <div class="noticias-actions">
                                        <?php if (!empty($ACL['editar'])): ?>
                                            <a href="editar.php?id=<?= $row['id'] ?>" class="btn btn-edit" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                        <?php endif; ?>
                                        <?php if (!empty($ACL['leer'])): ?>
                                            <a href="see.php?id=<?= $row['id'] ?>" class="btn btn-view" title="Ver Estadísticas"><i class="bi bi-bar-chart"></i></a>
                                        <?php endif; ?>
                                        <?php if (!empty($ACL['eliminar'])): ?>
                                            <button type="button" class="btn btn-delete" data-id="<?= $row['id'] ?>" data-titulo="<?= htmlspecialchars($row['titulo']) ?>" title="Eliminar"><i class="bi bi-trash"></i></button>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="botonesSemana d-flex justify-content-between align-items-center mt-4">
                <a href="?week=<?= $weekOffset - 1 ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Semana anterior</a>
                <h4>Semana del <?= $startOfWeek->format('d/m') ?> al <?= $endOfWeek->format('d/m/Y') ?></h4>
                <a href="?week=<?= $weekOffset + 1 ?>" class="btn btn-outline-secondary">Semana siguiente <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
</div>
<!-- Modal de Confirmación para Eliminar -->
<div id="modalOverlay" class="crop-modal" style="display: none;">
    <div class="card">
        <div class="crop-modal-content">
            <h3 id="modalTitle">Confirmar eliminación</h3>
            <p>¿Estás seguro de que deseas eliminar esta noticia? Esta acción no se puede deshacer.</p>
            <form id="modalForm" action="../controllers/eliminar_noticia.php" method="POST">
                <input type="hidden" name="id" id="modalId">
                <div class="crop-actions">
                    <button type="button" class="btn btn-secondary btn-cancel">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include("./../layout/footerAdmin.php"); ?>