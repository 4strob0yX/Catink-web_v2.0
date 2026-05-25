<?php
    include("./../layout/headerAdmin.php");
    include("./../data/conexion.php");
    $ACL = $_SESSION['ACL']['correos'] ?? [
        "crear" => false,
        "leer" => false,
        "editar" => false,
        "eliminar" => false
    ];
    if(!$ACL['leer']) {
        header("Location: admin.php");
        exit();
    }
?>
<script>
    const ACL = <?= json_encode($ACL) ?>;
</script>
<?php
    $sql= $con -> prepare("SELECT * FROM correos_publicitarios ORDER BY creado DESC");
    $sql->execute();
    $result = $sql->get_result();
    $correos = $result -> fetch_all(MYSQLI_ASSOC);
?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestión de Corros Publicitarios</h1>
    </div>
    <?php if ($ACL['crear']): ?>
        <div class="col">
            <a href="correo_pub.php" class="btn btn-success"><i class="bi bi-plus-lg"></i>Agregar Correo Publicitario</a>
        </div>
    <?php endif; ?>
    <br>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Titulo</th>
                    <th>Contenido</th>
                    <th>Url</th>
                    <th>Enviado</th>
                    <?php if(!empty($ACL['editar']) || !empty($ACL['eliminar'])): ?>
                        <th>Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($correos as $correo): ?>
                    <tr>
                        <th><?= $correo['titulo'] ?></th>
                        <th><?= $correo['contenido'] ?></th>
                        <th><?= $correo['url_c'] ?></th>
                        <th><?= $correo['envio'] ?></th>
                        <?php if(!empty($ACL['editar']) || !empty($ACL['eliminar'])): ?>
                            <th>
                                <?php if(!empty($ACL['editar'])): ?>
                                    <a href="correo_pub_edit.php?id=<?= $correo['id_correo'] ?>" class="btn btn-secondary">Editar</a>
                                <?php endif;?>
                                <?php if(!empty($ACL['eliminar'])): ?>
                                    <button type="button" class="btn btn-danger btnEliminarCorreo" data-id="<?= $correo['id_correo'] ?>">Eliminar</button>
                                <?php endif;?>
                            </th>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<div id="modalCorreo" class="crop-modal" style="display:none;">
    <div class="crop-modal-content">
        <h3>Confirmar eliminación</h3>
        <p>
            ¿Estás seguro de que deseas eliminar este correo publicitario?
            Esta acción eliminará también su imagen.
        </p>

        <form id="modalForm" action="./../controllers/eliminarCorreoPub.php" method="POST">
            <input type="hidden" name="id" id="modalIdCorreo">

            <div class="crop-actions">
                <button type="button" class="btn btn-secondary" id="btnCancel">Cancelar</button>
                <button type="submit" class="btn btn-danger">Eliminar</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("modalCorreo");
    const modalIdCorreo = document.getElementById("modalIdCorreo");
    const cancelBtn = document.getElementById("btnCancel");
    document.querySelectorAll(".btnEliminarCorreo").forEach(btn => {
        btn.addEventListener("click", () => {
            const id = btn.dataset.id;
            modalIdCorreo.value = id;
            modal.style.display = "flex";
        });
    });
    cancelBtn.addEventListener("click", () => {
        modal.style.display = "none";
    });
    window.addEventListener("click", (e) => {
        if(e.target === modal){
            modal.style.display = "none";
        }
    });

});
</script>
<?php include("./../layout/footerAdmin.php") ?>