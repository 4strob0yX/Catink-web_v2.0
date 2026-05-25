<?php
require_once("./../data/conexion.php");
require_once("./helpers/urlhelper.php");

// ==============================
// PAGINACIÓN
// ==============================
$porPagina = 10;
$pagina = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $porPagina;

// ==============================
// PARÁMETROS
// ==============================
$q         = trim(urldecode($_GET['q'] ?? $_GET['query'] ?? $_GET['search'] ?? ''));
$categoria = trim(urldecode($_GET['cat'] ?? $_GET['category'] ?? $_GET['categoria'] ?? ''));

// ==============================
// SEO DINÁMICO
// ==============================
if ($q !== '') {
    $pageTitle = "Resultados para: $q";
    $pageDescription = "Busca noticias de anime y manga sobre $q en CatInk.";
} elseif ($categoria !== '') {
    $pageTitle = "Noticias de $categoria";
    $pageDescription = "Últimas noticias de $categoria en CatInk.";
} else {
    $pageTitle = "Últimas noticias de anime y manga";
    $pageDescription = "Descubre las últimas noticias del mundo del anime y manga.";
}

if ($pagina > 1) {
    $pageTitle .= " - Página $pagina";
    $pageDescription .= " Página $pagina de resultados.";
}

// ==============================
// CANONICAL
// ==============================
$canonical = "https://www.catink.com.mx";

if ($categoria !== '') {
    $canonical .= "/categoria/" . urlencode($categoria);
} elseif ($q !== '') {
    $canonical .= "/buscar/" . urlencode($q);
}

if ($pagina > 1) {
    $canonical .= "?page=" . $pagina;
}

// ==============================
// HEADER (IMPORTANTE ARRIBA)
// ==============================
include("./../layout/header.php");

// ==============================
// CONSULTA PRINCIPAL
// ==============================
if ($q !== '') {
    $stmt = $con->prepare("
        SELECT n.id, n.titulo, n.descripcion, n.crop3, n.fecha_publicacion,
               GROUP_CONCAT(c.nombre SEPARATOR ',') AS categorias
        FROM noticias n
        LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
        LEFT JOIN categorias c ON nc.categoria_id = c.id_c
        WHERE n.fecha_publicacion <= NOW()
          AND (n.titulo LIKE ? OR n.descripcion LIKE ? OR n.contenido LIKE ?)
        GROUP BY n.id
        ORDER BY n.fecha_publicacion DESC
        LIMIT ? OFFSET ?
    ");
    $like = "%$q%";
    $stmt->bind_param("sssii", $like, $like, $like, $porPagina, $offset);

} elseif ($categoria !== '') {
    $stmt = $con->prepare("
        SELECT n.id, n.titulo, n.descripcion, n.crop3, n.fecha_publicacion,
               GROUP_CONCAT(c.nombre SEPARATOR ',') AS categorias
        FROM noticias n
        INNER JOIN noticia_categoria nc_filter ON n.id = nc_filter.noticia_id
        INNER JOIN categorias c_filter ON nc_filter.categoria_id = c_filter.id_c AND c_filter.nombre = ?
        LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
        LEFT JOIN categorias c ON nc.categoria_id = c.id_c
        WHERE n.fecha_publicacion <= NOW()
        GROUP BY n.id
        ORDER BY n.fecha_publicacion DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("sii", $categoria, $porPagina, $offset);

} else {
    $stmt = $con->prepare("
        SELECT n.id, n.titulo, n.descripcion, n.crop3, n.fecha_publicacion,
               GROUP_CONCAT(c.nombre SEPARATOR ',') AS categorias
        FROM noticias n
        LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
        LEFT JOIN categorias c ON nc.categoria_id = c.id_c
        WHERE n.fecha_publicacion <= NOW()
        GROUP BY n.id
        ORDER BY n.fecha_publicacion DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $porPagina, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

// ==============================
// TOTAL
// ==============================
if ($q !== '') {
    $stmtTotal = $con->prepare("
        SELECT COUNT(DISTINCT n.id) as total
        FROM noticias n
        LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
        LEFT JOIN categorias c ON nc.categoria_id = c.id_c
        WHERE n.fecha_publicacion <= NOW()
        AND (n.titulo LIKE ? OR n.descripcion LIKE ? OR n.contenido LIKE ?)
    ");
    $stmtTotal->bind_param("sss", $like, $like, $like);

} elseif ($categoria !== '') {
    $stmtTotal = $con->prepare("
        SELECT COUNT(DISTINCT n.id) as total
        FROM noticias n
        INNER JOIN noticia_categoria nc ON n.id = nc.noticia_id
        INNER JOIN categorias c ON nc.categoria_id = c.id_c
        WHERE n.fecha_publicacion <= NOW()
        AND c.nombre = ?
    ");
    $stmtTotal->bind_param("s", $categoria);

} else {
    $stmtTotal = $con->prepare("
        SELECT COUNT(*) as total FROM noticias
        WHERE fecha_publicacion <= NOW()
    ");
}

$stmtTotal->execute();
$totalNoticias = $stmtTotal->get_result()->fetch_assoc()['total'];
$totalpaginas = ceil($totalNoticias / $porPagina);

// ==============================
// SIDEBAR
// ==============================
$stmtUltimas = $con->prepare("
    SELECT id, titulo, crop3
    FROM noticias
    WHERE fecha_publicacion <= NOW()
    ORDER BY fecha_publicacion DESC
    LIMIT 3
");
$stmtUltimas->execute();
$ultimas = $stmtUltimas->get_result();

$stmtPopulares = $con->prepare("
    SELECT id, titulo, crop3
    FROM noticias
    ORDER BY likes DESC
    LIMIT 3
");
$stmtPopulares->execute();
$populares = $stmtPopulares->get_result();

$stmt = $con->prepare("SELECT * FROM publicidad WHERE activo = 1 AND tipo = 2 ORDER BY RAND() LIMIT 1");
$stmt->execute();
$publicidadCuadro = $stmt->get_result()->fetch_assoc();

// ==============================
// BREADCRUMBS JSON-LD
// ==============================
$breadcrumbList = [
    "@context" => "https://schema.org",
    "@type" => "BreadcrumbList",
    "itemListElement" => [
        [
            "@type" => "ListItem",
            "position" => 1,
            "name" => "Inicio",
            "item" => "https://www.catink.com.mx/"
        ]
    ]
];

$position = 2;

if ($categoria !== '') {
    $breadcrumbList['itemListElement'][] = [
        "@type" => "ListItem",
        "position" => $position++,
        "name" => $categoria,
        "item" => categoryUrl($categoria)
    ];
}

if ($q !== '') {
    $breadcrumbList['itemListElement'][] = [
        "@type" => "ListItem",
        "position" => $position++,
        "name" => "Resultados para: $q",
        "item" => $canonical
    ];
}
?>

<!-- SEO -->
<link rel="canonical" href="<?= $canonical ?>">
<?php if ($q !== ''): ?>
<meta name="robots" content="noindex, follow">
<?php endif; ?>

<script type="application/ld+json">
<?= json_encode($breadcrumbList, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) ?>
</script>

<div class="container mt-5">
  <div class="container-fluid">

    <!-- TITULO CONTEXTUAL -->
    <?php if ($q !== ''): ?>
      <h4>Resultados para: <strong><?= htmlspecialchars($q) ?></strong></h4>
    <?php elseif ($categoria !== ''): ?>
      <h4>Categoría: <strong><?= htmlspecialchars($categoria) ?></strong></h4>
    <?php endif; ?>
    <br>
    <div class="row">

      <!-- MAIN -->
      <div class="col-md-9">

        <?php if ($result->num_rows === 0): ?>
          <p>No se encontraron resultados.</p>
        <?php endif; ?>

        <?php while ($row = $result->fetch_assoc()): ?>
          <?php
            $cats = !empty($row['categorias']) ? explode(",", $row['categorias']) : [];
            $cats = array_map('trim', $cats);

            if ($categoria !== '' && in_array($categoria, $cats)) {
                $cats = array_diff($cats, [$categoria]);
                array_unshift($cats, $categoria);
            }

            $img = !empty($row['crop3']) ? "./../".$row['crop3'] : "./../img/placeholder.jpg";
          ?>

          <div class="card mb-3" data-url="<?= newsUrl($row['id']) ?>">
            <div class="row row-no-gap">

              <div class="col-md-4">
                <img src="<?= htmlspecialchars($img) ?>" class="card-img-left">
              </div>

              <div class="col-md-8">
                <div class="card-body">

                  <?php foreach ($cats as $cat): ?>
                    <a href="<?= categoryUrl($cat) ?>" class="news-tag"><?= htmlspecialchars($cat) ?></a>
                  <?php endforeach; ?>

                  <h5 class="card-title">
                    <a href="<?= newsUrl($row['id']) ?>" class="news-link">
                      <?= htmlspecialchars($row['titulo']) ?>
                    </a>
                  </h5>

                  <p><?= htmlspecialchars($row['descripcion']) ?></p>

                  <small><?= date('d M Y', strtotime($row['fecha_publicacion'])) ?></small>

                </div>
              </div>

            </div>
          </div>
        <?php endwhile; ?>

        <!-- PAGINACIÓN -->
        <div class="pagination-wrapper">
            <ul class="pagination">
                <?php if ($pagina > 1): ?>
                    <li>
                        <a href="?page=<?= $pagina-1 ?>&q=<?= urlencode($q) ?>&cat=<?= urlencode($categoria) ?>">
                            « Anterior
                        </a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalpaginas; $i++): ?>
                    <li class="<?= $i == $pagina ? 'active' : '' ?>">
                        <a href="?page=<?= $i ?>&q=<?= urlencode($q) ?>&cat=<?= urlencode($categoria) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
                <?php if ($pagina < $totalpaginas): ?>
                    <li>
                        <a href="?page=<?= $pagina+1 ?>&q=<?= urlencode($q) ?>&cat=<?= urlencode($categoria) ?>">
                            Siguiente »
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
      </div>

      <!-- SIDEBAR -->
      <div class="col-md-3">
        <div class="sidebar-wrapper">
          <div class="card sidebar-card">
            <?php if($secciones['publicidad']['estado'] == 1) : ?>
                <div class="ad-container">
                    <a href="<?php echo htmlspecialchars($publicidadCuadro['url']); ?>" class="banner-button" data-pub="<?php echo htmlspecialchars($publicidadCuadro['id_pub']); ?>">
                        <img src="./../<?php echo htmlspecialchars($publicidadCuadro['imagen']); ?>" class="banner-card-img-top">
                    </a>
                    <span class="ads-label">ADS</span>
                </div>
            <?php endif; ?>
            <div class="card-body">
              <h3><i class="bi bi-alarm"></i> Lo más nuevo</h3>
              <br>
              <ul class="list-group list-group-flush mb-3">
                <?php while ($row = $ultimas->fetch_assoc()): ?>
                  <div class="cardSpecial row row-no-gap">
                        <div class="col-md-4">
                            <img src="./../<?=$row['crop3']?>" class="imgCard card-img-left-rounded">
                        </div>
                        <div class="col-md-8">
                            <div class="card-body">
                                <a href="<?= newsUrl($row['id']) ?>" class="linkCard news-link title-limit-2"><?= htmlspecialchars($row['titulo']) ?></a>
                            </div>
                        </div>
                    </div>
                    <br>
                <?php endwhile; ?>
              </ul>
              <h3><i class="bi bi-fire"></i> Lo más popular</h3>
              <br>
              <ul class="list-group list-group-flush">
                <?php while ($row = $populares->fetch_assoc()): ?>
                  <div class="cardSpecial row row-no-gap">
                        <div class="col-md-4">
                            <img src="./../<?=$row['crop3']?>" class="imgCard card-img-left-rounded">
                        </div>
                        <div class="col-md-8">
                            <div class="card-body">
                                <a href="<?= newsUrl($row['id']) ?>" class="linkCard news-link title-limit-2"><?= htmlspecialchars($row['titulo']) ?></a>
                            </div>
                        </div>
                    </div>
                    <br>
                <?php endwhile; ?>
              </ul>
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
  </div>
</div>
<?php include("./../layout/footer.php"); ?>