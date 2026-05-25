<?php
    include("./../layout/headerAdmin.php");
    include("./../controllers/aclcontroller.php");
    proteger('publicidad','crear');
    include("./../data/conexion.php");
    $categoriasResult = $con->query("SELECT id_c, nombre FROM categorias ORDER BY nombre ASC");
    $categorias = [];
    while($row = $categoriasResult->fetch_assoc()){
        $categorias[] = $row;
    }
?>
<div class="container">
    <h2>Crear Publicidad</h2>
    <form action="./../../controllers/guardar_publicidad.php" method="POST" enctype="multipart/form-data">
        <div class="form-card card">
            <input type="hidden" name="autor" value="<?= $fila['id_u'] ?>">
            <div class="form-group">
                <label for="Titulo" >Título</label>
                <input type="text" id="Titulo" name="Titulo" required>
            </div>
            <div class="form-group">
                <label for="tipo">Tipo de publicadad</label>
                <select id="tipo" name="tipo" required>
                    <option value="1">Banner Publicitario</option>
                    <option value="2">Cuadro Publicitario</option>
                </select>
            </div>
            <div class="form-group">
                <label for="imagen">Imagen</label>
                <input type="file" id="imagen" name="imagen" accept="image/*" required>
                <!-- Imagen original -->
                <div class="crop-container">
                    <img id="imagePreview" style="max-width:100%; display:none;">
                </div>
                <!-- Botones -->
                <div class="crop-buttons">
                    <button type="button" id="cropBtn">Recortar</button>
                    <button type="button" id="resetBtn">Deshacer</button>
                </div>
                <!-- Resultado final -->
                <h4>Vista previa final:</h4>
                <img id="resultPreview" style="max-width:100%; border:1px solid #ccc;">
                <!-- Imagen final enviada al backend -->
                <input type="hidden" name="imagenCrop" id="imagenCrop">
            </div>
            <div class="form-group">
                <label for="url" >Url</label>
                <span>A donde va a redireccionar</span>
                <input type="text" id="url" name="url" required>
            </div>
            <div class="form-group">
                <label for="estado" >Estado</label>
                <span>Activo o Inactivo</span>
                <select id="estado" name="estado" required>
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
            <div class="form-group">
                <label for="Categorias">Categorías</label>
                <div class="checkbox-group">
                    <?php
                        foreach($categorias as $c):
                    ?>
                        <label>
                            <input type="checkbox" name="Categorias[]" value="<?=$c['id_c']?>">
                            <?=$c['nombre']?>
                        </label>
                    <?php
                        endforeach;
                    ?>
                </div>
            </div>
            <div class="form-group">
                <label for="fechaInicio">Fecha de inicio</label>
                <input type="datetime-local" id="fechaInicio" name="fechaInicio" required>
                <label for="fechaFin">Fecha de fin</label>
                <input type="datetime-local" id="fechaFin" name="fechaFin" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-success" name="guardarPublicidad">
                    Guardar publicidad
                </button>
            </div>
        </div>
    </form>
</div>
<?php
    include("./../layout/footerAdmin.php");
?>