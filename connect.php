<?php
$host="localhost";
$user="root";
$pass= "";
$db="hotel_base_donnees";
$conn=new mysqli($host,$user,$pass,$db);
if($conn->connect_error){
    echo"Failed to connect to db".$conn->connect_error;
}
?>