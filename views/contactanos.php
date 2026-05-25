<?php
include("./../layout/header.php");
?>
<div class="container-fluid">
    <h2>Contactanos</h2>
    <span>Formulario para contactar con CatInk</span>
    <hr>
    <br>
    <div class="card">
        <form action="./../views/email/contacto.php" method="post">
            <div class="form-card">
                <div class="form-group">
                    <label for="name">Nombre:</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="email">Correo electrónico:</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="message">Mensaje:</label>
                    <textarea id="message" name="message" class="form-control" rows="5" required></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Enviar</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php
include("./../layout/footer.php");
?>