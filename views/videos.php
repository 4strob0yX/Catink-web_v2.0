<?php
include (__DIR__ . "/../layout/headerAdmin.php");
$ACL = $_SESSION['ACL']['videos'] ?? [
    'crear' => false,
    'leer' => false,
    'editar' => false,
    'eliminar' => false,
];
if (!$ACL['leer']) {
    header("Location: admin.php");
    exit();
}
?>
<script>
const ACL = <?= json_encode($ACL) ?>;
</script>
<?php
include(__DIR__ . "/helpers/videoEmbed.php");
include(__DIR__ . "/../data/conexion.php");
$stmt = $con->prepare("SELECT * FROM videos order by id_v desc");
$stmt->execute();
$videos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestión de Videos</h1>
    </div>
    <?php if($ACL['crear']): ?>
        <button id="btnCrear" class="btn btn-success">
            <i class="bi bi-plus-lg"></i> Crear Video
        </button>
    <?php endif; ?>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Lista de Videos</h5>
                <?php
                    $chunkedVideos = array_chunk($videos, 3); // divide en grupos de 2
                    foreach($chunkedVideos as $videofila):
                ?>
                    <div class="row mb-3">
                        <?php foreach($videofila as $video): ?>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <?= renderizarVideo($video['url_v']) ?>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Estado:</strong> <?= ($video['activo']==1 ? 'Activo' : 'Inactivo') ?></p>
                                        <?php if($ACL['editar'] || $ACL['eliminar']): ?>
                                            <div class="d-flex gap-2">
                                                <?php if($ACL['editar']): ?>
                                                    <button class="btn btn-secondary btn-editar"
                                                        data-id="<?= $video['id_v'] ?>"
                                                        data-url="<?= htmlspecialchars($video['url_v']) ?>"
                                                        data-activo="<?= $video['activo'] ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if($ACL['eliminar']): ?>
                                                    <button class="btn btn-danger btn-eliminar"
                                                        data-id="<?= $video['id_v'] ?>">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
        </div>
    </div>
</div>
<div id="modal" class="modal">
    <div class="modal-content">
        <span id="modalClose" class="modal-close">&times;</span>
        <h3 id="modalTitle"></h3>
        <p id="modalConfirmText" style="display:none;"></p>
        <form id="modalForm">
            <input type="hidden" name="id_v" id="modalId">
            <div class="mb-3">
                <label>URL del Video</label>
                <input type="text" name="url_v" id="modalUrl" required>
            </div>
            <div class="mb-3">
                <label>Estado</label>
                <select name="activo" id="modalActivo">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
            <button type="submit" id="modalSubmit" class="btn btn-success"></button>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modal');
    const modalTitle = document.getElementById('modalTitle');
    const modalForm = document.getElementById('modalForm');
    const modalId = document.getElementById('modalId');
    const modalUrl = document.getElementById('modalUrl');
    const modalActivo = document.getElementById('modalActivo');
    const modalSubmit = document.getElementById('modalSubmit');
    const modalClose = document.getElementById('modalClose');
    const modalConfirmText = document.getElementById('modalConfirmText');
    const btnCrear = document.getElementById('btnCrear');
    if(btnCrear && ACL.crear){
        btnCrear.addEventListener('click', () => {
            modalTitle.textContent = "Crear Video";
            modalSubmit.textContent = "Crear";
            modalForm.dataset.action = "crear";
            modalId.value = "";
            modalUrl.value = "";
            modalActivo.value = "1";
            modalConfirmText.style.display = "none";
            modal.style.display = "flex";
        });
    }
    if(ACL.editar){
        document.querySelectorAll('.btn-editar').forEach(btn => {
            btn.addEventListener('click', () => {
                modalTitle.textContent = "Editar Video";
                modalSubmit.textContent = "Actualizar";
                modalForm.dataset.action = "editar";
                modalId.value = btn.dataset.id;
                modalUrl.value = btn.dataset.url;
                modalActivo.value = btn.dataset.activo;
                modalConfirmText.style.display = "none";
                modal.style.display = "flex";
            });
        });
    }
    if(ACL.eliminar){
        document.querySelectorAll('.btn-eliminar').forEach(btn => {
            btn.addEventListener('click', () => {
                modalTitle.textContent = "Eliminar Video";
                modalSubmit.textContent = "Eliminar";
                modalForm.dataset.action = "eliminar";
                modalId.value = btn.dataset.id;
                modalConfirmText.style.display = "block";
                modalConfirmText.textContent =
                    "¿Seguro que deseas eliminar este video?";
                modalUrl.required = false;   // 👈 CLAVE
                modalActivo.required = false;
                modalUrl.parentElement.style.display = "none";
                modalActivo.parentElement.style.display = "none";
                modal.style.display = "flex";
            });
        });
    }
    modalClose.addEventListener('click', () => {
        modal.style.display = "none";
    });
    modal.addEventListener('click', (e) => {
        if(e.target === modal){
            modal.style.display = "none";
        }
    });
    modalForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const action = modalForm.dataset.action;
        if(!ACL[action]){
            alert("No tienes permisos");
            return;
        }
        const formData = new FormData(modalForm);
        let url = "";
        if(action === "crear") url = "./../controllers/crearv.php";
        if(action === "editar") url = "./../controllers/editarv.php";
        if(action === "eliminar") url = "./../controllers/eliminarv.php";
        fetch(url, {
            method: "POST",
            body: formData
        })
        .then(async r => {

            const text = await r.text();   // LEER COMO TEXTO
            console.log("RESPUESTA CRUDA:", text);

            try{
                const data = JSON.parse(text);
                if(data.success) location.reload();
                else alert(data.error);
            }catch(e){
                alert("El servidor NO devolvió JSON válido");
                console.error(text);       // AQUÍ VERÁS EL ERROR PHP REAL
            }
        })
        .catch(err => console.error(err));
    });
});
</script>
<?php
include (__DIR__ . "/../layout/footerAdmin.php");
?>