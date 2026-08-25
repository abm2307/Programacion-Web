<?php
function connection(){

$server = "localhost";
$user = "root";
$pass = "";
$db = "world";

$conexion = mysqli_connect ($server,$user,$pass,$db);

return $conexion;

};

?>
