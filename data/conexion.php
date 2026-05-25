<?php
    //datos de conexion desde config.php
    $config = require __DIR__ . '/config.php';
    $server = $config['server'];
    $user   = $config['user'];
    $pass   = $config['pass'];
    $dbname = $config['dbname'];
    //sentencia de conexion
    $con=new mysqli($server,$user,$pass,$dbname);
    if($con->connect_error){
        die("la conexion fallo: ".$con->connect_error);
    }
    mysqli_set_charset($con, "utf8mb4");
?>