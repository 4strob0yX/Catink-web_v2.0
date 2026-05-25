<?php
function cargarACL($modulo){
    if(!isset($_SESSION['ACL'][$modulo])){
        header("Location: admin.php");
        exit();
    }
    $perm = $_SESSION['ACL'][$modulo];
    // Si no puede leer → fuera
    if(!tienePermiso($modulo,'leer')){
        header("Location: admin.php");
        exit();
    }
    return [
        'crear'     => tienePermiso($modulo,'crear'),
        'leer'      => tienePermiso($modulo,'leer'),
        'editar'    => tienePermiso($modulo,'editar'),
        'eliminar'  => tienePermiso($modulo,'eliminar'),
        'raw'       => $perm
    ];
}
