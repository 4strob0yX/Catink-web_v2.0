<?php
session_start();
include("./aclcontroller.php");
proteger('publicidad','crear');
// ============================
// FUNCION GUARDAR IMAGEN BASE64 WEBP
// ============================
function guardarPublicidadBase64Webp($base64, $publicidadId, $calidad = 80) {
    if (empty($base64)) return null;

    if (!preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', $base64)) {
        return null;
    }
    $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
    $binario = base64_decode($base64);
    if ($binario === false) return null;
    $imagen = imagecreatefromstring($binario);
    if (!$imagen) return null;
    $timestamp = time();
    $nombre = "pub_{$publicidadId}_{$timestamp}.webp";
    $rutaFisica = __DIR__ . "/../img/publicidad/" . $nombre;
    // Crear carpeta si no existe
    if (!is_dir(__DIR__ . "/../img/publicidad")) {
        mkdir(__DIR__ . "/../img/publicidad", 0777, true);
    }
    imagewebp($imagen, $rutaFisica, $calidad);
    imagedestroy($imagen);
    return "img/publicidad/" . $nombre;
}
// ============================
// CONEXION
// ============================
include("../data/conexion.php");
// ============================
// DATOS FORMULARIO
// ============================
$titulo = $_POST['Titulo'] ?? '';
$tipo = $_POST['tipo'] ?? 1; // 1 banner, 2 cuadro
$url = $_POST['url'] ?? '';
$estado = $_POST['estado'] ?? 1;
$fechaInicio = $_POST['fechaInicio'] ?? null;
$fechaFin = $_POST['fechaFin'] ?? null;
$categorias = $_POST['Categorias'] ?? [];
// ============================
// VALIDACION
// ============================
if (empty($titulo) || empty($url)) {
    die("Datos incompletos");
}
// ============================
// INSERTAR PUBLICIDAD
// ============================
$sql = "INSERT INTO publicidad (titulo, imagen, tipo, url, activo, fecha_inicio, fecha_fin)
        VALUES (?, '', ?, ?, ?, ?, ?)";
$stmt = $con->prepare($sql);
$stmt->bind_param("sisiss", $titulo, $tipo, $url, $estado, $fechaInicio, $fechaFin);
$stmt->execute();
$publicidadId = $con->insert_id;
// ============================
// GUARDAR IMAGEN CROP
// ============================
$imagenFinal = guardarPublicidadBase64Webp($_POST['imagenCrop'] ?? null, $publicidadId);
// ============================
// ACTUALIZAR IMAGEN EN BD
// ============================
if ($imagenFinal) {
    $update = $con->prepare("UPDATE publicidad SET imagen=? WHERE id_pub=?");
    $update->bind_param("si", $imagenFinal, $publicidadId);
    $update->execute();
}
// ============================
// INSERTAR CATEGORIAS
// ============================
if (!empty($categorias)) {
    $stmtCat = $con->prepare("INSERT INTO publicidad_categoria (publicidad_id, categoria_id) VALUES (?, ?)");

    foreach ($categorias as $cat) {
        $stmtCat->bind_param("ii", $publicidadId, $cat);
        $stmtCat->execute();
    }
}
// ============================
// REDIRECCION
// ============================
header("Location: ./../views/publicidad.php");
exit;