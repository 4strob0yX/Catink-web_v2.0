<?php
include('./../layout/headerAdmin.php');
include('./../controllers/aclcontroller.php');
$ACl = $_SESSION['ACL']['publicidad']??[
    "crear" => false,
    "leer" => false,
    "editar" => false,
    "eliminar" => false
];
if(!$ACl['crear']) {
    header("Location: admin.php");
    exit();
}
proteger('publicidad','crear');
?>
<div class="container-fluid">
    <h1>Alta de correo publicitario</h1>
    <form action="./../controllers/crearCorreoPub.php" method="POST" enctype="multipart/form-data">
        <div class="form-card card">
            <div class="form-group">
                <label for="titulo">Titulo:</label>
                <input type="text" id="titulo" name="titulo" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="contenido">Contenido:</label>
                <textarea id="contenido" name="contenido" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <label for="imagen">Imagen:</label>
                <input type="file" id="imagenCorreo" name="imagenCorreo" class="form-control" required>
                <div id="preview"></div>
            </div>
            <div class="form-group">
                <label for="url">Url:</label>
                <input type="url" id="url" name="url" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="programacion">Hora de envio:</label>
                <input type="datetime-Local" name="envio" class="btn-calendar">
            </div>
            <div class="form-actions">
                <?php if($ACl['crear']): ?>
                    <button type="submit" class="btn btn-success">Guardar</button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>
<?php include('./../layout/footerAdmin.php'); ?>
