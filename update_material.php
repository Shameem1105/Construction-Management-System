<?php
include "db.php";

$id = $_POST['id'];
$name = $_POST['name'];
$qty = $_POST['quantity'];
$unit = $_POST['unit'];

$sql = "UPDATE materials 
SET name='$name', quantity='$qty', unit='$unit'
WHERE id=$id";

echo $conn->query($sql) ? "success" : "error";
?>