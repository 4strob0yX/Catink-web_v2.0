<?php
session_start();
include("./aclcontroller.php");
proteger('correos','crear');
include("../data/conexion.php");
include("../views/helpers/img.php");
$titulo     = $_POST['titulo'];
$contenido  = $_POST['contenido'];
$url        = $_POST['url'];
$envio = $_POST['envio'] ?? date('Y-m-d H:i:s');
$envio = str_replace('T', ' ', $envio);
$carpetaDestino = __DIR__ . "/../img/correo";
if (!is_dir($carpetaDestino)) {
    mkdir($carpetaDestino, 0777, true);
}
if(!isset($_FILES['imagenCorreo']) || $_FILES['imagenCorreo']['error'] !== 0){
    die("No se recibió la imagen correctamente");
}
$img = $_FILES['imagenCorreo'];
$imagenNombre = convertirImagenAWebp(
    $img,
    $carpetaDestino,
    1200,
    80
);
if(!$imagenNombre){
    die("Error al procesar imagen");
}
$stmt = $con->prepare("
    INSERT INTO correos_publicitarios 
    (titulo, contenido, imagen, url_c, envio) 
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param(
    "sssss",
    $titulo,
    $contenido,
    $imagenNombre,
    $url,
    $envio
);
if($stmt->execute()){
    header("Location: ../views/correos.php?success=1");
    exit();
}else{
    header("Location: ../views/correos.php?error=1");
    exit();
}