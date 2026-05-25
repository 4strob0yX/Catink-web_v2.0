<?php
include("../data/conexion.php");
include("../views/helpers/helper.php");
include("../views/helpers/urlhelper.php");
// ============================
// OBTENER DATOS DEL FORMULARIO
// ============================
$usuario = $_POST['usuario'] ?? '';
$pass = $_POST['pass'] ?? '';
if (empty($usuario) || empty($pass)) {
    header('Location: ' . basePath() . '/index.php?error=1');
    exit();
}
// ============================
// CONSULTA SEGURA (Prepared Statement)
// ============================
$stmt = $con->prepare("SELECT * FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $fila = $result->fetch_assoc();
    $passCorrecta = false;
    // ============================
    // VERIFICAR PASSWORD (plana o hash)
    // ============================
    if ($pass === $fila['pass']) {
        // contraseña en texto plano
        $passCorrecta = true;
    } elseif (password_verify($pass, $fila['pass'])) {
        // contraseña hasheada
        $passCorrecta = true;
    }
    if ($passCorrecta) {
        session_start();
        session_regenerate_id(true); // seguridad
        $_SESSION['usuario'] = $fila['usuario'];
        $_SESSION['ACL']= mapearPermisos($fila);
        // redirigir a admin si es superadmin
        if(esSuperAdmin($fila)){
            $_SESSION['superadmin'] = true;
            header('Location: ' . basePath() . '/views/admin.php');
            exit();
        }
        // redirigir al primer modulo de lectura
        $modulo = primerModuloLectura($_SESSION['ACL']);
        if($modulo){
            $_SESSION['superadmin'] = false;
            header('Location: ' . basePath() . '/' . $modulo);
            exit();
        }
    } else {
        header('Location: ' . basePath() . '/index.php?error=1');
        exit();
    }
} else {
    // Usuario no encontrado
    header('Location: ' . basePath() . '/index.php?error=1');
    exit();
}
?>