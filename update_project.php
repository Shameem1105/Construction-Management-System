<?php
include "db.php";

$id = $_POST['id'];
$title = $_POST['title'];
$city = $_POST['city'];
$address = $_POST['address'];
$start = $_POST['start'];
$end = $_POST['end'];

if (isset($_FILES['image']) && $_FILES['image']['name'] != "") {

    $imageName = $_FILES['image']['name'];
    $tempName = $_FILES['image']['tmp_name'];

    $folder = "uploads/" . time() . "_" . $imageName;
    move_uploaded_file($tempName, $folder);

    $sql = "UPDATE projects SET title='$title', city='$city', address='$address',
            start_date='$start', end_date='$end', image='$folder' WHERE id=$id";

} else {

    $sql = "UPDATE projects SET title='$title', city='$city', address='$address',
            start_date='$start', end_date='$end' WHERE id=$id";
}

echo $conn->query($sql) ? "success" : "error: " . $conn->error;
?>