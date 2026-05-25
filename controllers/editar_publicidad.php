<?php
session_start();
include("./aclcontroller.php");
proteger('publicidad','editar');
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
$id_pub = $_POST['id_pub'] ?? null;
$titulo = $_POST['Titulo'] ?? '';
$tipo = $_POST['tipo'] ?? 1; // 1 banner, 2 cuadro
$url = $_POST['url'] ?? '';
$estado = $_POST['estado'] ?? 1;
$fechaInicio = $_POST['fechaInicio'] ?? null;
$fechaFin = $_POST['fechaFin'] ?? null;
$categorias = $_POST['Categorias'] ?? [];
$imagenCrop = $_POST['imagenCrop'] ?? null;

// ============================
// VALIDACION
// ============================
if (empty($id_pub) || empty($titulo) || empty($url)) {
    die("Datos incompletos");
}

// ============================
// ACTUALIZAR DATOS TEXTO
// ============================
$sql = "UPDATE publicidad SET titulo=?, tipo=?, url=?, activo=?, fecha_inicio=?, fecha_fin=? WHERE id_pub=?";
$stmt = $con->prepare($sql);
$stmt->bind_param("sisissi", $titulo, $tipo, $url, $estado, $fechaInicio, $fechaFin, $id_pub);
$stmt->execute();

// ============================
// ACTUALIZAR IMAGEN (SI SE ENVIO UNA NUEVA)
// ============================
if (!empty($imagenCrop)) {
    // Primero obtenemos la imagen anterior para borrarla (opcional, buena práctica)
    $stmtImg = $con->prepare("SELECT imagen FROM publicidad WHERE id_pub = ?");
    $stmtImg->bind_param("i", $id_pub);
    $stmtImg->execute();
    $resImg = $stmtImg->get_result();
    $rowImg = $resImg->fetch_assoc();
    
    // Guardar nueva imagen
    $imagenFinal = guardarPublicidadBase64Webp($imagenCrop, $id_pub);
    
    if ($imagenFinal) {
        // Actualizar en BD
        $update = $con->prepare("UPDATE publicidad SET imagen=? WHERE id_pub=?");
        $update->bind_param("si", $imagenFinal, $id_pub);
        $update->execute();
        
        // Borrar imagen vieja del servidor si existe y es diferente
        if ($rowImg && !empty($rowImg['imagen']) && file_exists(__DIR__ . "/../" . $rowImg['imagen'])) {
            unlink(__DIR__ . "/../" . $rowImg['imagen']);
        }
    }
}

// ============================
// ACTUALIZAR CATEGORIAS
// ============================
// Primero eliminamos las relaciones existentes
$stmtDel = $con->prepare("DELETE FROM publicidad_categoria WHERE publicidad_id = ?");
$stmtDel->bind_param("i", $id_pub);
$stmtDel->execute();

// Luego insertamos las seleccionadas
if (!empty($categorias)) {
    $stmtCat = $con->prepare("INSERT INTO publicidad_categoria (publicidad_id, categoria_id) VALUES (?, ?)");
    foreach ($categorias as $cat) {
        $stmtCat->bind_param("ii", $id_pub, $cat);
        $stmtCat->execute();
    }
}

// ============================
// REDIRECCION
// ============================
header("Location: ./../views/publicidad.php");
exit;
