<?php
session_start();
include("./../controllers/aclcontroller.php");
proteger('categorias','eliminar');
include("./../data/conexion.php");
header('Content-Type: application/json');
$id_c = intval($_POST['id_c'] ?? 0);
if($id_c <= 0){
    echo json_encode(['error'=>'ID de categoría inválido']);
    exit();
}
$stmt = $con->prepare("DELETE FROM noticia_categoria WHERE categoria_id=?");
$stmt->bind_param("i",$id_c);
$stmt->execute();
$stmt = $con->prepare("DELETE FROM categorias WHERE id_c=?");
$stmt->bind_param("i",$id_c);
if($stmt->execute()){
    echo json_encode(['success'=>true]);
}else{
    echo json_encode(['error'=>'No se pudo eliminar la categoría']);
}