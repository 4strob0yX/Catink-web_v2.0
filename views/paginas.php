<?php
    include("./../layout/headerAdmin.php");
    include("./../data/conexion.php");
    $sql = "SELECT * FROM paginas"; // Asumiendo que 'Nosotros' tiene id=1 y 'Términos y Condiciones' id=2
    $result = $con->query($sql);
?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestión de Páginas</h1>
    </div>
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'actualizado'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Página actualizada correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Sección</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['nombre_pag']) ?></td>
                                <td>
                                    <button 
                                        class="btn btn-secondary btnEditar" 
                                        data-id="<?= $row['id_pag'] ?>" 
                                        data-nombre="<?= $row['nombre_pag'] ?>"
                                        data-contenido="<?= base64_encode($row['contenido_pag']) ?>">
                                        <i class="bi bi-pencil-square"></i>Editar
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div id="modalPagina" class="modal">
    <form id="formPagina" class="modal-content-nativo-special" action="./../controllers/pagina.php" method="POST">
        <div class="form-card">
            <span id="modalClose" class="modal-close">&times;</span>
            <div class="form-group">
                <input type="hidden" name="id" id="pagina_id">
                <label for="nombre">Sección</label>
                <select name="nombre" id="nombre">
                    <option value="nosotros" selected>Nosotros</option>
                    <option value="terminos">Términos y condiciones</option>
                    <option value="privacidad">Aviso de privacidad</option>
                    <option value="cookies">Política de cookies</option>
                </select>
            </div>
            <div class="form-group">
                <label>Contenido</label>
                <!-- TOOLBAR -->
                <div class="editor-toolbar ql-toolbar ql-snow">
                    <!-- Fuente -->
                    <select class="ql-font" title="Fuente">
                        <option value="arial" selected>Arial</option>
                        <option value="times">Times New Roman</option>
                        <option value="roboto">Roboto</option>
                        <option value="courier">Courier</option>
                    </select>
                    <!-- Tamaño -->
                    <select class="ql-size" title="Tamaño">
                        <option value="small">Pequeño</option>
                        <option selected>Normal</option>
                        <option value="large">Grande</option>
                        <option value="huge">Muy grande</option>
                    </select>
                    <!-- Estilos -->
                    <button class="ql-bold" title="Negritas"></button>
                    <button class="ql-italic" title="Cursiva"></button>
                    <button class="ql-underline" title="Subrayado"></button>
                    <button class="ql-strike" title="Tachado"></button>

                    <!-- Color -->
                    <select class="ql-color" title="Color"></select>
                    <select class="ql-background" title="Fondo"></select>

                    <!-- Alineación -->
                    <select class="ql-align" title="Alineación"></select>
                    <!-- Interlineado -->
                    <select class="ql-lineheight" title="Interlineado">
                        <option value="0">0</option>
                        <option value="0.85">0.85</option>
                        <option value="1">1</option>
                        <option value="1.5">1.5</option>
                        <option value="2">2</option>
                        <option value="2.5">2.5</option>
                        <option value="3">3</option>
                    </select>
                    <!-- Listas -->
                    <button class="ql-list" value="ordered" title="Lista ordenada"></button>
                    <button class="ql-list" value="bullet" title="Lista desordenada"></button>

                    <!-- Sangría -->
                    <button class="ql-indent" value="-1" title="Reducir sangría"></button>
                    <button class="ql-indent" value="+1" title="Aumentar sangría"></button>

                    <!-- Limpiar formato -->
                    <button class="ql-clean" title="Limpiar formato"></button>
                </div>
                <div id="editorpag" class="editor-content"></div>
                <!-- AQUI SE GUARDA QUILL -->
                <input type="hidden" name="contenido" id="contenido">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-success">Guardar</button>
            </div>
        </div>
    </form>
</div>
<script>
    var quillpag = new Quill('#editorpag', {
        theme: 'snow',
        placeholder: 'Escribe el contenido aquí...',
        modules: {
            toolbar: {
                container: '.editor-toolbar'
            }
        }
    });
    const modalPagina = document.getElementById("modalPagina");
    document.querySelectorAll(".btnEditar").forEach(btn => {
        btn.addEventListener("click", function(){
            document.getElementById("pagina_id").value =
            this.dataset.id;
            document.getElementById("nombre").value =
            this.dataset.nombre;
            let contenido = atob(this.dataset.contenido);
            modalPagina.style.display = "block";
            setTimeout(() => {
                quillpag.setContents([]);
                quillpag.clipboard.dangerouslyPasteHTML(contenido);
            }, 100);
        });
    });
    document.getElementById("formPagina")
    .addEventListener("submit", function(){
        document.getElementById("contenido").value =
        quillpag.root.innerHTML;
    });
    modalClose.addEventListener('click', () => {
        modalPagina.style.display = "none";
        modalNombre.parentElement.style.display = "block"; // reset
    });
    modal.addEventListener('click', (e) => {
        if(e.target === modal) {
            modalPagina.style.display = "none";
            modalNombre.parentElement.style.display = "block";
        }
    });
</script>
<?php include("./../layout/footerAdmin.php"); ?>