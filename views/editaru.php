<?php
include("./../layout/headerAdmin.php");
include("./../controllers/aclcontroller.php");
$ACL = $_SESSION['ACL']['usuarios']??[
    'crear' => false,
    'leer' => false,
    'editar' => false,
    'eliminar' => false,
];
if (!$ACL['editar']) {
    header("Location: admin.php");
    exit();
}
proteger('usuarios', 'editar');
include("./../data/conexion.php");
$id = $_GET['id'];
$stmt = $con->prepare("SELECT * FROM usuarios WHERE id_u=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Editar Usuario</h1>
    </div>
    <a href="./../views/usuarios.php" class="btn btn-secondary"><i class="bi bi-arrow-return-left"></i> Regresar</a>
    <form id="editUserForm" action="./../controllers/editarusuario.php?id=<?= $id ?>" method="POST">
        <div class="form-card card">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" value="<?= $user['nombre'] ?>" required>
            </div>
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="usuario" value="<?= $user['usuario'] ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= $user['correo'] ?>" required>
            </div>
            <!-- CONTRASEÑA OPCIONAL -->
            <div class="form-group">
                <label>Nueva Contraseña (opcional)</label>
                <input type="text" name="password">
                <label>Confirmar Contraseña</label>
                <input type="text" name="confirm_password">
            </div>
            <div class="form-group">
                <?php
                    function check($perm,$bit){ return ($perm & $bit) ? "checked" : ""; }
                ?>
                <div class="row-permisos">
                    <div class="col-permiso">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Permisos Publicidad</h5>
                            </div>
                            <div class="card-body form-group">
                                <label><input type="checkbox" name="publicidad[]" value="1" <?= check($user['perm_publicidad'],1) ?>> Crear</label>
                                <span>Capacidad de agregar nueva publicidad</span>
                                <label><input type="checkbox" name="publicidad[]" value="2" <?= check($user['perm_publicidad'],2) ?>> Ver</label>
                                <span>Capacidad de acceder y ver en la seccion de publicidad</span>
                                <label><input type="checkbox" name="publicidad[]" value="4" <?= check($user['perm_publicidad'],4) ?>> Editar</label>
                                <span>Capacidad de modificar la publicidad existente</span>
                                <label><input type="checkbox" name="publicidad[]" value="8" <?= check($user['perm_publicidad'],8) ?>> Eliminar</label>
                                <span>Capacidad de eliminar alguna publicidad</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-permiso">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Permisos Noticias</h5>
                            </div>
                            <div class="card-body form-group">
                                <label><input type="checkbox" name="noticias[]" value="1" <?= check($user['perm_noticias'],1) ?>> Crear</label>
                                <span>Capacidad de crear nuevas publicaciones</span>
                                <label><input type="checkbox" name="noticias[]" value="2" <?= check($user['perm_noticias'],2) ?>> Ver</label>
                                <span>Capacidad de acceder y ver en la seccion de contenidos</span>
                                <label><input type="checkbox" name="noticias[]" value="4" <?= check($user['perm_noticias'],4) ?>> Editar</label>
                                <span>Capacidad de modificar las publicaciones</span>
                                <label><input type="checkbox" name="noticias[]" value="8" <?= check($user['perm_noticias'],8) ?>> Eliminar</label>
                                <span>Capacidad de eliminar las publicaciones</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-permiso">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Permisos Categorías</h5>
                            </div>
                            <div class="card-body form-group">
                                <label><input type="checkbox" name="categorias[]" value="1" <?= check($user['perm_categorias'],1) ?>> Crear</label>
                                <span>Capacidad de agregar una nueva categoría</span>
                                <label><input type="checkbox" name="categorias[]" value="2" <?= check($user['perm_categorias'],2) ?>> Ver</label>
                                <span>Capacidad de acceder y ver en la seccion de categorías</span> 
                                <label><input type="checkbox" name="categorias[]" value="4" <?= check($user['perm_categorias'],4) ?>> Editar</label>
                                <span>Capacidad de editar el nombre de la categoría</span>
                                <label><input type="checkbox" name="categorias[]" value="8" <?= check($user['perm_categorias'],8) ?>> Eliminar</label>
                                <span>Capacidad de eliminar categorias</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-permiso">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Permisos Correo</h5>
                            </div>
                            <div class="card-body form-group">
                                <label><input type="checkbox" name="correos[]" value="1" <?= check($user['perm_correos'],1) ?>> Crear</label>
                                <span>Capacidad de generar nuevos correos</span>
                                <label><input type="checkbox" name="correos[]" value="2" <?= check($user['perm_correos'],2) ?>> Ver</label>
                                <span>Capacidad de acceder y ver en la seccion de correos</span>
                                <label><input type="checkbox" name="correos[]" value="4" <?= check($user['perm_correos'],4) ?>> Editar</label>
                                <span>Capacidad de editar los correos (reenvio)</span>
                                <label><input type="checkbox" name="correos[]" value="8" <?= check($user['perm_correos'],8) ?>> Eliminar</label>
                                <span>Capacidad de eliminar los correos</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-permiso">  
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Permisos Suscripciones</h5>
                            </div>
                            <div class="card-body form-group">
                                <label><input type="checkbox" name="suscripciones[]" value="1" <?= check($user['perm_suscripciones'],1) ?>> Crear</label>
                                <span>La suscripcion la hacen los usuarios</span>
                                <label><input type="checkbox" name="suscripciones[]" value="2" <?= check($user['perm_suscripciones'],2) ?>> Ver</label>
                                <span>Capacidad de acceder y ver en la seccion de suscripciones</span>
                                <label><input type="checkbox" name="suscripciones[]" value="4" <?= check($user['perm_suscripciones'],4) ?>> Editar</label>
                                <span>Los usuarios activan o desactivan su suscripcion</span>
                                <label><input type="checkbox" name="suscripciones[]" value="8" <?= check($user['perm_suscripciones'],8) ?>> Eliminar</label>
                                <span>Capacidad de eliminar las suscripciones</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-permiso">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Permisos Usuarios</h5>
                            </div>
                            <div class="card-body form-group">
                                <label><input type="checkbox" name="usuarios[]" value="1" <?= check($user['perm_usuarios'],1) ?>> Crear</label>
                                <span>Capacidad de añadir usuarios al sistema</span>
                                <label><input type="checkbox" name="usuarios[]" value="2" <?= check($user['perm_usuarios'],2) ?>> Ver</label>
                                <span>Capacidad de acceder y ver en la seccion de Usuarios</span>
                                <label><input type="checkbox" name="usuarios[]" value="4" <?= check($user['perm_usuarios'],4) ?>> Editar</label>
                                <span>Capacidad de ediar los usuarios (permisos)</span>
                                <label><input type="checkbox" name="usuarios[]" value="8" <?= check($user['perm_usuarios'],8) ?>> Eliminar</label>
                                <span>Capacidad de eliminar usuarios del sistema</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-permiso">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Permisos Videos</h5>
                            </div>
                            <div class="card-body form-group">
                                <label><input type="checkbox" name="videos[]" value="1" <?= check($user['perm_videos'],1) ?>> Crear</label>
                                <span>Capacidad de agregar nuevos videos</span>
                                <label><input type="checkbox" name="videos[]" value="2" <?= check($user['perm_videos'],2) ?>> Ver</label>
                                <span>Capacidad de acceder y ver en la seccion de Videos</span>
                                <label><input type="checkbox" name="videos[]" value="4" <?= check($user['perm_videos'],4) ?>> Editar</label>
                                <span>Capacidad de editar los videos (reemplazar)</span>
                                <label><input type="checkbox" name="videos[]" value="8" <?= check($user['perm_videos'],8) ?>> Eliminar</label>
                                <span>Capacidad de eliminar videos</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <?php if($ACL['editar']): ?>
                    <button type="submit" class="btn btn-success">Actualizar Usuario</button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>
<?php include("./../layout/footerAdmin.php"); ?>
