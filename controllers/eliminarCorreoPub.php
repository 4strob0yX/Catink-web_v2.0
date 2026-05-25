<?php
session_start();
include("./aclcontroller.php");
proteger('correos','eliminar');
include("../data/conexion.php");
$id = $_POST['id'] ?? null;
if(!$id){
    header("Location: ../views/correos.php?error=1");
    exit();
}
// obtener nombre de imagen
$sql = $con->prepare("
    SELECT imagen 
    FROM correos_publicitarios 
    WHERE id_correo = ?
");
$sql->bind_param("i", $id);
$sql->execute();
$result = $sql->get_result();
$correo = $result->fetch_assoc();
if(!$correo){
    header("Location: ../views/correos.php?error=2");
    exit();
}
$imagen = $correo['imagen'];
$rutaImagen = __DIR__ . "/../img/correo/" . $imagen;
// eliminar registro BD
$stmt = $con->prepare("
    DELETE FROM correos_publicitarios 
    WHERE id_correo = ?
");
$stmt->bind_param("i", $id);
if($stmt->execute()){

    // eliminar imagen física
    if(file_exists($rutaImagen)){
        unlink($rutaImagen);
    }
    header("Location: ../views/correos.php?deleted=1");
    exit();
}else{
    header("Location: ../views/correos.php?error=3");
    exit();
}