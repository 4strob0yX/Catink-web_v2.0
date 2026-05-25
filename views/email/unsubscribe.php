<?php
include(__DIR__ . "/../../data/conexion.php");
$email = '';
$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $email = trim($_GET['email'] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Correo no válido.Usa una dirección válida.';
        $status = 'error';
    } else {
        $stmt = $con->prepare('DELETE FROM suscripciones WHERE correo = ?');
        $stmt->bind_param('s', $email);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $message = 'Tu suscripción ha sido cancelada y eliminada.';
                $status = 'success';
            } else {
                $message = 'No existe una suscripción registrada con ese correo.';
                $status = 'warning';
            }
        } else {
            $message = 'Ocurrió un error al procesar tu baja. Intenta de nuevo.';
            $status = 'error';
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cancelar suscripción</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f5f5f5; color:#333; margin:0; padding:16px; }
    .container { max-width:520px; margin:32px auto; background:#fff; border-radius:10px; padding:20px; box-shadow:0 0 14px rgba(0,0,0,0.1); }
    .brand { text-align:center; margin-bottom:14px; }
    .brand h1 { font-size:20px; margin:0; color:#EF3363; }
    .info { margin-bottom:16px; }
    .alert { border-radius:6px; padding:12px; margin-bottom:16px; }
    .success { background:#d4edda; color:#155724; }
    .warning { background:#fff3cd; color:#856404; }
    .error { background:#f8d7da; color:#721c24; }
    .form-group { margin-bottom:12px; }
    label { display:block; margin-bottom:6px; font-weight:600; }
    input[type="email"] { width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; }
    input[type="submit"] { width:100%; background:#EF3363; color:#fff; border:none; padding:12px; border-radius:6px; cursor:pointer; }
    input[type="submit"]:hover { background:#d12655; }
    .links { margin-top:20px; font-size:14px; text-align:center; }
    .links a { color:#EF3363; text-decoration:none; }
    .links a:hover { text-decoration:underline; }
  </style>
</head>
<body>
  <div class="container">
    <div class="brand">
      <h1>Cancelar suscripción</h1>
    </div>
    <div class="info">
      <p>Si deseas dejar de recibir correos, por favor confirma tu dirección y da clic en "Cancelar suscripción".</p>
    </div>

    <?php if ($message): ?>
      <div class="alert <?php echo $status; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="post" action="">
      <div class="form-group">
        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">
      </div>
      <input type="submit" value="Cancelar suscripción">
    </form>

    <div class="links">
      <p>
        <a href="https://www.catink.com.mx/terminos-condiciones">Términos y condiciones</a> | 
        <a href="https://www.catink.com.mx/privacidad">Política de privacidad</a>
      </p>
      <p>
        <strong>Síguenos:</strong>
        <a href="https://www.facebook.com/TheCatink?locale=es_LA">Facebook</a>,
        <a href="https://x.com/The_Catink/">Twitter/X</a>,
        <a href="https://www.instagram.com/the.catink/">Instagram</a>,
        <a href="https://www.youtube.com/@thecatink">YouTube</a>,
        <a href="https://www.tiktok.com/@thecatink">TikTok</a>
      </p>
    </div>
  </div>
</body>
</html>
