<?php
include(__DIR__ . "/../layout/headerAdmin.php");
include(__DIR__."/../controllers/aclcontroller.php");
// ACL global para noticias
$ACL = $_SESSION['ACL']['noticias'] ?? [
    "crear" => false,
    "leer" => false,
    "editar" => false,
    "eliminar" => false
];
proteger('noticias','crear');
// Página de creación de noticias
include(__DIR__ . "/../data/conexion.php");
if (empty($ACL['crear'])) {
    header("Location: admin.php");
    exit();
}
?>
<script>
    const ACL = <?= json_encode($ACL) ?>;
</script>
<?php
// Obtener categorías desde la base de datos
$categoriasResult = $con->query("SELECT id_c, nombre FROM categorias ORDER BY nombre ASC");
$categorias = [];
while($row = $categoriasResult->fetch_assoc()){
    $categorias[] = $row;
}
?>
<div class="admin-container">
    <h1>Alta de noticia | CatInk News</h1>
    <form id="formPublicacion" action="./../../controllers/noticiascontroller.php" method="POST" enctype="multipart/form-data">
        <div class="form-card card">
            <!-- Autor oculto -->
            <input type="hidden" name="autor" value="<?= $fila['id_u'] ?>">
            <!-- TÍTULO -->
            <div class="form-group">
                <label for="titulo">Título</label>
                <span>Máximo 50 caracteres</span>
                <input type="text" id="titulo" name="titulo" maxlength="50" required>
            </div>
            <!-- DESCRIPCIÓN -->
            <div class="form-group">
                <label for="descripcion">Descripción corta</label>
                <span>Máximo 150 caracteres</span>
                <textarea 
                    id="descripcion" 
                    name="descripcion" 
                    maxlength="150" 
                    rows="3"
                    placeholder="Resumen breve de la noticia"
                    required></textarea>
            </div>
            <!-- CATEGORÍAS dinámicas -->
            <div class="form-group">
                <label for="categorias">Categorías</label>
                <div class="checkbox-group">
                    <?php foreach($categorias as $cat): ?>
                        <label class="check">
                            <input type="checkbox" name="categoria[]" value="<?= $cat['id_c'] ?>">
                            <?= htmlspecialchars($cat['nombre']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- IMAGEN PRINCIPAL -->
            <div class="form-group">
                <label>Imagen principal</label>
                <span>Sube 3 imágenes: Original, Banner (21:6) y Miniatura (16:9)</span>
                
                <!-- IMAGEN 1: Original -->
                <div class="crop-image-section">
                    <h5>1. Imagen Original</h5>
                    <input type="file" id="imageInputCrop1" class="crop-input" accept="image/*" data-crop="1">
                    <div class="cropper-container">
                        <img id="cropperImage1" class="cropper-img">
                    </div>
                    <div class="crop-actions">
                        <button type="button" class="btn btn-outline-secondary crop-btn-confirm" data-crop="1"><i class="bi bi-check"></i> Confirmar</button>
                        <button type="button" class="btn btn-outline-secondary crop-btn-reset" data-crop="1"><i class="bi bi-recycle"></i> Reset</button>
                    </div>
                    <div class="crop-preview" id="preview1"></div>
                </div>
                
                <!-- IMAGEN 2: Banner -->
                <div class="crop-image-section">
                    <h5>2. Imagen Banner</h5>
                    <input type="file" id="imageInputCrop2" class="crop-input" accept="image/*" data-crop="2">
                    <div class="cropper-container">
                        <img id="cropperImage2" class="cropper-img">
                    </div>
                    <div class="crop-actions">
                        <button type="button" class="btn btn-outline-secondary crop-btn-confirm" data-crop="2"><i class="bi bi-check"></i> Confirmar</button>
                        <button type="button" class="btn btn-outline-secondary crop-btn-reset" data-crop="2"><i class="bi bi-recycle"></i> Reset</button>
                    </div>
                    <div class="crop-preview" id="preview2"></div>
                </div>
                
                <!-- IMAGEN 3: Miniatura -->
                <div class="crop-image-section">
                    <h5>3. Imagen Miniatura</h5>
                    <input type="file" id="imageInputCrop3" class="crop-input" accept="image/*" data-crop="3">
                    <div class="cropper-container">
                        <img id="cropperImage3" class="cropper-img">
                    </div>
                    <div class="crop-actions">
                        <button type="button" class="btn btn-outline-secondary crop-btn-confirm" data-crop="3"><i class="bi bi-check"></i> Confirmar</button>
                        <button type="button" class="btn btn-outline-secondary crop-btn-reset" data-crop="3"><i class="bi bi-recycle"></i> Reset</button>
                    </div>
                    <div class="crop-preview" id="preview3"></div>
                </div>
                
                <!-- Inputs ocultos para los datos de las imágenes -->
                <input type="hidden" name="crop1" id="crop1">
                <input type="hidden" name="crop2" id="crop2">
                <input type="hidden" name="crop3" id="crop3">
            </div>
            <!-- CONTENIDO -->
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

                    <!-- Enlaces / multimedia -->
                    <button class="ql-link" title="Añadir link"></button>
                    <button class="ql-image" title="Insertar imagen"></button>
                    <button class="ql-video" title="Insertar video"></button>

                    <!-- Limpiar formato -->
                    <button class="ql-clean" title="Limpiar formato"></button>
                    <!-- Embebido -->
                    <button class="ql-embed" title="Embebido"><i class="bi bi-boxes"></i></button>
                </div>

                <!-- EDITOR -->
                <div id="editor" class="editor-content"></div>

                <!-- INPUT OCULTO PARA IMÁGENES -->
                <input type="file" id="imageInputEditor" accept="image/*" hidden>


                <div id="editorContent"></div>
                <!-- Campo oculto para PHP -->
                <input type="hidden" name="contenido" id="contenido">
            </div>
            <!-- PROGRAMACIÓN -->
            <div class="form-group">
                <label>Programar publicación</label>
                <input type="datetime-local" name="fecha_publicacion" class="btn-calendar">
            </div>
            <!-- ACCIONES -->
            <div class="form-actions">
                <?php if (!empty($ACL['crear'])): ?>
                    <button type="submit" class="btn btn-success" name="guardarNoticia">
                        Guardar noticia
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>
<!-- Modal de Confirmación Hora -->
<div id="timeModalOverlay" class="crop-modal" style="display: none;">
    <div class="crop-modal-content">
        <h3 id="modalTitle">Hora no válida</h3>
        <p>
            La fecha y hora seleccionadas es menor a la actual.
            <br><br>
            ¿Qué deseas hacer?
        </p>
        <div class="modal-actions">
            <button class="btn-success" id="autoAdjustBtn" type="button">
                Ajustar automáticamente y guardar
            </button>
            <button class="btn-secondary" id="manualAdjustBtn" type="button">
                Volver a ajustar la hora
            </button>
        </div>
    </div>
</div>
<?php
include(__DIR__ . "/../layout/footerAdmin.php");
?>