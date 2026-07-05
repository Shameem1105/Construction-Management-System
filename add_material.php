<?php
include "db.php";

$project_id = $_POST['project_id'];
$name = $_POST['name'];
$qty = $_POST['quantity'];
$unit = $_POST['unit'];

$sql = "INSERT INTO materials (project_id, name, quantity, unit)
VALUES ('$project_id','$name','$qty','$unit')";

echo $conn->query($sql) ? "success" : "error";
?>