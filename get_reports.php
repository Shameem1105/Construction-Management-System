<?php
include "db.php";

// 🔥 JOIN projects + reports
$sql = "
SELECT 
  projects.id,
  projects.title,
  projects.city,
  IFNULL(reports.expense, 0) as expense,
  IFNULL(reports.budget, 0) as budget,
  IFNULL(reports.progress, 0) as progress,
  IFNULL(reports.days, 0) as days
FROM projects
LEFT JOIN reports ON projects.id = reports.project_id
";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>