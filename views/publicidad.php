<?php
    include("./../layout/headerAdmin.php");
    include("./../data/conexion.php");
    $ACL = $_SESSION['ACL']['publicidad'] ?? [
        "crear" => false,
        "leer" => false,
        "editar" => false,
        "eliminar" => false
    ];
?>
<script>
    const ACL = <?= json_encode($ACL) ?>;
</script>
<?php
    $sql = "SELECT * FROM publicidad";
    $result = $con->prepare($sql);
    $result->execute();
    $publicidades = $result->get_result();
?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestión de Publicidad</h1>
    </div>
    <?php if($ACL['crear']): ?>
        <div class="col">
            <a href="crearp.php" class="btn btn-success"><i class="bi bi-plus-lg"></i>Agregar Publicidad</a>
        </div>
    <?php endif; ?>
    <div class="row">
        <?php
            foreach($publicidades as $pub):
        ?>
            <div class="col">
                <div class="card publicidad-card">
                    <img src="./../<?= $pub['imagen'] ?>" alt="Publicidad" class="card-img-top">
                    <div class="card-body">
                        <h5 class="card-title"><?= $pub['titulo'] ?></h5>
                        <p class="news-tag">Tipo: <?= $pub['tipo'] == 1 ? 'Banner Largo' : 'Banner Cuadrado' ?></p>
                        <p class="card-text">Inicio: <?= date("M d, Y", strtotime($pub['fecha_inicio']))?> - Fin: <?= date("M d, Y", strtotime($pub['fecha_fin'])) ?></p>
                        <p class="card-text">Url: <?= $pub['url'] ?></p>
                    </div>
                    <?php if(!empty($ACL['editar']) || !empty($ACL['eliminar'])): ?>
                        <div class="card-footer">
                            <?php if($ACL['editar']): ?>
                                <a href="editarp.php?id=<?= $pub['id_pub'] ?>" class="btn btn-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
                            <?php endif; ?>
                            <?php if($ACL['leer']): ?>
                                <a href="verp.php?id=<?= $pub['id_pub'] ?>" class="btn btn-secondary" title="Ver Estadísticas"><i class="bi bi-bar-chart"></i></a> 
                            <?php endif; ?>
                            <?php if($ACL['eliminar']): ?>
                                <button type="button" class="btn btn-delete-publicidad" data-id="<?= $pub['id_pub'] ?>" data-titulo="<?= htmlspecialchars($pub['titulo']) ?>" title="Eliminar"><i class="bi bi-trash"></i></button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php
            endforeach;
        ?>
    </div>
</div>
<!-- Modal de Confirmación para Eliminar -->
<div id="modalOverlayP" class="crop-modal" style="display: none;">
    <div class="card">
        <div class="crop-modal-content">
            <h3 id="modalTitleP">Confirmar eliminación</h3>
            <p>¿Estás seguro de que deseas eliminar esta publicacion? Esta acción no se puede deshacer.</p>
            <form id="modalFormP" action="../controllers/eliminarp.php" method="POST">
                <input type="hidden" name="id" id="modalIdP">
                <div class="crop-actions">
                    <button type="button" class="btn btn-secondary btn-cancel">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
    include("./../layout/footerAdmin.php");
?>