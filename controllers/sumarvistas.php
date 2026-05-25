<?php
include("../data/conexion.php");
header('Content-Type: application/json');
try {
    if (!isset($_POST['noticia_id'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Falta el ID de noticia"]);
        exit;
    }
    $noticia_id = intval($_POST['noticia_id']);
    if ($noticia_id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "ID de noticia inválido"]);
        exit;
    }
    // Opcional: iniciar transacción si se planea guardar también tiempo de visualización
    $con->begin_transaction();
    $sql = "UPDATE noticias SET vistas = vistas + 1 WHERE id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $noticia_id);
    $stmt->execute();
    $con->commit();
    echo json_encode(["success" => true, "message" => "Vista sumada"]);
} catch (mysqli_sql_exception $e) {
    $con->rollback();
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>