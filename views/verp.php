<?php
include("./../layout/headerAdmin.php");
include("./../data/conexion.php");
$ACL = $_SESSION['ACL']['publicidad'] ?? [
    "crear" => false,
    "leer" => false,
    "editar" => false,
    "eliminar" => false
];
if (!$ACL['leer']) {
    header("Location: publicidad.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: ./../views/publicidad.php");
    exit;
}
// ===================== PUBLICIDAD + TOTALES =====================
$stmt = $con->prepare("
    SELECT 
        p.*,
        (SELECT COUNT(*) FROM publicidad_views WHERE publicidad_id = p.id_pub) AS vistas,
        (SELECT COALESCE(AVG(tiempo_segundos),0) FROM publicidad_views WHERE publicidad_id = p.id_pub) AS tiempo_promedio,
        (SELECT COALESCE(SUM(tiempo_segundos),0) FROM publicidad_views WHERE publicidad_id = p.id_pub) AS tiempo_total,
        (SELECT COUNT(*) FROM publicidad_clicks WHERE publicidad_id = p.id_pub) AS clicks
    FROM publicidad p
    WHERE p.id_pub = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
if (!$row) {
    header("Location: ./../views/publicidad.php");
    exit;
}

$pubId = $row['id_pub'];
?>
<script>
    const ACL = <?= json_encode($ACL) ?>;
</script>
<div class="container-fluid">
    <h1>Publicidad: <?= htmlspecialchars($row['titulo']) ?></h1>
    <div class="row mt-3">
        <!-- TARJETA -->
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <img src="./../<?= htmlspecialchars($row['imagen'] ?? 'img/placeholder.jpg') ?>" class="card-img-top">
                <div class="card-body">
                    <p>Tipo: <?= $row['tipo'] == 1 ? 'Banner Largo' : 'Banner Cuadrado' ?></p>
                    <p>URL: <a href="<?= htmlspecialchars($row['url']) ?>" target="_blank"><?= htmlspecialchars($row['url']) ?></a></p>
                </div>
                <div class="card-footer">
                    <p><i class="bi bi-eye"></i> Vistas: <?= number_format($row['vistas']) ?></p>
                    <p><i class="bi bi-stopwatch"></i> Tiempo promedio: <?= number_format($row['tiempo_promedio']) ?> s</p>
                    <p><i class="bi bi-stopwatch-fill"></i> Tiempo total: <?= number_format($row['tiempo_total']) ?> s</p>
                    <p><i class="bi bi-mouse"></i> Clicks: <?= number_format($row['clicks']) ?></p>
                    <div class="row">
                        <?php if($ACL['editar']): ?>
                            <div class="col">
                                <a href="editarp.php?id=<?= $row['id_pub'] ?>" class="btn btn-edit" title="Editar"><i class="bi bi-pencil-square"></i></a>
                            </div>
                        <?php endif; ?>
                    </div>
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
                            <button class="btn btn-secondary w-100" onclick="loadViewsStats(); loadClicksStats();">Aplicar</button>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><canvas id="chartVistas"></canvas></div>
                        <div class="col-md-6"><canvas id="chartTiempo"></canvas></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><canvas id="chartClicks"></canvas></div>
                        <div class="col-md-6"><canvas id="chartClicksRegion"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const charts = {};
document.addEventListener("DOMContentLoaded", () => {
    loadViewsStats();
    loadClicksStats();
});

function loadViewsStats() {
    const fi = filterFechaInicio.value;
    const ff = filterFechaFin.value;
    fetch(`./../controllers/obtener_views_pub.php?pub_id=<?= $pubId ?>&fecha_inicio=${fi}&fecha_fin=${ff}`)
        .then(r => r.json())
        .then(d => {
            renderChart("chartVistas", d.labels, d.vistas, "Vistas");
            renderChart("chartTiempo", d.labels, d.tiempoPromedio, "Tiempo promedio (s)");
        });
}

function loadClicksStats() {
    const fi = filterFechaInicio.value;
    const ff = filterFechaFin.value;
    fetch(`./../controllers/obtener_clicks_pub.php?pub_id=<?= $pubId ?>&fecha_inicio=${fi}&fecha_fin=${ff}`)
        .then(r => r.json())
        .then(d => {
            renderChart("chartClicks", d.labels, d.clicks, "Clicks");
            renderBar("chartClicksRegion", d.geo.paises.labels, d.geo.paises.values, "Clicks por país");
        });
}

function renderChart(id, labels, data, label) {
    const ctx = document.getElementById(id);
    if(charts[id]) charts[id].destroy();
    charts[id] = new Chart(ctx, {
        type: "line",
        data: { labels, datasets:[{ label, data, fill:true }] },
        options: { responsive:true }
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
