<?php
include("./../layout/header.php");
include("./../data/conexion.php");
$sql = "SELECT contenido_pag FROM paginas WHERE nombre_pag='cookies'";
$result = $con->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
} else {
    echo "No se encontró el contenido de 'Política de cookies'.";
}
?>
<div class="container-fluid">
    <div class="post-content">
        <div class="ql-editor">
            <?php echo $row['contenido_pag'] ?? ''; ?>
        </div>
    </div>
</div>
<?php
include("./../layout/footer.php");
?>