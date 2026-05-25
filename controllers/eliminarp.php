<?php
session_start();
include("./aclcontroller.php");
proteger('publicidad','eliminar');
include("../data/conexion.php");
if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    // Obtener imagen
    $stmt = $con->prepare("SELECT imagen FROM publicidad WHERE id_pub = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $imagenPath = __DIR__ . "/../" . $row['imagen'];
        if (!empty($row['imagen']) && file_exists($imagenPath)) {
            unlink($imagenPath);
        }
    }
    // Eliminar relaciones
    $stmtCat = $con->prepare("DELETE FROM publicidad_categoria WHERE publicidad_id = ?");
    $stmtCat->bind_param("i", $id);
    $stmtCat->execute();
    // Eliminar publicidad
    $stmtDel = $con->prepare("DELETE FROM publicidad WHERE id_pub = ?");
    $stmtDel->bind_param("i", $id);
    $stmtDel->execute();
}
header("Location: ../views/publicidad.php?msg=eliminado");
exit;