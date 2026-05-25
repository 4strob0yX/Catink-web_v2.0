<?php 
include("./../layout/headerAdmin.php");
include("./../data/conexion.php");
$ACL = $_SESSION['ACL']['suscripciones']??[
    "crear" => false,
    "leer" => false,
    "editar" => false,
    "eliminar" => false
];
if (!$ACL['leer']) {
    header("Location: admin.php");
    exit();
}
$sql = "SELECT * FROM suscripciones";
$resultado = mysqli_query($con, $sql);
$suscripciones = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
$sql2= "SELECT * FROM programacion_correos";
$resultado2 = mysqli_query($con, $sql2);
$programacion = mysqli_fetch_all($resultado2, MYSQLI_ASSOC);
?>
<div class="container-fluid">
    <?php if($superadmin): ?>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Programacion de correos</h1>
        </div>
        <form action="./../controllers/actualizarcorreos.php" method="POST">
            <div class="form-card card">
                <input type="hidden" name="id" value="<?php echo $programacion[0]['id_programacion']; ?>">
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label for="hora">Hora de envio:</label>
                            <input type="time" name="hora" value="<?php echo $programacion[0]['hora']; ?>" required>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label for="estado">Estado:</label>
                            <select name="estado" id="estado" value="<?php echo $programacion[0]['estado']; ?>" required>
                                <option value="activo" <?php if($programacion[0]['estado'] == 'activo') echo 'selected'; ?>>Activo</option>
                                <option value="inactivo" <?php if($programacion[0]['estado'] == 'inactivo') echo 'selected'; ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success" name="actualizarProgramacion">
                        Actualizar programación
                    </button>
                </div>
            </div>
        </form>
        <hr>
    <?php endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Gestión de Suscripciones</h1>
    </div>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Sexo</th>
                    <th>Fecha de alta</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($suscripciones as $suscripcion): ?>
                <tr>
                    <td><?php echo $suscripcion['nombre_completo']; ?></td>
                    <td><?php echo $suscripcion['correo']; ?></td>
                    <td><?php echo $suscripcion['sexo']; ?></td>
                    <td><?php echo $suscripcion['fecha']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include("./../layout/footerAdmin.php"); ?>