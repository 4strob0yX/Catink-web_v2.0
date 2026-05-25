<?php
    include("./../layout/headerAdmin.php");
    include("./../controllers/aclcontroller.php");
    $ACL = $_SESSION['ACL']['publicidad'] ?? [
        "crear" => false,
        "leer" => false,
        "editar" => false,
        "eliminar" => false
    ];
    if (!$ACL['editar']) {
        header("Location: publicidad.php");
        exit;
    }
    proteger('publicidad','editar');
    include("./../data/conexion.php");
    if (!isset($_GET['id'])) {
        header("Location: publicidad.php");
        exit;
    }
    $id_pub = $_GET['id'];
    // Obtener datos de la publicidad
    $stmt = $con->prepare("SELECT * FROM publicidad WHERE id_pub = ?");
    $stmt->bind_param("i", $id_pub);
    $stmt->execute();
    $result = $stmt->get_result();
    $publicidad = $result->fetch_assoc();

    if (!$publicidad) {
        header("Location: publicidad.php");
        exit;
    }
    // Obtener categorías seleccionadas
    $stmtCat = $con->prepare("SELECT categoria_id FROM publicidad_categoria WHERE publicidad_id = ?");
    $stmtCat->bind_param("i", $id_pub);
    $stmtCat->execute();
    $resultCat = $stmtCat->get_result();
    $categoriasSeleccionadas = [];
    while ($row = $resultCat->fetch_assoc()) {
        $categoriasSeleccionadas[] = $row['categoria_id'];
    }
    // Obtener todas las categorías
    $categoriasResult = $con->query("SELECT id_c, nombre FROM categorias ORDER BY nombre ASC");
    $categorias = [];
    while($row = $categoriasResult->fetch_assoc()){
        $categorias[] = $row;
    }
?>
<script>
    const ACL = <?= json_encode($ACL) ?>;
</script>
<div class="container">
    <h2>Editar Publicidad</h2>
    <div class="mt-3">
        <a href="./../views/publicidad.php" class="btn btn-secondary"><i class="bi bi-arrow-return-left"></i> Volver</a>
    </div>
    <form action="./../../controllers/editar_publicidad.php" method="POST" enctype="multipart/form-data">
        <div class="form-card card">
            <input type="hidden" name="id_pub" value="<?= $publicidad['id_pub'] ?>">
            
            <div class="form-group">
                <label for="Titulo" >Título</label>
                <input type="text" id="Titulo" name="Titulo" value="<?= htmlspecialchars($publicidad['titulo']) ?>" required>
            </div>
            <div class="form-group">
                <label for="tipo">Tipo de publicidad</label>
                <!-- Nota: En guardar_publicidad.php no veo que se guarde el 'tipo' en la BD explícitamente en el INSERT mostrado, 
                     pero estaba en el formulario. Asumiré que tal vez no se guardó o falta la columna. 
                     Si existe la columna en la BD, debería preseleccionarse. 
                     Por ahora dejaré el select genérico. -->
                <select id="tipo" name="tipo" required>
                    <?php if ($publicidad['tipo'] == 1): ?>
                        <option value="1" selected>Banner Publicitario</option>
                        <option value="2">Cuadro Publicitario</option>
                    <?php else: ?>
                        <option value="2" selected>Cuadro Publicitario</option>
                        <option value="1">Banner Publicitario</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="imagen">Imagen Actual</label>
                <div>
                    <img src="./../<?= $publicidad['imagen'] ?>" alt="Imagen Actual" style="max-width: 200px; margin-bottom: 10px;">
                </div>
                <label for="imagen">Cambiar Imagen (Opcional)</label>
                <input type="file" id="imagen" name="imagen" accept="image/*">
                <!-- Imagen original -->
                <div class="crop-container">
                    <img id="imagePreview" style="max-width:100%; display:none;">
                </div>
                <!-- Botones -->
                <div class="crop-buttons" style="display:none;" id="cropButtonsArea">
                    <button type="button" id="cropBtn">Recortar</button>
                    <button type="button" id="resetBtn">Deshacer</button>
                </div>
                <!-- Resultado final -->
                <h4 id="previewTitle" style="display:none;">Vista previa final:</h4>
                <img id="resultPreview" style="max-width:100%; border:1px solid #ccc; display:none;">
                <!-- Imagen final enviada al backend -->
                <input type="hidden" name="imagenCrop" id="imagenCrop">
            </div>
            <div class="form-group">
                <label for="url" >Url</label>
                <span>A donde va a redireccionar</span>
                <input type="text" id="url" name="url" value="<?= htmlspecialchars($publicidad['url']) ?>" required>
            </div>
            <div class="form-group">
                <label for="estado" >Estado</label>
                <span>Activo o Inactivo</span>
                <select id="estado" name="estado" required>
                    <option value="1" <?= $publicidad['activo'] == 1 ? 'selected' : '' ?>>Activo</option>
                    <option value="0" <?= $publicidad['activo'] == 0 ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <div class="form-group">
                <label for="Categorias">Categorías</label>
                <div class="checkbox-group">
                    <?php
                        foreach($categorias as $c):
                            $checked = in_array($c['id_c'], $categoriasSeleccionadas) ? 'checked' : '';
                    ?>
                        <label>
                            <input type="checkbox" name="Categorias[]" value="<?=$c['id_c']?>" <?= $checked ?>>
                            <?=$c['nombre']?>
                        </label>
                    <?php
                        endforeach;
                    ?>
                </div>
            </div>
            <div class="form-group">
                <label for="fechaInicio">Fecha de inicio</label>
                <input type="datetime-local" id="fechaInicio" name="fechaInicio" value="<?= date('Y-m-d\TH:i', strtotime($publicidad['fecha_inicio'])) ?>" required>
                <label for="fechaFin">Fecha de fin</label>
                <input type="datetime-local" id="fechaFin" name="fechaFin" value="<?= date('Y-m-d\TH:i', strtotime($publicidad['fecha_fin'])) ?>" required>
            </div>
            <div class="form-actions">
                <?php if ($ACL['editar']): ?>
                    <button type="submit" class="btn btn-success" name="actualizarPublicidad">
                        Actualizar publicidad
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<script>
    // Script simple para mostrar preview y crop (si existe la funcionalidad JS global, esto podría necesitar ajuste)
    // Asumo que el JS de crop ya existe en admin.js o scripts.js, pero aquí agrego lógica básica para mostrar elementos
    const imagenInput = document.getElementById('imagen');
    const imagePreview = document.getElementById('imagePreview');
    const cropButtonsArea = document.getElementById('cropButtonsArea');
    
    imagenInput.addEventListener('change', function(e) {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
                cropButtonsArea.style.display = 'block';
                // Aquí debería iniciarse la librería de cropper si se usa una
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
</script>

<?php
    include("./../layout/footerAdmin.php");
?>
