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
$id = intval($_GET['id'] ?? 1);
// ==============================
// Obtener noticia con autor y categorías
// ==============================
$sql = "
    SELECT n.*, u.nombre AS autor_nombre,
           GROUP_CONCAT(c.nombre SEPARATOR ',') AS categorias
    FROM noticias n
    LEFT JOIN usuarios u ON n.autor = u.id_u
    LEFT JOIN noticia_categoria nc ON n.id = nc.noticia_id
    LEFT JOIN categorias c ON nc.categoria_id = c.id_c
    WHERE n.id = ? AND n.fecha_publicacion <= NOW()
    GROUP BY n.id
";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$noticia = $result->fetch_assoc();
if (!$noticia) die("Noticia no encontrada");
// Parsear categorías
$cats = !empty($noticia['categorias']) ? explode(',', $noticia['categorias']) : [];
$cats = array_map('trim', $cats);
?>
<div class="container">
    <div class="container-fluid">
        <button class="btn btn-secondary" title="Volver" onclick="history.back()">
            <i class="bi bi-arrow-left"></i> Regresar
        </button>
        <br>
        <div class="col-md-8" style="margin-top: 15px;">
            <div class="container-noticia">
            <!-- Categorías -->
            <?php foreach ($cats as $cat): ?>
                <span class="news-tag"><?= htmlspecialchars($cat) ?></span>
            <?php endforeach; ?>
            <h1><?= htmlspecialchars($noticia['titulo']) ?></h1>
            <p class="descripcion"><?= nl2br(htmlspecialchars($noticia['descripcion'])) ?></p>
            <p class="meta">
                Por <strong><?= htmlspecialchars($noticia['autor_nombre'] ?? 'Desconocido') ?></strong> —
                <?= date("d/m/Y H:i", strtotime($noticia['fecha_publicacion'])) ?>
            </p>
            <button id="likeBtn" class="like-btn" data-id="<?= $id ?>">
                ❤️ Like <span id="likeCount"><?= $noticia['likes'] ?></span>
            </button>
            <?php
                $img = !empty($noticia['crop1']) ? "./../" . htmlspecialchars($noticia['crop1']) : "./../img/placeholder.jpg";
            ?>
            <img src="<?= $img ?>" alt="" class="img-titular">
            <!-- Contenido completo de la noticia -->
            <div class="ql-editor">
                <?= $noticia['contenido'] ?>
            </div>
            <div class="ad-container">
                <a href="<?= $publicidad['url'] ?>" class="banner-button" data-pub="<?= $publicidad['id_pub'] ?>">
                    <img src="./../<?= $publicidad['imagen'] ?>" alt="" class="banner">
                </a>
                <span class="ads-label">ADS</span>
            </div>
            </div>
        </div>
    </div>
</div>
<?php include("./../layout/footerAdmin.php"); ?>