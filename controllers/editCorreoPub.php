<?php
session_start();
include("./aclcontroller.php");
proteger('correos','editar');
include("../data/conexion.php");
include("../views/helpers/img.php");
$id= $_POST['id'];
$titulo     = $_POST['titulo'];
$contenido  = $_POST['contenido'];
$url        = $_POST['url'];
$envio = !empty($_POST['envio'])
    ? str_replace('T', ' ', $_POST['envio'])
    : null;
$carpetaDestino = __DIR__ . "/../img/correo";
$sql = $con->prepare("SELECT imagen FROM correos_publicitarios WHERE id_correo = ?");
$sql->bind_param("i", $id);
$sql->execute();
$result = $sql->get_result();
$correoActual = $result->fetch_assoc();
$imagenNombre = $correoActual['imagen'];
$huboNuevaImagen = isset($_FILES['imagenCorreo']) && $_FILES['imagenCorreo']['error'] !== 4;
if($huboNuevaImagen){
    if($_FILES['imagenCorreo']['error'] !== 0){
        die("Error al subir la nueva imagen");
    }
    $img = $_FILES['imagenCorreo'];
    $nuevaImagen = convertirImagenAWebp(
        $img,
        $carpetaDestino,
        1200,
        80
    );
    if(!$nuevaImagen){
        die("Error al procesar imagen");
    }
    $rutaVieja = $carpetaDestino . "/" . $imagenNombre;
    if(file_exists($rutaVieja)){
        unlink($rutaVieja);
    }
    $imagenNombre = $nuevaImagen;
}
$stmt = $con->prepare("
    UPDATE correos_publicitarios 
    SET titulo = ?, contenido = ?, imagen = ?, url_c = ?, envio = ?
    WHERE id_correo = ?
");
$stmt->bind_param(
    "sssssi",
    $titulo,
    $contenido,
    $imagenNombre,
    $url,
    $envio,
    $id
);
if($stmt->execute()){
    header("Location: ../views/correos.php?success=Correo actualizado correctamente");
} else {
    header("Location: ../views/correos.php?error=Error al actualizar correo");
}