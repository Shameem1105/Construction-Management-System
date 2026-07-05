<?php
include "db.php";

$id = $_POST['id'];
$conn->query("DELETE FROM projects WHERE id=$id");
?>