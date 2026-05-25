<?php
include("./../controllers/aclcontroller.php");
proteger('usuarios', 'crear');
include("./../data/conexion.php");
header('Content-Type: application/json');

// ========================
// Validar campos obligatorios
// ========================
if (!isset($_POST['nombre'], $_POST['usuario'], $_POST['email'], $_POST['password'], $_POST['confirm_password'])) {
    echo json_encode(['error' => 'Faltan datos obligatorios']);
    exit;
}
$nombre   = trim($_POST['nombre']);
$usuario  = trim($_POST['usuario']);
$email    = trim($_POST['email']);
$password = $_POST['password'];
$passConfirm = $_POST['confirm_password'];

// ========================
// Validar contraseñas
// ========================
if ($password !== $passConfirm) {
    echo json_encode(['error' => 'Las contraseñas no coinciden']);
    exit;
}

// ========================
// Validar usuario existente
// ========================
$stmt = $con->prepare("SELECT id_u FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['error' => 'El nombre de usuario ya existe']);
    exit;
}

// ========================
// FUNCIÓN BITMASK LINUX
// ========================
function calcPerm($arr){
    $perm = 0;
    if(isset($arr)){
        foreach($arr as $v){
            $perm += (int)$v;
        }
    }
    return $perm;
}

// ========================
// Obtener permisos
// ========================
$perm_publicidad    = calcPerm($_POST['publicidad'] ?? []);
$perm_noticias      = calcPerm($_POST['noticias'] ?? []);
$perm_categorias    = calcPerm($_POST['categorias'] ?? []);
$perm_suscripciones = calcPerm($_POST['suscripciones'] ?? []);
$perm_usuarios      = calcPerm($_POST['usuarios'] ?? []);
$perm_correos = calcPerm($_POST['correos'] ?? []);
$perm_videos = calcPerm($_POST['videos'] ?? []);

// ========================
// Hash seguro de contraseña
// ========================
$passHash = password_hash($password, PASSWORD_BCRYPT);

// ========================
// Insertar usuario
// ========================
$alt = $con->prepare("
INSERT INTO usuarios 
(nombre, usuario, correo, pass, perm_publicidad, perm_noticias, perm_categorias, perm_suscripciones, perm_usuarios, perm_correos, perm_videos)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$alt->bind_param(
    "ssssiiiiiii",
    $nombre,
    $usuario,
    $email,
    $passHash,
    $perm_publicidad,
    $perm_noticias,
    $perm_categorias,
    $perm_suscripciones,
    $perm_usuarios,
    $perm_correos,
    $perm_videos
);

if($alt->execute()){
    echo json_encode([
        'success' => 'Usuario creado correctamente',
        'permisos' => [
            'publicidad' => $perm_publicidad,
            'noticias' => $perm_noticias,
            'categorias' => $perm_categorias,
            'suscripciones' => $perm_suscripciones,
            'usuarios' => $perm_usuarios,
            'correos' => $perm_correos,
            'videos' => $perm_videos
        ]
    ]);
    header("Location: ./../views/usuarios.php");
    exit;
} else {
    echo json_encode(['error' => 'Error al registrar usuario']);
}
