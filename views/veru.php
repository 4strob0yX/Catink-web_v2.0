<?php
include("./../layout/headerAdmin.php");
$ACL = $_SESSION['ACL']['usuarios']??[
    'crear' => 1,
    'leer' => 2,
    'editar' => 4,
    'eliminar' => 8,
];
if (!($ACL['leer'])) {
    header("Location: admin.php");
    exit();
}
include("./../data/conexion.php");
$id = $_GET['id'];
$stmt = $con->prepare("SELECT * FROM usuarios WHERE id_u = $id");
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
function mostrarPermisos($perm) {
    $permisos = [];
    if ($perm & 1) $permisos[] = "Crear";
    if ($perm & 2) $permisos[] = "Ver";
    if ($perm & 4) $permisos[] = "Editar";
    if ($perm & 8) $permisos[] = "Eliminar";
    if ($perm == 0) return "Sin acceso";
    if ($perm == 15) return "Acceso completo";
    return implode(", ", $permisos);
}
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Datos del usuario <?= $usuario['nombre'] ?></h1>
    </div>
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Detalles del usuario</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col">
                    <p class="card-text"><strong>ID:</strong> <?= $usuario['id_u'] ?></p>
                    <p class="card-text"><strong>Nombre:</strong> <?= $usuario['nombre'] ?></p>
                    <p class="card-text"><strong>Email:</strong> <?= $usuario['correo'] ?></p>
                </div>
                <div class="col">
                    <h5 class="card-text"><strong>Permisos:</strong></h5>
                    <p><strong>Categorías:</strong> <?= mostrarPermisos($usuario['perm_categorias']) ?></p>
                    <p><strong>Noticias:</strong> <?= mostrarPermisos($usuario['perm_noticias']) ?></p>
                    <p><strong>Publicidad:</strong> <?= mostrarPermisos($usuario['perm_publicidad']) ?></p>
                    <p><strong>Suscripciones:</strong> <?= mostrarPermisos($usuario['perm_suscripciones']) ?></p>
                    <p><strong>Usuarios:</strong> <?= mostrarPermisos($usuario['perm_usuarios']) ?></p>
                    <p><strong>Correos:</strong> <?= mostrarPermisos($usuario['perm_correos']) ?></p>
                    <p><strong>Videos:</strong> <?= mostrarPermisos($usuario['perm_videos']) ?></p>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <a href="./../views/usuarios.php" class="btn btn-secondary">Volver</a>
            <?php if ($ACL['editar']) : ?>
                <a href="./../views/editaru.php?id=<?= $usuario['id_u'] ?>" class="btn btn-secondary">Editar</a>
            <?php endif; ?>
            <?php if ($ACL['eliminar']) : ?>
                <button class="btn btn-delete-usuario" data-id="<?= $usuario['id_u'] ?>" data-nombre="<?= $usuario['nombre'] ?>" title="Eliminar Usuario">Eliminar</button>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Modal eliminacion usuario -->
<div id="modalOverlayU" class="crop-modal" style="display: none;">
    <div class="crop-modal-content">
        <h3 id="modalTitleU">Confirmar eliminación</h3>
        <p>¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.</p>
        <form id="modalFormU" action="./../controllers/eliminarusuario.php?id=<?= $usuario['id_u'] ?>" method="POST">
            <input type="hidden" name="id" id="modalIdU">
            <div class="crop-actions">
                <button type="button" class="btn btn-secondary btn-cancel">Cancelar</button>
                <button type="submit" class="btn btn-danger">Eliminar</button>
            </div>
        </form>
    </div>
</div>
<?php include("./../layout/footerAdmin.php"); ?>