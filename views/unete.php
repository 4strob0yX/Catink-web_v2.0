<?php
include("./../layout/header.php");
?>
<div class="container-fluid">
    <h2>Unete a nuestro equipo</h2>
    <span>Formulario para enviar solicitud de pasantia en CatInk</span>
    <hr>
    <br>
    <div class="card">
        <div class="row">
            <div class="col-md-4">
                <img src="./../img/reclutamineto.jpg" alt="Reclutamiento" class="card-img-left">
            </div>
            <div class="col-md-8">
                <div class="card-body">
                    <h5 class="card-title">¿Quieres ser parte de CatInk?</h5>
                    <form action="./../views/email/solicitud.php" method="post" enctype="multipart/form-data">
                        <div class="form-card">
                            <div class="form-group">
                                <label for="nombre">Nombre completo</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Correo electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="razon">¿Por qué quieres unirte a CatInk?</label>
                                <textarea class="form-control" id="razon" name="razon" rows="3" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="cv">Sube tu CV</label>
                                <input type="file" class="form-control-file" id="cv" name="cv" required accept=".pdf,.doc,.docx">
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-success">Enviar solicitud</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include("./../layout/footer.php");
?>