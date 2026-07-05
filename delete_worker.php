<?php
include "db.php";

$id = $_POST['id'];

$sql = "DELETE FROM workers WHERE id = '$id'";

echo $conn->query($sql) ? "success" : "error";
?>