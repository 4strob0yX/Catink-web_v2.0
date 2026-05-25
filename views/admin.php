<?php 
include(__DIR__ . "/../layout/headerAdmin.php");
include(__DIR__ . "/../data/conexion.php");
if(isset($_POST['actualizarEstado']) && isset($_POST['secciones'])){
    foreach($_POST['secciones'] as $id => $datos){
        $estado = $datos['estado'] == '1' ? 1 : 0;
        $stmt = $con->prepare("UPDATE secciones SET estado = ? WHERE id_s = ?");
        $stmt->bind_param("ii", $estado, $id);
        $stmt->execute();
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}
// ====================================
// ACL GLOBAL
// ====================================
// Obtener permisos específicos del módulo "noticias"
$ACLNoticias = $_SESSION['ACL']['noticias'] ?? [
    'crear' => false,
    'leer' => false,
    'editar' => false,
    'eliminar' => false
];

// Verificar si el usuario puede ver admin.php
$puedeVerAdmin = false;
foreach($_SESSION['ACL'] as $mod){
    if($mod['leer']){
        $puedeVerAdmin = true;
        break;
    }
}
if(!$superadmin && !$puedeVerAdmin){
    session_destroy();
    header("Location: ./../index.php");
    exit();
}
// ====================================
// KPIs
// ====================================
$kpis = $con->query("
    SELECT
        (SELECT COUNT(*) FROM noticias) AS total_noticias,
        (SELECT COUNT(*) FROM noticias WHERE fecha_publicacion <= NOW()) AS publicadas,
        (SELECT COUNT(*) FROM noticias WHERE fecha_publicacion > NOW()) AS programadas,
        (SELECT COUNT(*) FROM noticias_stats) AS total_vistas,
        (SELECT COUNT(*) FROM noticia_likes) AS total_likes
")->fetch_assoc();
// Tiempo total de lectura
$tiempoTotal = $con->query("
    SELECT COALESCE(SUM(tiempo_segundos),0) AS tiempo_total
    FROM noticias_stats
")->fetch_assoc()['tiempo_total'];
// ====================================
// Últimas noticias (TOP 5) SIN DUPLICAR TIEMPO
// ====================================
$resultNoticias = $con->query("
    SELECT 
        n.id,
        n.titulo,
        n.descripcion,
        n.crop3,
        n.vistas,
        n.likes,
        n.fecha_publicacion,
        GROUP_CONCAT(DISTINCT c.nombre SEPARATOR ',') AS categorias,
        COALESCE((SELECT SUM(tiempo_segundos) FROM noticias_stats WHERE noticia_id = n.id), 0) AS tiempo_total_stats
    FROM noticias n
    LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
    LEFT JOIN categorias c ON nc.categoria_id = c.id_c
    GROUP BY n.id
    ORDER BY n.fecha_publicacion DESC
    LIMIT 5
");
$ultimasNoticias = [];
while($row = $resultNoticias->fetch_assoc()){
    $ultimasNoticias[] = $row;
}
$configResult = $con->query("SELECT * FROM secciones");
$config = [];
while($row = $configResult->fetch_assoc()){
    $config[] = $row;
}
function formatNumberShort($num){
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) {
        return round($num / 1000, 1) . 'K';
    }
    return $num;
}
?>
<div class="container-fluid">
    <!-- SALUDO -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Bienvenido, <?= htmlspecialchars($fila['usuario']) ?></h1>
    </div>
    <!-- BOTÓN NUEVA NOTICIA -->
    <?php if($ACLNoticias['crear']): ?>
        <a href="crear.php" class="btn btn-success"><i class="bi bi-plus-lg"></i> Nueva Noticia</a>
    <?php endif; ?> 
    <!-- Botón para abrir el modal -->
     <?php if($superadmin): ?>
        <a href="paginas.php" class="btn btn-success"><i class="bi bi-card-text"></i> Editar Páginas Informativas</a>
        <button id="btnAbrirModal" class="btn btn-success">
            <i class="bi bi-gear"></i> Gestionar Estado de Secciones
        </button>
    <?php endif; ?>
    <!-- KPIs -->
     <div class="card">
        <div class="card-body">
            <h5 class="card-title">KPIs</h5>
            <div class="row mb-4 kpi-row-mobile-fix">
                <?php 
                    $cards = [
                        ['Noticias', $kpis['total_noticias'], 'bi-newspaper', 'bg-primary'],
                        ['Publicadas', $kpis['publicadas'], 'bi-check-circle', 'bg-success'],
                        ['Programadas', $kpis['programadas'], 'bi-clock', 'bg-warning'],
                        ['Vistas', number_format($kpis['total_vistas']), 'bi-eye', 'bg-info'],
                        ['Likes', number_format($kpis['total_likes']), 'bi-heart', 'bg-danger'],
                        ['Tiempo (min)', number_format($tiempoTotal/60), 'bi-stopwatch', 'bg-secondary'],
                    ];
                    foreach($cards as $c):
                ?>
                <div class="col-md-2 col-6 mb-3 kpi-col-mobile-fix">
                    <div class="card text-center shadow-sm h-100">
                        <div class="card-body">
                            <i class="bi <?= $c[2] ?> fs-3 <?= $c[3] ?>"></i>
                            <h4 class="mb-0"><?= $c[1] ?></h4>
                            <small class="text-muted"><?= $c[0] ?></small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- FILTROS -->
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <h5>Filtros de Estadísticas</h5>
                    <div class="row g-3">
                        <div class="col-md-3 col-12">
                            <label>Fecha Inicio</label>
                            <input type="date" id="filterFechaInicio" class="form-control" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                        </div>
                        <div class="col-md-3 col-12">
                            <label>Fecha Fin</label>
                            <input type="date" id="filterFechaFin" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-secondary w-100" onclick="loadGlobalStats(); loadLikesStats();">
                                <i class="bi bi-funnel"></i> Aplicar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
     </div>
    <!-- GRÁFICOS -->
    <div class="charts-container">
        <h5 class="mb-3">Estadísticas Globales</h5>
        <div class="card mb-4">
            <div class="card-body">
                <div class="chart-wrapper">
                    <canvas id="globalChartVistas"></canvas>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <div class="chart-wrapper">
                    <canvas id="globalChartTiempo"></canvas>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <div class="chart-wrapper">
                    <canvas id="globalChartLikes"></canvas>
                </div>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <div class="chart-wrapper">
                    <canvas id="globalChartLikesRegion"></canvas>
                </div>
            </div>
        </div>
    </div>
    <!-- ÚLTIMAS NOTICIAS -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light"><h5>Últimas Noticias</h5></div>
            <div class="card-body">
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    <?php foreach($ultimasNoticias as $n):
                        $desc = mb_strimwidth($n['descripcion'] ?? '', 0, 80, '...');
                        $img = !empty($n['crop3']) ? "./../".$n['crop3'] : "./../img/placeholder.jpg";
                    ?>
                    <div class="col">
                        <div class="card">
                            <div class="card-header"><small><?= date('d/m/Y H:i', strtotime($n['fecha_publicacion'])) ?></small></div>
                            <img src="<?= htmlspecialchars($img) ?>" class="card-img-top">
                            <div class="card-body">
                                <div class="news-tags">
                                    <?php foreach(array_filter(array_map('trim', explode(',', $n['categorias'] ?? ''))) as $cat): ?>
                                        <span class="news-tag"><?= htmlspecialchars($cat) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <h5 class="card-title text-truncate"><?= htmlspecialchars($n['titulo']) ?></h5>
                                <p class="small text-muted"><?= htmlspecialchars($desc) ?></p>
                            </div>
                            <div class="card-footer d-flex card-especial">
                                <span><i class="bi bi-eye"></i> <?= formatNumberShort($n['vistas']) ?> </span>
                                <span><i class="bi bi-clock"></i> <?= number_format($n['tiempo_total_stats']/60,0) ?>m </span>
                                <span><i class="bi bi-heart"></i> <?= formatNumberShort($n['likes']) ?> </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div id="modalSecciones" class="modal-nativo">
        <div class="modal-content-nativo">
            <div class="modal-header-nativo">
                <h5>Estado de Secciones</h5>
                <span id="cerrarModal" class="cerrar">&times;</span>
            </div>
            <div class="modal-body-nativo">
                <form action="" method="POST">
                    <?php foreach($config as $sec): ?>
                        <div class="mb-2">
                            <label for="sec_<?= $sec['id_s'] ?>"><?= htmlspecialchars($sec['nombre']) ?></label>
                            <input type="hidden" name="secciones[<?= $sec['id_s'] ?>][id]" value="<?= $sec['id_s'] ?>">
                            <select name="secciones[<?= $sec['id_s'] ?>][estado]" id="sec_<?= $sec['id_s'] ?>">
                                <option value="1" <?= $sec['estado'] == '1' ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?= $sec['estado'] == '0' ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                    <?php endforeach; ?>
                    <div class="mt-3" style="text-align:right;">
                        <button type="button" id="btnCancelarModal" class="btn btn-secondary">Cancelar</button>
                        <button type="submit" class="btn btn-success" name="actualizarEstado">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<script>
    const charts = {};
    document.addEventListener("DOMContentLoaded", ()=>{ 
        loadGlobalStats(); 
        loadLikesStats();
        // Event listeners para filtros
        const filterInicio = document.getElementById('filterFechaInicio');
        const filterFin = document.getElementById('filterFechaFin');
        if (filterInicio) filterInicio.addEventListener('change', () => { loadGlobalStats(); loadLikesStats(); });
        if (filterFin) filterFin.addEventListener('change', () => { loadGlobalStats(); loadLikesStats(); });
    });
    // ================================
    // GLOBAL STATS
    // ================================
    function loadGlobalStats(){
        const f1=document.getElementById('filterFechaInicio').value;
        const f2=document.getElementById('filterFechaFin').value;
        fetch(`./../controllers/obtener_estadisticas_globales.php?fecha_inicio=${f1}&fecha_fin=${f2}`)
            .then(r=>r.json())
            .then(d=>{
                if (d.error) {
                    console.error('Error del servidor:', d.error);
                    return;
                }
                renderAreaChart('globalChartVistas', d, 'vistas', 'Vistas por categoría');
                renderAreaChart('globalChartTiempo', d, 'tiempo', 'Tiempo de lectura por categoría (Min)');
            })
            .catch(err => console.error('Error fetching global stats:', err));
    }
    // ================================
    // LIKES STATS
    // ================================
    function loadLikesStats(){
        const f1=document.getElementById('filterFechaInicio').value;
        const f2=document.getElementById('filterFechaFin').value;
        fetch(`./../controllers/obtener_estadisticas_likes.php?fecha_inicio=${f1}&fecha_fin=${f2}`)
            .then(r=>r.json())
            .then(d=>{
                if (d.error) {
                    console.error('Error del servidor:', d.error);
                    return;
                }
                renderAreaChart('globalChartLikes', d, 'likes', 'Likes por categoría');
                if (d.geo && d.geo.estados) {
                    renderBarChart('globalChartLikesRegion', d.geo.estados, 'Likes por Estado');
                }
            })
            .catch(err => console.error('Error fetching likes stats:', err));
    }
    // ================================
    // CHART AREA
    // ================================
    function getChartOptions(title){
        const mobile = window.matchMedia('(max-width: 768px)').matches;
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                title: { display: true, text: title },
                legend: { position: mobile ? 'bottom' : 'top' }
            },
            scales: {
                x: { ticks: { maxRotation: 45, minRotation: 45 } },
                y: { beginAtZero: true }
            }
        };
    }
    function renderAreaChart(id,data,metric,title){
        if(!data || !data.labels || data.labels.length === 0) {
            console.warn(`No data available for ${id}`);
            return;
        }
        
        const ctx=document.getElementById(id);
        if(!ctx) return;
        
        if(charts[id]) charts[id].destroy();

        const colors=['rgba(75,192,192,0.35)','rgba(255,99,132,0.35)','rgba(54,162,235,0.35)','rgba(255,159,64,0.35)','rgba(153,102,255,0.35)'];

        const datasets = data.categorias && Object.keys(data.categorias).length > 0 
            ? Object.entries(data.categorias).map(([cat,val],i)=>({
                label:cat,
                data:val[metric] || [],
                fill:true,
                tension:.4,
                borderWidth:2,
                backgroundColor:colors[i%colors.length],
                borderColor:colors[i%colors.length].replace('0.35','1')
            }))
            : [{label: 'Sin datos', data: Array(data.labels.length).fill(0)}];

        charts[id]=new Chart(ctx,{
            type:'line',
            data:{labels:data.labels,datasets},
            options:getChartOptions(title)
        });
    }
    // ================================
    // CHART BAR GEO
    // ================================
    function renderBarChart(id,geo,title){
        if(!geo || !geo.labels || geo.labels.length === 0) {
            console.warn(`No geo data available for ${id}`);
            return;
        }
        const ctx=document.getElementById(id);
        if(!ctx) return;
        if(charts[id]) charts[id].destroy();
        
        // Limitar a 10 estados pero mostrar todos los datos disponibles
        const limitedLabels = geo.labels.slice(0,10);
        const limitedValues = geo.values.slice(0,10);
        
        const dataset = {
            label:title,
            data:limitedValues,
            backgroundColor:'rgba(75,192,192,0.8)',
            borderColor:'rgba(75,192,192,1)',
            borderWidth:1
        };
        
        charts[id]=new Chart(ctx,{
            type:'bar',
            data:{
                labels:limitedLabels,
                datasets:[dataset]
            },
            options:getChartOptions(title)
        });
    }
    window.addEventListener('resize', () => {
        loadGlobalStats();
        loadLikesStats();
    });
</script>
<style>
.charts-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
}

.charts-container .card {
    width: 100%;
    max-width: calc(100% - 2rem);
}

.chart-wrapper {
    position: relative;
    width: 100%;
    height: 400px;
}

.chart-wrapper canvas {
    width: 100% !important;
    height: 400px !important;
}

@media (max-width: 768px){
    .kpi-row-mobile-fix{
        flex-wrap: wrap;
        display: flex;
    }
    .kpi-col-mobile-fix{
        flex: 1 1 60px;
    }
    .kpi-col-mobile-fix .card-body{
        padding: 0.75rem;
    }
    .kpi-col-mobile-fix h4{
        font-size: 1.05rem;
        padding: 0;
    }
    .chart-wrapper {
        height: 300px;
    }
    .chart-wrapper canvas {
        height: 300px !important;
    }
    #filterFechaInicio,
    #filterFechaFin{
        min-height: 42px;
    }
    #btnAbrirModal,
    a.btn.btn-success{
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>
<script>
const modal = document.getElementById('modalSecciones');
const btnAbrir = document.getElementById('btnAbrirModal');
const btnCerrar = document.getElementById('cerrarModal');
const btnCancelar = document.getElementById('btnCancelarModal');
// Abrir modal
if(btnAbrir){
    btnAbrir.addEventListener('click', ()=> modal.style.display = 'block');
}
// Cerrar modal
if(btnCerrar){
    btnCerrar.addEventListener('click', ()=> modal.style.display = 'none');
}
if(btnCancelar){
    btnCancelar.addEventListener('click', ()=> modal.style.display = 'none');
}
// Cerrar si se da click fuera del contenido
window.addEventListener('click', e => {
    if(e.target === modal) modal.style.display = 'none';
});
</script>
<?php include(__DIR__ . "/../layout/footerAdmin.php"); ?>