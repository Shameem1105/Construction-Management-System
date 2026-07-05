<?php
include "db.php";

$title = $_POST['title'];
$city = $_POST['city'];
$address = $_POST['address'];
$start = $_POST['start'];
$end = $_POST['end'];

$imageName = $_FILES['image']['name'];
$tempName = $_FILES['image']['tmp_name'];

$folder = "uploads/" . time() . "_" . $imageName;
move_uploaded_file($tempName, $folder);

$sql = "INSERT INTO projects (title, city, address, start_date, end_date, image)
VALUES ('$title','$city','$address','$start','$end','$folder')";

if ($conn->query($sql)) {
	$projectCode = "PRJ" . str_pad((string) $conn->insert_id, 4, "0", STR_PAD_LEFT);
	echo json_encode([
		"status" => "success",
		"project_id" => $projectCode
	]);
} else {
	echo "error: " . $conn->error;
}
?>