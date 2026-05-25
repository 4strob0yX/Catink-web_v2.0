<?php
session_start();
if(!isset($_SESSION['superadmin'])){
    header('Location: admin.php');
    exit();
}
include("./../data/conexion.php");
$id = $_POST['id'];
$hora = $_POST['hora'];
$estado = $_POST['estado'];
$sql = "UPDATE programacion_correos SET hora = ?, estado = ? WHERE id_programacion = $id";
$stmt = $con->prepare($sql);
$stmt->bind_param("ss", $hora, $estado);
$stmt->execute();
header('Location: ./../views/suscripciones.php');
exit();
