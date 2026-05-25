<?php
if(session_status() == PHP_SESSION_NONE){
    session_start();
}
require_once "./../views/helpers/helper.php";
require_once "./../views/helpers/acl.php";
if (!isset($_SESSION["usuario"])) {
    header("Location: ./../index.php");
    exit();
}
//detectar modulo automaticamente
$archivoActual = basename($_SERVER['PHP_SELF'], ".php");
$mapVistaModulo = [
    'cats' => 'categorias',
    'contenidos' => 'noticias',
    'publicidad' => 'publicidad',
    'suscripciones' => 'suscripciones',
    'usuarios' => 'usuarios',
    'correos' => 'correos',
    'videos' => 'videos'
];
$superadmin = $_SESSION['superadmin'];
// cargar acl globalmente
$ACL = null;
if(isset($mapVistaModulo[$archivoActual])){
    $ACL = cargarACL($mapVistaModulo[$archivoActual]);
}
// proteccion para admin.php
if($archivoActual === "admin" && !$superadmin){
    // si no tiene lectura en ningun modulo → fuera
    $tieneAcceso = false;
    foreach($_SESSION['ACL'] as $mod){
        if($mod['leer']){
            $tieneAcceso = true;
            break;
        }
    }
    if(!$tieneAcceso){
        session_destroy();
        header("Location: ./../index.php");
        exit();
    }
}
$usuario = $_SESSION['usuario'];
include("./../data/conexion.php");
// Obtener usuario desde la base de datos actualizada
$stmt = $con->prepare("SELECT id_u, nombre, usuario FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();
$fila = $result->fetch_assoc();
if (!$fila) {
    // Si el usuario no existe en la BD, destruir sesión y redirigir
    session_destroy();
    header("Location: ./../index.php");
    exit();
}
?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CatInk News - Admin</title>
  <link rel="stylesheet" href="https://www.catink.com.mx/CSS/styles.css">
  <link rel="stylesheet" href="https://www.catink.com.mx/CSS/admin.css">
  <link rel="icon" type="image/png" href="https://www.catink.com.mx/img/catink-icon.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css">
  <script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
  <script src="https://unpkg.com/quill-image-resize-module/image-resize.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
    <?php if($ACL): ?>
        <script>
            const ACL = {
                crear: <?= $ACL['crear'] ? 'true' : 'false' ?>,
                leer: <?= $ACL['leer'] ? 'true' : 'false' ?>,
                editar: <?= $ACL['editar'] ? 'true' : 'false' ?>,
                eliminar: <?= $ACL['eliminar'] ? 'true' : 'false' ?>
            };
        </script>
    <?php endif; ?>
</head>
<body class="has-sidebar">
<div class="sidebar">
    <div class="logotipo">
        <img id="icon" src="./../img/logo_alt_alt.jpg" alt="Logo">
    </div>
    <div id="user">
        <h4><?= htmlspecialchars($fila['usuario']) ?></h4>
    </div>
    <ul class="sidebar-menu">
        <li class="sidebar-menu-item">
            <a href="./../index.php" class="sidebar-menu-link"><i class="bi bi-house-fill"></i> Home</a>
        </li>
        <?php if ($superadmin): ?>
            <li class="sidebar-menu-item">
                <a href="./admin.php" class="sidebar-menu-link"><i class="bi bi-house"></i> Inicio</a>
            </li>
        <?php endif; ?>
        <?php if (($_SESSION['ACL']['categorias']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./cats.php" class="sidebar-menu-link">Categorias</a>
            </li>
        <?php endif; ?>
        <?php if (($_SESSION['ACL']['noticias']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./contenidos.php" class="sidebar-menu-link">Contenido</a>
            </li>
        <?php endif; ?>
        <?php if (($_SESSION['ACL']['publicidad']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./publicidad.php" class="sidebar-menu-link">Publicidad</a>
            </li>
        <?php endif; ?>
        <?php if (($_SESSION['ACL']['correos']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./correos.php" class="sidebar-menu-link">Correos</a>
            </li>
        <?php endif; ?>
        <?php if (($_SESSION['ACL']['suscripciones']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./suscripciones.php" class="sidebar-menu-link">Suscripciones</a>
            </li>
        <?php endif; ?>
        <?php if (($_SESSION['ACL']['usuarios']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./usuarios.php" class="sidebar-menu-link">Usuarios</a>
            </li>
        <?php endif; ?>
        <?php if (($_SESSION['ACL']['videos']['leer']?? false)): ?>
            <li class="sidebar-menu-item">
                <a href="./videos.php" class="sidebar-menu-link">Videos</a>
            </li>
        <?php endif; ?>
    </ul>
    <div class="sidebar-footer">
      <button id="themeToggle" class="btn btn-icon" title="Cambiar tema">🌙</button>
      <a href="./../controllers/logoutcontroller.php" class="sidebar-menu-link"><i class="bi bi-box-arrow-right"></i> Salir</a>
    </div>
</div>
<div id="sidebarBackdrop" class="sidebar-backdrop"></div>
<main class="site-main">
  <button id="sidebarToggle" class="btn btn-outline-secondary d-md-none mb-3 siderbar-toggle-mobile">
    <i class="bi bi-list"></i> Menú
  </button>
