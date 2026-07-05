<?php
include "db.php";

$id = $_POST['id'];
$conn->query("DELETE FROM materials WHERE id=$id");
?>