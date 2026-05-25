<?php
include(__DIR__ . "/../data/conexion.php");
header('Content-Type: application/json');

// ============================
// VALIDACIONES
// ============================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Método no permitido']));
}

$noticia_id = intval($_POST['noticia_id'] ?? 0);
$parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
$nombre = trim($_POST['nombre'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$contenido = trim($_POST['contenido'] ?? '');

if (!$noticia_id || !$nombre || !$contenido) {
    http_response_code(400);
    exit(json_encode(['error' => 'Faltan campos obligatorios (noticia_id, nombre, contenido)']));
}

// Validar longitud máxima
$maxLongitud = obtenerConfig($con, 'max_longitud', 1000);
if (mb_strlen($contenido) > $maxLongitud) {
    http_response_code(400);
    exit(json_encode(['error' => "El comentario excede el límite de {$maxLongitud} caracteres"]));
}

// Validar que la noticia exista
$stmtN = $con->prepare("SELECT id FROM noticias WHERE id = ?");
$stmtN->bind_param("i", $noticia_id);
$stmtN->execute();
if ($stmtN->get_result()->num_rows === 0) {
    http_response_code(404);
    exit(json_encode(['error' => 'Noticia no encontrada']));
}

// ============================
// COOLDOWN POR IP
// ============================
$ip = getUserIP();
$cooldown = obtenerConfig($con, 'cooldown_segundos', 60);

$stmtCool = $con->prepare("SELECT fecha FROM comentarios WHERE ip = ? ORDER BY fecha DESC LIMIT 1");
$stmtCool->bind_param("s", $ip);
$stmtCool->execute();
$ultimoComentario = $stmtCool->get_result()->fetch_assoc();

if ($ultimoComentario) {
    $diff = time() - strtotime($ultimoComentario['fecha']);
    if ($diff < $cooldown) {
        $espera = $cooldown - $diff;
        http_response_code(429);
        exit(json_encode(['error' => "Espera {$espera} segundos antes de comentar de nuevo"]));
    }
}

// ============================
// FILTRO DE PALABRAS PROHIBIDAS
// ============================
$filtroActivo = obtenerConfig($con, 'filtro_activo', 1);
$contenidoOriginal = $contenido;
$palabrasDetectadas = [];
$estado = 'pendiente';

if ($filtroActivo) {
    $resultado = filtrarContenido($con, $contenido);
    $contenido = $resultado['contenido'];
    $palabrasDetectadas = $resultado['detectadas'];
    
    if (!empty($palabrasDetectadas)) {
        // Determinar acción según severidad máxima
        $accionFinal = determinarAccion($resultado['acciones']);
        switch ($accionFinal) {
            case 'rechazar':
                http_response_code(403);
                exit(json_encode(['error' => 'Tu comentario contiene lenguaje que no está permitido']));
            case 'censurar':
                $estado = 'censurado';
                break;
            case 'revisar':
                $estado = 'pendiente';
                break;
        }
    }
}

// Auto-aprobar si está configurado y no fue censurado
$autoAprobar = obtenerConfig($con, 'auto_aprobar', 0);
if ($autoAprobar && $estado === 'pendiente') {
    $estado = 'aprobado';
}

// ============================
// GEOLOCALIZACIÓN
// ============================
$geo = obtenerGeo($ip);

// ============================
// INSERTAR COMENTARIO
// ============================
$stmt = $con->prepare("
    INSERT INTO comentarios (noticia_id, parent_id, nombre, correo, contenido, contenido_original, estado, ip, pais, region)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
    "iissssssss",
    $noticia_id, $parent_id, $nombre, $correo, $contenido, $contenidoOriginal, $estado, $ip, $geo['pais'], $geo['region']
);
$stmt->execute();
$comentarioId = $con->insert_id;

// ============================
// LOG DE MODERACIÓN AUTOMÁTICA
// ============================
if (!empty($palabrasDetectadas)) {
    $palabrasStr = implode(', ', $palabrasDetectadas);
    $accionLog = ($estado === 'censurado') ? 'censurar' : 'revisar';
    $stmtLog = $con->prepare("
        INSERT INTO moderacion_log (comentario_id, accion, motivo, palabras_detectadas, automatico)
        VALUES (?, ?, 'Filtro automático de lenguaje', ?, 1)
    ");
    $stmtLog->bind_param("iss", $comentarioId, $accionLog, $palabrasStr);
    $stmtLog->execute();
}

// ============================
// RESPUESTA
// ============================
$mensaje = match($estado) {
    'aprobado' => 'Comentario publicado',
    'censurado' => 'Comentario publicado (algunas palabras fueron moderadas)',
    'pendiente' => 'Comentario enviado, será revisado por un moderador',
    default => 'Comentario procesado'
};

echo json_encode([
    'success' => true,
    'mensaje' => $mensaje,
    'estado' => $estado,
    'id' => $comentarioId
]);

// ============================
// FUNCIONES AUXILIARES
// ============================
function obtenerConfig($con, $clave, $default = '') {
    $stmt = $con->prepare("SELECT valor FROM moderacion_config WHERE clave = ?");
    $stmt->bind_param("s", $clave);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? $row['valor'] : $default;
}

function filtrarContenido($con, $contenido) {
    $stmt = $con->prepare("SELECT palabra, severidad, accion FROM palabras_prohibidas WHERE activo = 1");
    $stmt->execute();
    $palabras = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $detectadas = [];
    $acciones = [];
    $contenidoFiltrado = $contenido;
    
    // Normalizar el contenido para detectar variaciones (p3nd3j0, m.i.e.r.d.a, pUt@)
    $contenidoNormalizado = normalizarTexto($contenido);
    
    foreach ($palabras as $p) {
        $palabraNorm = normalizarTexto($p['palabra']);
        // Buscar en texto normalizado (detecta variaciones)
        $patron = '/\b' . preg_quote($palabraNorm, '/') . '\b/iu';
        if (preg_match($patron, $contenidoNormalizado)) {
            $detectadas[] = $p['palabra'];
            $acciones[] = $p['accion'];
            // Censurar en el contenido original usando patrón flexible
            $patronFlex = construirPatronFlexible($p['palabra']);
            $contenidoFiltrado = preg_replace($patronFlex, str_repeat('*', mb_strlen($p['palabra'])), $contenidoFiltrado);
        }
    }
    
    return [
        'contenido' => $contenidoFiltrado,
        'detectadas' => $detectadas,
        'acciones' => $acciones
    ];
}

function normalizarTexto($texto) {
    // Mapa de sustituciones: símbolo/número → letra que imita
    $mapa = [
        '0' => 'o', '1' => 'i', '3' => 'e', '4' => 'a', '5' => 's',
        '6' => 'g', '7' => 't', '8' => 'b', '9' => 'g',
        '@' => 'a', '$' => 's', '!' => 'i', '¡' => 'i',
        '(' => 'c', '{' => 'c', '[' => 'c',
        '|' => 'l', '+' => 't', '#' => 'h',
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
        'ñ' => 'n',
    ];
    
    $texto = mb_strtolower($texto);
    // Quitar puntos, guiones, espacios y underscores entre letras (m.i.e.r.d.a → mierda)
    $texto = preg_replace('/(?<=\w)[.\-_\s]+(?=\w)/u', '', $texto);
    // Aplicar mapa de sustituciones
    $texto = strtr($texto, $mapa);
    // Eliminar caracteres repetidos excesivos (puuutaaa → puta)
    $texto = preg_replace('/(.)\1{2,}/u', '$1', $texto);
    
    return $texto;
}

function construirPatronFlexible($palabra) {
    // Construye un regex que matchea la palabra con separadores opcionales entre letras
    // y variaciones de caracteres (para censurar el texto original)
    $chars = mb_str_split(mb_strtolower($palabra));
    $partes = [];
    
    $variantes = [
        'a' => '[a@4áàäâ]', 'b' => '[b8]', 'c' => '[c({\\[]', 'd' => '[d]',
        'e' => '[e3éèëê]', 'f' => '[f]', 'g' => '[g69]', 'h' => '[h#]',
        'i' => '[i1!¡íìïî|]', 'j' => '[j]', 'k' => '[k]', 'l' => '[l1|]',
        'm' => '[m]', 'n' => '[nñ]', 'o' => '[o0óòöô]', 'p' => '[p]',
        'q' => '[q]', 'r' => '[r]', 's' => '[s5$]', 't' => '[t7+]',
        'u' => '[u úùüû]', 'v' => '[v]', 'w' => '[w]', 'x' => '[x]',
        'y' => '[y]', 'z' => '[z]'
    ];
    
    foreach ($chars as $c) {
        $lower = mb_strtolower($c);
        if (isset($variantes[$lower])) {
            $partes[] = $variantes[$lower];
        } else {
            $partes[] = preg_quote($c, '/');
        }
    }
    
    // Permitir separadores opcionales entre caracteres (puntos, guiones, espacios, etc.)
    $separador = '[.\-_\s*]*';
    $regex = '/\b' . implode($separador, $partes) . '\b/iu';
    
    return $regex;
}

function determinarAccion($acciones) {
    // Prioridad: rechazar > censurar > revisar
    if (in_array('rechazar', $acciones)) return 'rechazar';
    if (in_array('censurar', $acciones)) return 'censurar';
    return 'revisar';
}

function getUserIP() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function obtenerGeo($ip) {
    $default = ['pais' => null, 'region' => null];
    if ($ip === '127.0.0.1' || $ip === '::1') return $default;
    
    $url = "http://ip-api.com/json/{$ip}?fields=country,regionName";
    $ctx = stream_context_create(['http' => ['timeout' => 3]]);
    $response = @file_get_contents($url, false, $ctx);
    
    if ($response) {
        $data = json_decode($response, true);
        return [
            'pais' => $data['country'] ?? null,
            'region' => $data['regionName'] ?? null
        ];
    }
    return $default;
}
