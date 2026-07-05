<?php
include "db.php";

$title = $_POST['title'];
$desc = $_POST['desc'];
$category = $_POST['category'];

$sql = "INSERT INTO updates (title, description, category)
VALUES ('$title','$desc','$category')";

$conn->query($sql);
?>