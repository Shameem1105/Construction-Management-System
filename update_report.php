<?php
include "db.php";

$project_id = $_POST['project_id'];
$expense = $_POST['expense'];
$budget = $_POST['budget'];
$progress = $_POST['progress'];
$days = $_POST['days'];

// 🔥 UPSERT (INSERT OR UPDATE)
$sql = "
INSERT INTO reports (project_id, expense, budget, progress, days)
VALUES ('$project_id','$expense','$budget','$progress','$days')
ON DUPLICATE KEY UPDATE
expense='$expense',
budget='$budget',
progress='$progress',
days='$days'
";

echo $conn->query($sql) ? "success" : "error";
?>