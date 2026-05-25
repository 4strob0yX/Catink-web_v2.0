<?php
include("./../data/conexion.php");
header('Content-Type: application/json');
if (!isset($_GET['usuario'])) {
    echo json_encode(['existe' => false]);
    exit;
}
$usuario = trim($_GET['usuario']);
$stmt = $con->prepare("SELECT id_u FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();
echo json_encode([
    'existe' => $result->num_rows > 0
]);
