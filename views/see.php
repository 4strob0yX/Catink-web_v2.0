<?php
include("./../layout/headerAdmin.php");
include("./../data/conexion.php");
$ACL = $_SESSION['ACL']['noticias'] ?? [
    "crear" => false,
    "leer" => false,
    "editar" => false,
    "eliminar" => false
];
if (empty($ACL['leer'])) {
    header("Location: admin.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: ./../views/contenidos.php");
    exit;
}

// ===================== NOTICIA + TOTALES =====================
$stmt = $con->prepare("
    SELECT 
        n.*,
        (SELECT COUNT(*) FROM noticias_stats WHERE noticia_id = n.id) AS vistas,
        (SELECT COUNT(*) FROM noticia_likes WHERE noticia_id = n.id) AS likes,
        (SELECT COALESCE(SUM(tiempo_segundos),0) FROM noticias_stats WHERE noticia_id = n.id) AS tiempo_total
    FROM noticias n
    WHERE n.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row) {
    header("Location: ./../views/contenidos.php");
    exit;
}

$noticiaId = $row['id'];

function quillPreview($html, $limit = 500) {
    $text = trim(strip_tags(html_entity_decode($html)));
    return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit) . '...' : $text;
}
?>
<script>
    const ACL = <?= json_encode($ACL) ?>;
</script>
<div class="container-fluid">
    <h1>Noticia: <?= htmlspecialchars($row['titulo']) ?></h1>
    <div class="row mt-3">
        <!-- TARJETA -->
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <img src="./../<?= htmlspecialchars($row['crop1'] ?? 'img/placeholder.jpg') ?>" class="card-img-top">
                <div class="card-body">
                    <p class="text-muted"><?= htmlspecialchars($row['descripcion']) ?></p>
                    <p><small><?= htmlspecialchars(quillPreview($row['contenido'])) ?></small></p>
                </div>
                <div class="card-footer">
                    <p><i class="bi bi-eye"></i> Vistas: <?= number_format($row['vistas']) ?></p>
                    <p><i class="bi bi-heart"></i> Likes: <?= number_format($row['likes']) ?></p>
                    <p><i class="bi bi-clock"></i>  Tiempo total: <?= number_format($row['tiempo_total']) ?> s</p>
                    <?php if ($ACL['editar']): ?>
                        <div class="row">
                            <?php if ($ACL['editar']): ?>
                                <div class="col">
                                    <a href="editar.php?id=<?= $row['id'] ?>" class="btn btn-edit" title="Editar"><i class="bi bi-pencil-square"></i></a>
                                </div>
                            <?php endif; ?>
                            <?php if ($ACL['leer']): ?>
                                <div class="col">
                                    <a href="newsAdmin.php?id=<?= $row['id'] ?>" class="btn btn-edit" title="Ver"><i class="bi bi-eye"></i></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- DASHBOARD -->
        <div class="col-md-8 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h4>Estadísticas</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col">
                            <label>Inicio</label>
                            <input type="date" id="filterFechaInicio" class="form-control" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                        </div>
                        <div class="col">
                            <label>Fin</label>
                            <input type="date" id="filterFechaFin" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col d-flex align-items-end">
                            <button class="btn btn-secondary w-100" onclick="loadGlobalStats(); loadLikesStats();">Aplicar</button>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><canvas id="chartVistas"></canvas></div>
                        <div class="col-md-6"><canvas id="chartTiempo"></canvas></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><canvas id="chartLikes"></canvas></div>
                        <div class="col-md-6"><canvas id="chartLikesRegion"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    const charts = {};
    document.addEventListener("DOMContentLoaded", () => {
        loadGlobalStats();
        loadLikesStats();
    });
    function loadGlobalStats() {
        const fi = filterFechaInicio.value;
        const ff = filterFechaFin.value;
        fetch(`./../controllers/obtener_estadisticas.php?noticia_id=<?= $noticiaId ?>&fecha_inicio=${fi}&fecha_fin=${ff}`)
            .then(r => r.json())
            .then(d => {
                renderChart("chartVistas", d.labels, d.vistas, "Vistas");
                renderChart("chartTiempo", d.labels, d.tiempoPromedio, "Tiempo promedio (s)");
            });
    }
    function loadLikesStats() {
        const fi = filterFechaInicio.value;
        const ff = filterFechaFin.value;
        fetch(`./../controllers/obtener_likes.php?noticia_id=<?= $noticiaId ?>&fecha_inicio=${fi}&fecha_fin=${ff}`)
            .then(r => r.json())
            .then(d => {
                renderChart("chartLikes", d.labels, d.likes, "Likes");
                renderBar("chartLikesRegion", d.geo.paises.labels, d.geo.paises.values, "Likes por país");
            });
    }
    function renderChart(id, labels, data, label) {
        const ctx = document.getElementById(id);
        if(charts[id]) charts[id].destroy();
        charts[id] = new Chart(ctx, {
            type: "line",
            data: { labels, datasets:[{ label, data, fill:true }] },
            options: { responsive:true}
        });
    }
    function renderBar(id, labels, data, label) {
        const ctx = document.getElementById(id);
        if(charts[id]) charts[id].destroy();
        charts[id] = new Chart(ctx, {
            type: "bar",
            data: { labels, datasets:[{ label, data }] },
            options: { responsive:true }
        });
    }
</script>
<?php include("./../layout/footerAdmin.php"); ?>