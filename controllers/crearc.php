<?php
session_start();
include("./../controllers/aclcontroller.php");
proteger('categorias','crear');
include("./../data/conexion.php");
header('Content-Type: application/json');
$nombre = trim($_POST['nombre'] ?? '');
if($nombre === ''){
    echo json_encode(['error'=>'El nombre es obligatorio']);
    exit();
}
$stmt = $con->prepare("SELECT id_c FROM categorias WHERE nombre = ?");
$stmt->bind_param("s",$nombre);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows > 0){
    echo json_encode(['error'=>'Ya existe una categoría con ese nombre']);
    exit();
}
$stmt = $con->prepare("INSERT INTO categorias(nombre) VALUES(?)");
$stmt->bind_param("s",$nombre);
if($stmt->execute()){
    echo json_encode(['success'=>true]);
}else{
    echo json_encode(['error'=>'No se pudo crear la categoría']);
}