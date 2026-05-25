<?php
/**
 * TEST DEL FILTRO DE MODERACIÓN
 * Ejecutar: php test_filtro.php
 * O visitar: http://localhost:8080/test_filtro.php
 */
include(__DIR__ . "/data/conexion.php");

// Copiar funciones del controller
function normalizarTexto($texto) {
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
    $texto = preg_replace('/(?<=\w)[.\-_\s]+(?=\w)/u', '', $texto);
    $texto = strtr($texto, $mapa);
    $texto = preg_replace('/(.)\1{2,}/u', '$1', $texto);
    return $texto;
}

function construirPatronFlexible($palabra) {
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
    $separador = '[.\-_\s*]*';
    return '/\b' . implode($separador, $partes) . '\b/iu';
}

function filtrarContenido($con, $contenido) {
    $stmt = $con->prepare("SELECT palabra, severidad, accion FROM palabras_prohibidas WHERE activo = 1");
    $stmt->execute();
    $palabras = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $detectadas = [];
    $acciones = [];
    $contenidoFiltrado = $contenido;
    $contenidoNormalizado = normalizarTexto($contenido);
    
    foreach ($palabras as $p) {
        $palabraNorm = normalizarTexto($p['palabra']);
        $patron = '/\b' . preg_quote($palabraNorm, '/') . '\b/iu';
        if (preg_match($patron, $contenidoNormalizado)) {
            $detectadas[] = $p['palabra'];
            $acciones[] = $p['accion'];
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

// ============================
// CASOS DE PRUEBA
// ============================
$tests = [
    "Hola, buen artículo",
    "Esto es una mierda",
    "Eres un p3nd3j0",
    "Vete a la m.i.e.r.d.a",
    "Que put@ eres",
    "HIJO DE PUTA",
    "Eres un 1d10t4",
    "No seas cul3r0",
    "Me caga este pinch3 artículo",
    "Puuuutaaa maaadre",
    "Buen trabajo, felicidades",
    "Eres un est-u-p-i-d-o",
    "idi0ta",
    "v3rg@",
];

echo "<h1>Test del Filtro de Moderación</h1>";
echo "<table border='1' cellpadding='8' cellspacing='0' style='font-family:monospace; border-collapse:collapse;'>";
echo "<tr style='background:#333;color:#fff;'><th>#</th><th>Input Original</th><th>Normalizado</th><th>Resultado</th><th>Palabras Detectadas</th><th>Acción</th></tr>";

foreach ($tests as $i => $test) {
    $resultado = filtrarContenido($con, $test);
    $norm = normalizarTexto($test);
    $detectadas = !empty($resultado['detectadas']) ? implode(', ', $resultado['detectadas']) : '-';
    $accion = !empty($resultado['acciones']) ? (in_array('rechazar', $resultado['acciones']) ? '🚫 RECHAZAR' : (in_array('censurar', $resultado['acciones']) ? '✂️ CENSURAR' : '👁️ REVISAR')) : '✅ LIMPIO';
    $bgColor = empty($resultado['detectadas']) ? '#e8f5e9' : '#ffebee';
    
    echo "<tr style='background:{$bgColor};'>";
    echo "<td>" . ($i+1) . "</td>";
    echo "<td>{$test}</td>";
    echo "<td>{$norm}</td>";
    echo "<td>{$resultado['contenido']}</td>";
    echo "<td>{$detectadas}</td>";
    echo "<td>{$accion}</td>";
    echo "</tr>";
}

echo "</table>";
echo "<br><p><strong>Total palabras en diccionario:</strong> ";
$count = $con->query("SELECT COUNT(*) AS t FROM palabras_prohibidas WHERE activo = 1")->fetch_assoc();
echo $count['t'] . "</p>";
