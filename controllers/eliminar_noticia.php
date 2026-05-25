<?php
session_start();
include("./aclcontroller.php");
proteger('noticias','eliminar');
include("../data/conexion.php");
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    // ==========================
    // OPCIONAL: BORRAR IMÁGENES FISICAS
    // ==========================
    $stmtImgs = $con->prepare("SELECT crop1, crop2, crop3 FROM noticias WHERE id = ?");
    $stmtImgs->bind_param("i", $id);
    $stmtImgs->execute();
    $resImgs = $stmtImgs->get_result();
    if ($resImgs->num_rows) {
        $imgs = $resImgs->fetch_assoc();
        foreach (['crop1','crop2','crop3'] as $c) {
            if (!empty($imgs[$c]) && file_exists(__DIR__ . "/../" . $imgs[$c])) {
                unlink(__DIR__ . "/../" . $imgs[$c]);
            }
        }
    }
    $stmtImgs->close();
    // ==========================
    // ELIMINAR NOTICIA (CASCADE BORRA stats, likes y categoria)
    // ==========================
    $stmt = $con->prepare("DELETE FROM noticias WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: ../views/contenidos.php?msg=eliminado");
    } else {
        header("Location: ../views/contenidos.php?error=no_eliminado");
    }
    $stmt->close();
} else {
    header("Location: ../views/contenidos.php");
}
?>