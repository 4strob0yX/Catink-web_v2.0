<?php
include("./../layout/header.php");
?>
<div class="container-fluid">
    <div style="display:flex; justify-content:center; margin-top: 5%;">
        <div class="card" style="width: 22rem; background:transparent; border:0;">
            <div class="card-header">
                <h1 class="text-center">Suscribete</h1>
                <p class="text-center">Suscribete para recibir noticias y actualizaciones sobre nuestras categorías.</p>
            </div>
            <div class="card-body">
                <?php
                    if(isset($_GET['success'])) echo "<p style='color:green; text-align:center'>¡Suscripción registrada! ✔</p>";
                    if(isset($_GET['error'])) echo "<p style='color:red; text-align:center'>Error al suscribirse ❌</p>";
                ?>
                <form action="./../controllers/suscribirse.php" method="POST">
                    <div class="form-card card">
                        <div class="form-group">
                            <label for="nombre">Nombre:</label>
                            <input type="text" id="nombre" name="nombre" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Correo electrónico:</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="sexo">Sexo:</label>
                            <select id="sexo" name="sexo" required>
                                <option value="masculino">Masculino</option>
                                <option value="femenino">Femenino</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-success">Suscribirse</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
include("./../layout/footer.php");
?>