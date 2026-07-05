<?php
include "db.php";

$result = $conn->query("SELECT * FROM updates ORDER BY created_at DESC LIMIT 15");

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>