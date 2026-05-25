<?php
require_once(__DIR__ . "/../views/helpers/urlhelper.php");
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
if (isset($_SESSION['usuario'])) {
    header('Location: ' . basePath() . '/views/admin.php');
    exit();
}
include("./../layout/header.php");
?>
<div class="container-fluid">
    <center>
        <div class="card">
            <div class="row">
                <div class="col-md-4">
                    <!-- Logo dentro de la tarjeta -->
                    <img src="./../img/logo_alt_alt.jpg" class="card-img-left" alt="logo">
                </div>
                <div class="col-md-8">
                    <div class="card-header text-center">
                        <h5 class="card-title">Login</h5>
                    </div>
                    <!-- Formulario de inicio de sesión -->
                    <div class="card-body">
                        <form action="./../controllers/logincontroller.php" method="POST">
                            <div class="form-card">
                                <div class="form-group">
                                    <label for="usuario" class="form-label">Usuario</label>
                                    <input type="text" class="form-control" id="usuario" name="usuario" required>
                                </div>
                                <div class="form-group">
                                    <label for="pass" class="form-label">Contraseña</label>
                                    <input type="password" class="form-control" id="pass" name="pass" required>
                                </div>
                                <div class="form-actions">
                                    <button class="btn btn-success" type="submit">Iniciar Sesión</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </center>
</div>
<?php
include("./../layout/footer.php");
?>