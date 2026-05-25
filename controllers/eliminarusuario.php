<?php
    include("./../controllers/aclcontroller.php");
    proteger('usuarios', 'eliminar');
    include ('../data/conexion.php');
    $id = $_POST['id'];
    $sql = "DELETE FROM usuarios WHERE id_u = $id";
    $resultado = mysqli_query($con, $sql);
    if($resultado){
        echo "Usuario eliminado correctamente";
        header("Location: ./../views/usuarios.php");
    }else{
        echo "Error al eliminar el usuario";
        header("Location: ./../views/usuarios.php?error=1");
    }