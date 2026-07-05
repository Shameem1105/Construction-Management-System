<?php
include "db.php";

$result = $conn->query("SELECT workers.*, projects.title AS project_title, projects.city AS project_city FROM workers LEFT JOIN projects ON workers.project_id = projects.id ORDER BY workers.id DESC");

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>