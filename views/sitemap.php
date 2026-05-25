<?php
require_once(__DIR__ . '/helpers/urlhelper.php');
header('Content-Type: application/xml; charset=utf-8');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'www.catink.com.mx';
$baseUrl = $scheme . '://' . $host . basePath();
$urls = [];
$staticPages = [
    ['loc' => $baseUrl . '/', 'changefreq' => 'hourly', 'priority' => '1.0'],
    ['loc' => $baseUrl . '/noticias', 'changefreq' => 'hourly', 'priority' => '0.9'],
    ['loc' => $baseUrl . '/sobre-nosotros', 'changefreq' => 'monthly', 'priority' => '0.6'],
    ['loc' => $baseUrl . '/terminos-condiciones', 'changefreq' => 'yearly', 'priority' => '0.4'],
    ['loc' => $baseUrl . '/aviso-privacidad', 'changefreq' => 'yearly', 'priority' => '0.4'],
    ['loc' => $baseUrl . '/aviso-cookies', 'changefreq' => 'yearly', 'priority' => '0.4'],
    ['loc' => $baseUrl . '/suscripcion', 'changefreq' => 'weekly', 'priority' => '0.7'],
    ['loc' => $baseUrl . '/contactanos', 'changefreq' => 'monthly', 'priority' => '0.6'],
    ['loc' => $baseUrl . '/solicitud', 'changefreq' => 'monthly', 'priority' => '0.6'],
];
$urls = array_merge($urls, $staticPages);
mysqli_report(MYSQLI_REPORT_OFF);
$con = @new mysqli('localhost', 'u780114275_catink_news', '3N@KIrckPDm#', 'u780114275_cat_ink');
if (!$con->connect_error) {
    $con->set_charset('utf8mb4');
    $newsQuery = $con->prepare('SELECT id, fecha_publicacion FROM noticias WHERE fecha_publicacion <= NOW() ORDER BY fecha_publicacion DESC');
    if ($newsQuery && $newsQuery->execute()) {
        $newsResult = $newsQuery->get_result();
        while ($row = $newsResult->fetch_assoc()) {
            $urls[] = [
                'loc' => $baseUrl . newsUrl($row['id']),
                'lastmod' => date('c', strtotime($row['fecha_publicacion'])),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ];
        }
        $newsQuery->close();
    }
    $catQuery = $con->prepare('SELECT DISTINCT c.nombre FROM categorias c INNER JOIN noticia_categoria nc ON nc.categoria_id = c.id_c INNER JOIN noticias n ON n.id = nc.noticia_id WHERE n.fecha_publicacion <= NOW()');
    if ($catQuery && $catQuery->execute()) {
        $catResult = $catQuery->get_result();
        while ($row = $catResult->fetch_assoc()) {
            $urls[] = [
                'loc' => $baseUrl . categoryUrl($row['nombre']),
                'changefreq' => 'daily',
                'priority' => '0.7',
            ];
        }
        $catQuery->close();
    }
    $con->close();
}
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
    <url>
        <loc><?= htmlspecialchars($url['loc'], ENT_QUOTES, 'UTF-8') ?></loc>
<?php if (!empty($url['lastmod'])): ?>
        <lastmod><?= htmlspecialchars($url['lastmod'], ENT_QUOTES, 'UTF-8') ?></lastmod>
<?php endif; ?>
        <changefreq><?= htmlspecialchars($url['changefreq'], ENT_QUOTES, 'UTF-8') ?></changefreq>
        <priority><?= htmlspecialchars($url['priority'], ENT_QUOTES, 'UTF-8') ?></priority>
    </url>
<?php endforeach; ?>
</urlset>