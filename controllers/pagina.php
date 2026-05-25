<?php
include("./../data/conexion.php");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $contenido = $_POST['contenido'];

    $stmt = $con->prepare("UPDATE paginas SET nombre_pag=?, contenido_pag=? WHERE id_pag=?");
    $stmt->bind_param("ssi", $nombre, $contenido, $id);
    if ($stmt->execute()) {
        header("Location: ./../views/paginas.php?msg=actualizado");
        exit();
    } else {
        echo "Error al actualizar: " . $stmt->error;
    }
}