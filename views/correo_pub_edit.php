<?php
include('./../layout/headerAdmin.php');
include('./../controllers/aclcontroller.php');
$ACl = $_SESSION['ACL']['publicidad']??[
    "crear" => false,
    "leer" => false,
    "editar" => false,
    "eliminar" => false
];
if(!$ACl['editar']) {
    header("Location: admin.php");
    exit();
}
proteger('publicidad','crear');
$id = $_GET['id'] ?? null;
if($id){
    include('./../data/conexion.php');
    $sql = $con -> prepare("SELECT * FROM correos_publicitarios WHERE id_correo = ?");
    $sql -> bind_param("i",$id);
    $sql -> execute();
    $result = $sql -> get_result();
    $correo = $result -> fetch_assoc();
}
?>
<div class="container-fluid">
    <h1>Edición de correo publicitario</h1>
    <div class="mt-3">
        <a href="./../views/correos.php" class="btn btn-secondary"><i class="bi bi-arrow-return-left"></i> Volver</a>
    </div>
    <form action="./../controllers/editCorreoPub.php" method="POST" enctype="multipart/form-data">
        <div class="form-card card">
            <input type="hidden" value="<?= $id ?>" name="id">
            <div class="form-group">
                <label for="titulo">Titulo:</label>
                <input type="text" id="titulo" name="titulo" class="form-control" required value="<?= $correo['titulo'] ?>">
            </div>
            <div class="form-group">
                <label for="contenido">Contenido:</label>
                <textarea id="contenido" name="contenido" class="form-control" required><?= $correo['contenido'] ?></textarea>
            </div>
            <div class="form-group">
                <label for="imagen">Imagen:</label>
                <div>
                    <img src="./../img/correo/<?= $correo['imagen'] ?>" alt="Actual" style="width:auto; max-height:120px; object-fit:cover;">
                </div>
                <span>Imagen actual</span>
                <input type="file" id="imagenCorreo" name="imagenCorreo" class="form-control" value="">
                <div id="preview"></div>
            </div>
            <div class="form-group">
                <label for="url">Url:</label>
                <input type="url" id="url" name="url" class="form-control" required value="<?= $correo['url_c'] ?>">
            </div>
            <div class="form-group">
                <label for="programacion">Hora de reenvio:</label>
                <input type="datetime-local" name="envio" class="btn-calendar" value="<?= $correo['envio'] ?>">
            </div>
            <div class="form-actions">
                <?php if($ACl['editar']): ?>
                    <button type="submit" class="btn btn-success">Guardar</button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>
<?php include('./../layout/footerAdmin.php'); ?>
