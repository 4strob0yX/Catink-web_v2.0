<?php
include(__DIR__ . "/layout/header.php");
require_once(__DIR__ . "/data/conexion.php");
include(__DIR__ . "/views/helpers/videoEmbed.php");
require_once(__DIR__ . "/views/helpers/urlhelper.php");
// =====================
// Obtener todas las noticias con sus categorías
// =====================
$stmt = $con->prepare("
    SELECT 
        n.id,
        n.titulo,
        n.descripcion,
        n.crop1,
        n.crop2,
        n.crop3,
        n.fecha_publicacion AS fecha,
        n.likes,
        u.nombre AS nombre_u,
        GROUP_CONCAT(c.nombre SEPARATOR ',') AS categorias
    FROM noticias n
    INNER JOIN usuarios u ON n.autor = u.id_u
    LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
    LEFT JOIN categorias c ON nc.categoria_id = c.id_c
    WHERE n.fecha_publicacion <= NOW()
    GROUP BY n.id
    ORDER BY n.fecha_publicacion DESC;
");
$stmt->execute();
$result = $stmt->get_result();
$noticias = $result->fetch_all(MYSQLI_ASSOC);
// Últimas 3 noticias para sidebar
$ultimasNoticiasSidebar = array_slice($noticias, 0, 3);
// Noticias más populares (por likes)
$popularesNoticiasSidebar = $noticias;
usort($popularesNoticiasSidebar, fn($a,$b)=>$b['likes']-$a['likes']);
$popularesNoticiasSidebar = array_slice($popularesNoticiasSidebar, 0, 3);
// Noticias principales para slider y últimas
$slider = array_slice($noticias, 0, 5);
$ultimasNoticias = $noticias;
usort($ultimasNoticias, fn($a,$b)=>$b['likes']-$a['likes']);
$ultimasNoticias = array_slice($ultimasNoticias, 0, 7);
$noticiasMasRecientes = array_slice($noticias, 0, 6);
$noticiasMasRecientes2 = array_slice($noticias, 7, 11);
$noticiasMasRecientes3 = array_slice($noticias, 12, 17);
//Obtener banner publicidad
$stmt = $con->prepare("SELECT * FROM publicidad WHERE activo = 1 AND tipo = 1 and fecha_fin >= NOW() ORDER BY RAND() LIMIT 1");
$stmt->execute();
$publicidad = $stmt->get_result()->fetch_assoc();
//Obtener cuadro publicitario
$stmt = $con->prepare("SELECT * FROM publicidad WHERE activo = 1 AND tipo = 2 and fecha_fin >= NOW() ORDER BY RAND() LIMIT 1");
$stmt->execute();
$publicidadCuadro = $stmt->get_result()->fetch_assoc();
//obtener publicidad inferior
$stmt = $con->prepare("SELECT * FROM publicidad WHERE activo = 1 AND tipo = 1 ORDER BY RAND() LIMIT 1");
$stmt->execute();
$publicidadInferior = $stmt->get_result()->fetch_assoc();
// obtener videos
$stmt = $con->prepare("SELECT * FROM videos WHERE activo = 1 ORDER BY id_v DESC LIMIT 6");
$stmt->execute();
$vid = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
// obtener secciones (visibilidad de bloques)
$secciones = [];
$stmtSec = $con->prepare("SELECT nombre, estado FROM secciones");
$stmtSec->execute();
$resSec = $stmtSec->get_result();
while ($sec = $resSec->fetch_assoc()) {
    $secciones[$sec['nombre']] = $sec;
}
// ==============================
// NOTICIAS RECOMENDADAS
// ==============================
$recomendadas = [];
$sqlRec = "
    SELECT id, titulo, descripcion, crop3, fecha_publicacion, nombre
    FROM noticias, usuarios
    WHERE noticias.autor = usuarios.id_u and fecha_publicacion <= NOW()
    ORDER BY likes DESC, vistas DESC, fecha_publicacion DESC
    LIMIT 3
";
$stmtRec = $con->prepare($sqlRec);
$stmtRec->execute();
$recomendadas = $stmtRec->get_result();
// ==============================
// NOTICIAS RECIENTES
// ==============================
$stmtRecientes = $con->prepare("
    SELECT id, titulo, descripcion, crop3, fecha_publicacion, nombre
    FROM noticias, usuarios
    WHERE noticias.autor = usuarios.id_u and fecha_publicacion <= NOW()
    ORDER BY fecha_publicacion DESC
    LIMIT 4
");
$stmtRecientes->execute();
$recientes = $stmtRecientes->get_result();
?>
<!-- ===================== -->
<!-- SLIDER PRINCIPAL -->
<!-- ===================== -->
<?php if(!empty($slider)) : ?>
<div id="carouselExampleCaptions" class="carousel slide" data-bs-ride="carousel" data-bs-interval="10000">
    <div class="carousel-indicators custom-indicators">
        <?php foreach($slider as $i => $row): ?>
            <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="<?= $i ?>" class="<?= $i==0?'active':'' ?>">
                <div class="indicator-avatar">
                    <img src="./<?= htmlspecialchars($row['crop1'] ?? 'img/placeholder.jpg') ?>" alt="<?= htmlspecialchars($row['titulo']) ?>">
                    <svg viewBox="0 0 36 36"><circle cx="18" cy="18" r="16"></circle></svg>
                </div>
            </button>
        <?php endforeach; ?>
    </div>
    <div class="carousel-inner">
        <?php foreach($slider as $i => $row): ?>
            <div class="carousel-item <?= $i==0?'active':'' ?>" data-url="<?= newsUrl($row['id']) ?>">
                <picture>
                    <!-- MÓVIL usa crop2 -->
                    <source 
                        media="(max-width:768px)" 
                        srcset="./<?= htmlspecialchars($row['crop1'] ?? $row['crop2'] ?? 'img/placeholder.jpg') ?>">
                    <!-- DESKTOP usa crop1 -->
                    <img 
                        src="./<?= htmlspecialchars($row['crop2'] ?? 'img/placeholder.jpg') ?>" 
                        class="carousel-img"
                        alt="<?= htmlspecialchars($row['titulo']) ?>">
                </picture>
                <div class="carousel-caption caption-md">
                    <?php foreach(array_filter(array_map('trim', explode(',', $row['categorias'] ?? ''))) as $cat): ?>
                        <a href="<?= categoryUrl($cat) ?>" class="carousel-tag"><?= htmlspecialchars($cat) ?></a>
                    <?php endforeach; ?>
                    <h5><a href="./<?= newsUrl($row['id']) ?>" class="carousel-link"><?= htmlspecialchars($row['titulo']) ?></a></h5>
                    <p><?= htmlspecialchars($row['descripcion']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
<!-- ===================== -->
<!-- TOP NOTICIAS -->
<!-- ===================== -->
<?php if(count($ultimasNoticias) >= 7) : ?>
<div class="container mt-5">
    <div class="container-fluid">
        <h2><i class="bi bi-chat-left-dots"></i>  Top Publicaciones de la Semana</h2><br>
        <div class="row">
            <!-- Primeras 2 noticias principales -->
            <div class="col-md-8">
                <div class="news-card" data-url="./<?= newsUrl($ultimasNoticias[0]['id']) ?>">
                    <img src="<?= htmlspecialchars($ultimasNoticias[0]['crop2'] ?? $ultimasNoticias[0]['crop1'] ?? 'img/placeholder.jpg') ?>" alt="">
                    <div class="news-overlay">
                        <div class="news-tags">
                            <?php foreach(array_filter(array_map('trim', explode(',', $ultimasNoticias[0]['categorias'] ?? ''))) as $cat): ?>
                                <a href="<?= categoryUrl($cat) ?>" class="news-tag"><?= htmlspecialchars($cat) ?></a>
                            <?php endforeach; ?>
                        </div>
                        <div class="news-content">
                            <a href="./<?= newsUrl($ultimasNoticias[0]['id']) ?>" class="news-link-card">
                                <h3 class="title-limit-2"><?= htmlspecialchars($ultimasNoticias[0]['titulo']) ?></h3>
                            </a>
                            <p class="desc-limit-1"><?= htmlspecialchars($ultimasNoticias[0]['descripcion']) ?></p>
                        </div>
                        
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="news-card" data-url="./<?= newsUrl($ultimasNoticias[1]['id']) ?>">
                    <img src="<?= htmlspecialchars($ultimasNoticias[1]['crop3'] ?? $ultimasNoticias[1]['crop1'] ?? 'img/placeholder.jpg') ?>" alt="">
                    <div class="news-overlay">
                        <div class="news-tags">
                            <?php foreach(array_filter(array_map('trim', explode(',', $ultimasNoticias[1]['categorias'] ?? ''))) as $cat): ?>
                                <a href="<?= categoryUrl($cat) ?>" class="news-tag"><?= htmlspecialchars($cat) ?></a>
                            <?php endforeach; ?>
                        </div>
                        <div class="news-content">
                            <a href="./<?= newsUrl($ultimasNoticias[1]['id']) ?>" class="news-link-card">
                                <h3 class="title-limit-2"><?= htmlspecialchars($ultimasNoticias[1]['titulo']) ?></h3>
                            </a>
                            <p class="desc-limit-1"><?= htmlspecialchars($ultimasNoticias[1]['descripcion']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="news-card" data-url="./<?= newsUrl($ultimasNoticias[2]['id']) ?>">
                    <img src="<?= htmlspecialchars($ultimasNoticias[2]['crop3'] ?? $ultimasNoticias[2]['crop1'] ?? 'img/placeholder.jpg') ?>" alt="">
                    <div class="news-overlay">
                        <div class="news-tags">
                            <?php foreach(array_filter(array_map('trim', explode(',', $ultimasNoticias[2]['categorias'] ?? ''))) as $cat): ?>
                               <a href="<?= categoryUrl($cat) ?>" class="news-tag"><?= htmlspecialchars($cat) ?></a>
                            <?php endforeach; ?>
                        </div>
                        <div class="news-content">
                            <a href="./<?= newsUrl($ultimasNoticias[2]['id']) ?>" class="news-link-card">
                                <h3 class="title-limit-2"><?= htmlspecialchars($ultimasNoticias[2]['titulo']) ?></h3>
                            </a>
                            <p class="desc-limit-1"><?= htmlspecialchars($ultimasNoticias[2]['descripcion']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="news-card" data-url="./<?= newsUrl($ultimasNoticias[3]['id']) ?>">
                    <img src="<?= htmlspecialchars($ultimasNoticias[3]['crop3'] ?? $ultimasNoticias[3]['crop1'] ?? 'img/placeholder.jpg') ?>" alt="">
                    <div class="news-overlay">
                        <div class="news-tags">
                            <?php foreach(array_filter(array_map('trim', explode(',', $ultimasNoticias[3]['categorias'] ?? ''))) as $cat): ?>
                                <a href="<?= categoryUrl($cat) ?>" class="news-tag"><?= htmlspecialchars($cat) ?></a>
                            <?php endforeach; ?>
                        </div>
                        <div class="news-content">
                            <a href="./<?= newsUrl($ultimasNoticias[3]['id']) ?>" class="news-link-card">
                                <h3 class="title-limit-2"><?= htmlspecialchars($ultimasNoticias[3]['titulo']) ?></h3>
                            </a>
                            <p class="desc-limit-1"><?= htmlspecialchars($ultimasNoticias[3]['descripcion']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="news-card" data-url="./<?= newsUrl($ultimasNoticias[4]['id']) ?>">
                    <img src="<?= htmlspecialchars($ultimasNoticias[4]['crop3'] ?? $ultimasNoticias[4]['crop1'] ?? 'img/placeholder.jpg') ?>" alt="">
                    <div class="news-overlay">
                        <div class="news-tags">
                            <?php foreach(array_filter(array_map('trim', explode(',', $ultimasNoticias[4]['categorias'] ?? ''))) as $cat): ?>
                                <a href="<?= categoryUrl($cat) ?>" class="news-tag"><?= htmlspecialchars($cat) ?></a>
                            <?php endforeach; ?>
                        </div>
                        <div class="news-content">
                            <a href="./<?= newsUrl($ultimasNoticias[4]['id']) ?>" class="news-link-card">
                                <h3 class="title-limit-2"><?= htmlspecialchars($ultimasNoticias[4]['titulo']) ?></h3>
                            </a>
                            <p class="desc-limit-1"><?= htmlspecialchars($ultimasNoticias[4]['descripcion']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="news-card" data-url="./<?= newsUrl($ultimasNoticias[5]['id']) ?>">
                    <img src="<?= htmlspecialchars($ultimasNoticias[5]['crop3'] ?? $ultimasNoticias[5]['crop1'] ?? 'img/placeholder.jpg') ?>" alt="">
                    <div class="news-overlay">
                        <div class="news-tags">
                            <?php foreach(array_filter(array_map('trim', explode(',', $ultimasNoticias[5]['categorias'] ?? ''))) as $cat): ?>
                                <a href="<?= categoryUrl($cat) ?>" class="news-tag"><?= htmlspecialchars($cat) ?></a>
                            <?php endforeach; ?>
                        </div>
                        <div class="news-content">
                            <a href="./<?= newsUrl($ultimasNoticias[5]['id']) ?>" class="news-link-card">
                                <h3 class="title-limit-2"><?= htmlspecialchars($ultimasNoticias[5]['titulo']) ?></h3>
                            </a>
                            <p class="desc-limit-1"><?= htmlspecialchars($ultimasNoticias[5]['descripcion']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="news-card" data-url="./<?= newsUrl($ultimasNoticias[6]['id']) ?>">
                    <img src="<?= htmlspecialchars($ultimasNoticias[6]['crop2'] ?? $ultimasNoticias[6]['crop1'] ?? 'img/placeholder.jpg') ?>" alt="">
                    <div class="news-overlay">
                        <div class="news-tags">
                            <?php foreach(array_filter(array_map('trim', explode(',', $ultimasNoticias[6]['categorias'] ?? ''))) as $cat): ?>
                                <a href="<?= categoryUrl($cat) ?>" class="news-tag"><?= htmlspecialchars($cat) ?></a>
                            <?php endforeach; ?>
                        </div>
                        <div class="news-content">
                            <a href="./<?= newsUrl($ultimasNoticias[6]['id']) ?>" class="news-link-card">
                                <h3 class="title-limit-2"><?= htmlspecialchars($ultimasNoticias[6]['titulo']) ?></h3>
                            </a>
                            <p class="desc-limit-1"><?= htmlspecialchars($ultimasNoticias[6]['descripcion']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
        <!-- ===================== -->
        <!-- SIDEBAR -->
        <!-- ===================== -->
        <h2><i class="bi bi-newspaper"></i>  Lo más recientes</h2>
        <div class="row mt-5">
            <div class="col-md-9">
                <?php if(($secciones['publicidad']['estado'] ?? 0) == 1 && $publicidad) : ?>
                    <div class="ad-container">
                        <a href="<?php echo htmlspecialchars($publicidad['url']); ?>" class="banner-button" data-pub="<?php echo htmlspecialchars($publicidad['id_pub']); ?>">
                            <img src="<?php echo htmlspecialchars($publicidad['imagen']); ?>" alt="" class="banner">
                        </a>
                        <span class="ads-label">ADS</span>
                    </div>
                <?php endif; ?>
                <?php foreach($noticiasMasRecientes as $row): ?>
                    <div class="card mb-3" data-url="./<?= newsUrl($row['id']) ?>">
                        <div class="row row-no-gap">
                            <div class="col-md-4">
                                <img src="<?= htmlspecialchars($row['crop3']  ?? 'img/placeholder.jpg') ?>" alt="" class="card-img-left">
                            </div>
                            <div class="col-md-8">
                                <div class="card-body">
                                    <?php foreach(array_filter(array_map('trim', explode(',', $row['categorias'] ?? ''))) as $cat): ?>
                                        <a href="<?= categoryUrl($cat) ?>" class="tag-news"><?= htmlspecialchars($cat) ?></a>
                                    <?php endforeach; ?>
                                    <h4 class="card-title">
                                        <a href="./<?= newsUrl($row['id']) ?>" class="news-link"><?= htmlspecialchars($row['titulo']) ?></a>
                                    </h4>
                                    <p class="card-text"><?= htmlspecialchars($row['descripcion']) ?></p>
                                    <span class="text-muted"> 
                                        <?php
                                            $fecha_pub = strtotime($row['fecha']); // convierte la fecha de la BD a timestamp
                                            $ahora = time();                        // timestamp actual
                                            $diff = $ahora - $fecha_pub;            // diferencia en segundos
                                            if ($diff < 3600) { // menos de 1 hora
                                                $minutos = floor($diff / 60);
                                                echo "Publicado hace " . $minutos . " min";
                                            } elseif ($diff < 86400) { // menos de 24 horas
                                                $horas = floor($diff / 3600);
                                                echo "Publicado hace " . $horas . " hrs";
                                            } elseif ($diff < 172800) { // entre 24 y 48 horas
                                                echo "Publicado ayer";
                                            } else { // más de 48 horas
                                                echo "Publicado: " . date("M d", $fecha_pub);
                                            }
                                        ?>, 
                                        Por: <?= $row['nombre_u'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if(($secciones['videos']['estado'] ?? 0) == 1 && !empty($vid)) : ?>
                    <h3><i class="bi bi-camera-video"></i> Videos Destacados</h3>
                    <div class="video-carousel <?php echo count($vid) == 1? 'single-video':''; ?>">
                        <?php foreach($vid as $video): ?>
                            <div class="video-slide">
                                <?php echo bloquearEmbeds(renderizarVideo($video['url_v'])); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <br>
                <?php foreach($noticiasMasRecientes2 as $row): ?>
                    <div class="card mb-3" data-url="./<?= newsUrl($row['id']) ?>">
                        <div class="row row-no-gap">
                            <div class="col-md-4">
                                <img src="<?= htmlspecialchars($row['crop3']  ?? 'img/placeholder.jpg') ?>" alt="" class="card-img-left">
                            </div>
                            <div class="col-md-8">
                                <div class="card-body">
                                    <?php foreach(array_filter(array_map('trim', explode(',', $row['categorias'] ?? ''))) as $cat): ?>
                                        <a href="<?= categoryUrl($cat) ?>" class="tag-news"><?= htmlspecialchars($cat) ?></a>
                                    <?php endforeach; ?>
                                    <h4 class="card-title">
                                        <a href="./<?= newsUrl($row['id']) ?>" class="news-link"><?= htmlspecialchars($row['titulo']) ?></a>
                                    </h4>
                                    <p class="card-text"><?= htmlspecialchars($row['descripcion']) ?></p>
                                    <span class="text-muted"> 
                                        <?php
                                            $fecha_pub = strtotime($row['fecha']); // convierte la fecha de la BD a timestamp
                                            $ahora = time();                        // timestamp actual
                                            $diff = $ahora - $fecha_pub;            // diferencia en segundos
                                            if ($diff < 3600) { // menos de 1 hora
                                                $minutos = floor($diff / 60);
                                                echo "Publicado hace " . $minutos . " min";
                                            } elseif ($diff < 86400) { // menos de 24 horas
                                                $horas = floor($diff / 3600);
                                                echo "Publicado hace " . $horas . " hrs";
                                            } elseif ($diff < 172800) { // entre 24 y 48 horas
                                                echo "Publicado ayer";
                                            } else { // más de 48 horas
                                                echo "Publicado: " . date("M d", $fecha_pub);
                                            }
                                        ?>, 
                                        Por: <?= $row['nombre_u'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="news-carousel">
                    <?php foreach($noticiasMasRecientes3 as $row): ?>
                        <div class="news-slide">
                            <div class="news-card" data-url="./<?= newsUrl($row['id']) ?>">
                                <img src="<?= htmlspecialchars($row['crop3']  ?? 'img/placeholder.jpg') ?>" alt="">
                                <div class="news-overlay">
                                    <div class="news-tags">
                                        <?php foreach(array_filter(array_map('trim', explode(',', $row['categorias'] ?? ''))) as $cat): ?>
                                            <a href="<?= categoryUrl($cat) ?>" class="tag-news"><?= htmlspecialchars($cat) ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="news-content">
                                        <a href="./<?= newsUrl($row['id']) ?>" class="news-link-card"><?= htmlspecialchars($row['titulo']) ?></a>
                                        <span class="text-muted">
                                            <?php
                                                $fecha_pub = strtotime($row['fecha']); // convierte la fecha de la BD a timestamp
                                                $ahora = time();                        // timestamp actual
                                                $diff = $ahora - $fecha_pub;            // diferencia en segundos
                                                if ($diff < 3600) { // menos de 1 hora
                                                    $minutos = floor($diff / 60);
                                                    echo "Publicado hace " . $minutos . " min";
                                                } elseif ($diff < 86400) { // menos de 24 horas
                                                    $horas = floor($diff / 3600);
                                                    echo "Publicado hace " . $horas . " hrs";
                                                } elseif ($diff < 172800) { // entre 24 y 48 horas
                                                    echo "Publicado ayer";
                                                } else { // más de 48 horas
                                                    echo "Publicado: " . date("M d", $fecha_pub);
                                                }
                                            ?>, 
                                            Por: <?= $row['nombre_u'] ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <br>
                <?php if(($secciones['publicidad']['estado'] ?? 0) == 1 && $publicidadInferior) : ?>
                    <div class="ad-container">
                        <a href="<?php echo htmlspecialchars($publicidadInferior['url']); ?>" class="banner-button" data-pub="<?php echo htmlspecialchars($publicidadInferior['id_pub']); ?>">
                            <img src="<?php echo htmlspecialchars($publicidadInferior['imagen']); ?>" alt="" class="banner">
                        </a>
                        <span class="ads-label">ADS</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <div class="sidebar-wrapper">
                    <div class="card sidebar-card">
                        <div class="card-h">
                            <?php if(($secciones['publicidad']['estado'] ?? 0) == 1 && $publicidadCuadro) : ?>
                                <div class="ad-container">
                                    <a href="<?php echo htmlspecialchars($publicidadCuadro['url']); ?>" class="banner-button" data-pub="<?php echo htmlspecialchars($publicidadCuadro['id_pub']); ?>">
                                        <img src="<?php echo htmlspecialchars($publicidadCuadro['imagen']); ?>" class="banner-card-img-top">
                                    </a>
                                    <span class="ads-label">ADS</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h3><i class="bi bi-alarm"></i> Lo más nuevo</h3>
                            <br>
                            <div class="sidebar-news-list">
                                <?php foreach($ultimasNoticiasSidebar as $row): ?>
                                        <div class="cardSpecial row row-no-gap">
                                            <div class="col-md-4">
                                                <img src="./<?= htmlspecialchars($row['crop3'] ?? 'img/placeholder.jpg') ?>" alt="" class="imgCard card-img-left-rounded">
                                            </div>
                                            <div class="col-md-8">
                                                <div class="card-body">
                                                    <a href="./<?= newsUrl($row['id']) ?>" class="linkCard news-link title-limit-2"><?= htmlspecialchars($row['titulo']) ?></a>
                                                </div>
                                            </div>
                                        </div>
                                    <br>
                                <?php endforeach; ?>
                            </div>
                            <h3><i class="bi bi-fire"></i> Lo más popular</h3>
                            <br>
                            <div class="sidebar-news-list">
                                <?php foreach($popularesNoticiasSidebar as $row): ?>
                                        <div class="cardSpecial row row-no-gap">
                                            <div class="col-md-4">
                                                <img src="./<?= htmlspecialchars($row['crop3'] ?? 'img/placeholder.jpg') ?>" alt="" class="imgCard card-img-left-rounded">
                                            </div>
                                            <div class="col-md-8">
                                                <div class="card-body">
                                                    <a href="./<?= newsUrl($row['id']) ?>" class="linkCard news-link title-limit-2"><?= htmlspecialchars($row['titulo']) ?></a>
                                                </div>
                                            </div>
                                        </div>
                                    <br>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="card-footer">
                            <h3>Siguenos</h3>
                            <br>
                            <div class="social-links">
                                <a href="https://www.facebook.com/TheCatink?locale=es_LA" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                                <a href="https://x.com/The_Catink/" aria-label="Twitter / X"><i class="bi bi-twitter-x"></i></a>
                                <a href="https://www.instagram.com/the.catink/" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                                <a href="https://www.youtube.com/@thecatink" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                                <a href="https://www.tiktok.com/@thecatink" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
                                <!--<a href="#" aria-label="Twitch"><i class="bi bi-twitch"></i></a>-->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <div class="row">
            <div class="container">
              <h3><i class="bi bi-stars"></i> Recomendados para ti</h3>
              <br>
              <div class="row">
                <?php while($r = $recomendadas->fetch_assoc()): 
                    $img = !empty($r['crop3']) ? "./../".$r['crop3'] : "./../img/placeholder.jpg";
                ?>
                  <div class="col">
                      <div class="card h-100" data-url="./<?= newsUrl($r['id']) ?>">
                          <img src="<?= htmlspecialchars($img) ?>" class="card-img-top">
                          <div class="card-body">
                              <a href="<?= newsUrl($r['id']) ?>" class="news-link title-limit-1">
                                  <?= htmlspecialchars($r['titulo']) ?>
                              </a>
                              <small class="desc-limit-3">
                                <?= htmlspecialchars($r['descripcion']) ?>
                              </small>
                              <br>
                              <small class="text-muted">
                                  <?= date('d M Y', strtotime($r['fecha_publicacion'])) ?> - Por: <?= $r['nombre'] ?>
                              </small>
                          </div>
                      </div>
                  </div>
                <?php endwhile; ?>
              </div>
            </div>
          </div>
        <br>
        <div class="row">
            <div class="container">
              <h3><i class="bi bi-lightning-fill"></i> Noticias recientes</h3>
              <br>
              <div class="row">
                <?php while($r = $recientes->fetch_assoc()): 
                    $img = !empty($r['crop3']) ? "./../".$r['crop3'] : "./../img/placeholder.jpg";
                ?>
                  <div class="col">
                      <div class="card h-100"  data-url="./<?= newsUrl($r['id']) ?>">
                          <img src="<?= htmlspecialchars($img) ?>" class="card-img-top">
                          <div class="card-body">
                              <a href="<?= newsUrl($r['id']) ?>" class="news-link title-limit-1">
                                  <?= htmlspecialchars($r['titulo']) ?>
                              </a>
                              <small class="desc-limit-3">
                                <?= htmlspecialchars($r['descripcion']) ?>
                              </small>
                              <br>
                              <small class="text-muted">
                                  <?= date('d M Y', strtotime($r['fecha_publicacion'])) ?> - Por: <?= $r['nombre'] ?>
                              </small>
                          </div>
                      </div>
                  </div>
                <?php endwhile; ?>
              </div>
            </div>
          </div>
    </div>
</div>

<!-- Conteo de clicks -->
<script>
    document.querySelectorAll(".banner-button").forEach(banner => {
        banner.addEventListener("click", function(e) {
            e.preventDefault();//pausar redireccionamiento
            let url = this.href;
            let publicidadId = this.dataset.pub;
            let data = new FormData();
            data.append("publicidad_id", publicidadId);
            fetch("./controllers/publicidad_click.php", {
                method: "POST",
                body: data
            }).finally(()=>{
                window.location.href = url;//redireccionar despues de registrar click
            });
        });
    });
</script>
<!-- Conteo de tiempo y visualizaciones -->
<script>
    document.querySelectorAll(".banner-button").forEach(banner => {
        let publicidadId = banner.dataset.pub;
        let startTime = null;
        let totalTime = 0;
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    startTime = Date.now();
                } else if (startTime) {
                    totalTime += (Date.now() - startTime) / 1000;
                    startTime = null;
                }
            });
        }, { threshold: 0.5 });
        observer.observe(banner);
        setInterval(()=>{
            if (totalTime > 1) {
                let data = new FormData();
                data.append("publicidad_id", publicidadId);
                data.append("tiempo", Math.round(totalTime));
                navigator.sendBeacon("./controllers/publicidad_view.php", data);
                totalTime = 0;  
            }
        }, 5000);
    });
</script>
<?php include(__DIR__ . "/layout/footer.php"); ?>