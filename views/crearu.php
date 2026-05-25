<?php
    include("./../layout/headerAdmin.php");
    include("./../controllers/aclcontroller.php");
    $ACL = $_SESSION['ACL']['usuarios']??[
        'crear' => false,
        'leer' => false,
        'editar' => false,
        'eliminar' => false,
    ];
    if (!$ACL['crear']) {
        header("Location: admin.php");
        exit();
    }
    proteger('usuarios', 'crear');
    include("./../data/conexion.php");
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Crear Usuario</h1>
    </div>
    <form id="formUsuario" action="./../controllers/altausuarios.php" method="POST">
        <div class="form-card card">
            <div class="form-group">
                <label for="nombre">Nombre</label>
                <span>Nombre completo</span>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <span>Nombre de usuario</span>
                <input type="text" id="usuario" name="usuario" required>
                <small id="usuarioEstado"></small>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <span>Correo electrónico</span>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <span>Contraseña</span>
                <input type="text" id="password" name="password" required>
                <span>Confirma contraseña</span>
                <input type="text" id="confirm_password" name="confirm_password" required>
                <small id="errorPassword" style="color:#dc3545; display:none;">
                    Las contraseñas no coinciden
                </small>
            </div>
            <div class="form-group">
                <div class="row-permisos">
                    <div class="col-permiso">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Permisos Publicidad</h5>
                            </div>
                            <div class="card-body form-group">
                                <label><input type="checkbox" name="publicidad[]" value="1"> Crear</label>
                                <span>Capacidad de agregar nueva publicidad</span>
                                <label><input type="checkbox" name="publicidad[]" value="2"> Ver</label>
                                <span>Capacidad de acceder y ver en la seccion de publicidad</span>
                                <label><input type="checkbox" name="publicidad[]" value="4"> Editar</label>
                                <span>Capacidad de modificar la publicidad existente</span>
                                <label><input type="checkbox" name="publicidad[]" value="8"> Eliminar</label>
                                <span>Capacidad de eliminar alguna publicidad</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-permiso">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Permisos Noticias</h5>
                            </div>
                            <div class="card-body form-group">
                                <label><input type="checkbox" name="noticias[]" value="1"> Crear</label>
                                <span>Capacidad de crear nuevas publicaciones</span>
                                <label><input type="checkbox" name="noticias[]" value="2"> Ver</label>
                                <span>Capacidad de acceder y ver en la seccion de contenidos</span>
                                <label><input type="checkbox" name="noticias[]" value="4"> Editar</label>
                                <span>Capacidad de modificar las publicaciones</span>
                                <label><input type="checkbox" name="noticias[]" value="8"> Eliminar</label>
                                <span>Capacidad de eliminar las publicaciones</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-permiso">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Permisos Categoría</h5>
                            </div>
                            <div class="card-body form-group">
                                <label><input type="checkbox" name="categorias[]" value="1"> Crear</label>
                                <span>Capacidad de agregar una nueva categoría</span>
                                <label><input type="checkbox" name="categorias[]" value="2"> Ver</label>
                                <span>Capacidad de acceder y ver en la seccion de categorías</span>  
                                <label><input type="checkbox" name="categorias[]" value="4"> Editar</label>
                                <span>Capacidad de editar el nombre de la categoría</span>
                                <label><input type="checkbox" name="categorias[]" value="8"> Eliminar</label>
                                <span>Capacidad de eliminar categorias</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-permiso">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Permisos Correo</h5>
                            </div>
                            <div class="card-body form-group">
                                <label><input type="checkbox" name="correos[]" value="1"> Crear</label>
                                <span>Capacidad de generar nuevos correos</span>
                                <label><input type="checkbox" name="correos[]" value="2"> Ver</label>
                                <span>Capacidad de acceder y ver en la seccion de Correos (publicitarios)</span>  
                                <label><input type="checkbox" name="correos[]" value="4"> Editar</label>
                                <span>Capacidad de editar los corros (reenvio)</span>
                                <label><input type="checkbox" name="correos[]" value="8"> Eliminar</label>
                                <span>Capacidad de eliminar correos</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-permiso">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Permisos Suscripciones</h5>
                            </div>
                            <div class="card-body form-group">
                                <label><input type="checkbox" name="suscripciones[]" value="1"> Crear</label>
                                <span>La suscripcion la hacen los usuarios</span>
                                <label><input type="checkbox" name="suscripciones[]" value="2"> Ver</label>
                                <span>Capacidad de acceder y ver en la seccion de suscripciones</span>
                                <label><input type="checkbox" name="suscripciones[]" value="4"> Editar</label>
                                <span>Los usuarios activan o desactivan su suscripcion</span>
                                <label><input type="checkbox" name="suscripciones[]" value="8"> Eliminar</label>
                                <span>Capacidad de eliminar las suscripciones</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-permiso">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Permisos Usuarios</h5>
                            </div>
                            <div class="card-body form-group">
                                <label><input type="checkbox" name="usuarios[]" value="1"> Crear</label>
                                <span>Capacidad de añadir usuarios al sistema</span>
                                <label><input type="checkbox" name="usuarios[]" value="2"> Ver</label>
                                <span>Capacidad de acceder y ver en la seccion de Usuarios</span>
                                <label><input type="checkbox" name="usuarios[]" value="4"> Editar</label>
                                <span>Capacidad de ediar los usuarios (permisos)</span>
                                <label><input type="checkbox" name="usuarios[]" value="8"> Eliminar</label>
                                <span>Capacidad de eliminar usuarios del sistema</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-permiso">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Permisos Videos</h5>
                            </div>
                            <div class="card-body form-group">
                                <label><input type="checkbox" name="videos[]" value="1"> Crear</label>
                                <span>Capacidad de agregar nuevos videos</span>
                                <label><input type="checkbox" name="videos[]" value="2"> Ver</label>
                                <span>Capacidad de acceder y ver en la seccion de videos</span>  
                                <label><input type="checkbox" name="videos[]" value="4"> Editar</label>
                                <span>Capacidad de editar los videos (reemplazar)</span>
                                <label><input type="checkbox" name="videos[]" value="8"> Eliminar</label>
                                <span>Capacidad de eliminar videos existentes</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <?php if($ACL['crear']): ?>
                    <button type="submit" class="btn btn-success">Crear Usuario</button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById('formUsuario');
    const inputUsuario = document.getElementById('usuario');
    const estado = document.getElementById('usuarioEstado');

    const passInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const errorPassword = document.getElementById('errorPassword');

    let usuarioValido = false;

    // Validación usuario en tiempo real
    inputUsuario.addEventListener('keyup', () => {
        const usuario = inputUsuario.value.trim();

        if (usuario.length < 3) {
            estado.textContent = 'El usuario debe tener al menos 3 caracteres';
            estado.style.color = '#ffc107';
            usuarioValido = false;
            return;
        }

        fetch(`./../controllers/validar_usuario.php?usuario=${usuario}`)
            .then(res => res.json())
            .then(data => {
                if (data.existe) {
                    estado.textContent = '❌ Usuario no disponible';
                    estado.style.color = '#dc3545';
                    usuarioValido = false;
                } else {
                    estado.textContent = '✅ Usuario disponible';
                    estado.style.color = '#198754';
                    usuarioValido = true;
                }
            })
            .catch(() => {
                estado.textContent = 'Error al validar usuario';
                estado.style.color = '#dc3545';
                usuarioValido = false;
            });
    });

    // Validación final al enviar
    form.addEventListener('submit', (e) => {

        let valido = true;

        // Contraseñas
        if (passInput.value !== confirmInput.value) {
            errorPassword.style.display = 'block';
            valido = false;
        } else {
            errorPassword.style.display = 'none';
        }

        // Usuario
        if (!usuarioValido) {
            alert('El nombre de usuario no es válido o ya existe');
            valido = false;
        }

        if (!valido) {
            e.preventDefault();
        }
    });

});
</script>
<?php include("./../layout/footerAdmin.php"); ?>