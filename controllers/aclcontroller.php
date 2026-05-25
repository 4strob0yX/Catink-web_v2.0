<?php
if(session_status() == PHP_SESSION_NONE){
    session_start();
}
require_once("../views/helpers/helper.php");
function proteger($modulo,$accion,$json=true){
    if(!isset($_SESSION['ACL'])){
        header("Location: ../index.php");
        exit();
    }
    if(!($_SESSION['superadmin'] ?? false)){
        if(!tienePermiso($modulo,$accion)){
            if($json){
                header("Content-Type: application/json");
                echo json_encode([
                    "success"=>false,
                    "error"=>"Acceso denegado"
                ]);
            }else{
                header("Location: admin.php");
            }
            exit();
        }
    }
}