<?php
include "db.php";

header("Content-Type: application/json"); // ✅ VERY IMPORTANT

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "error", "msg" => "No data received"]);
    exit();
}

$project_id = $data['project_id'] ?? '';
$type = $data['type'] ?? '';

$name = $data['name'] ?? '';
$phone = $data['phone'] ?? '';
$role = $data['role'] ?? '';

$wage_type = $data['wage_type'] ?? '';
$wage_rate = $data['wage_rate'] ?? 0;
$working_hours = $data['working_hours'] ?? 0;

$in_time = $data['in_time'] ?? null;
$out_time = $data['out_time'] ?? null;

$ot_rate = $data['ot_rate'] ?? 0;
$ot_limit = $data['ot_limit'] ?? 0;

// Duplicate check
$check = $conn->query("SELECT * FROM workers WHERE phone='$phone'");
if ($check && $check->num_rows > 0) {
    echo json_encode(["status" => "duplicate"]);
    exit();
}

// Insert
$sql = "INSERT INTO workers 
(project_id, type, name, phone, role, wage_type, wage_rate, working_hours, in_time, out_time, ot_rate, ot_limit, shift_am, shift_pm)
VALUES 
('$project_id','$type','$name','$phone','$role','$wage_type','$wage_rate','$working_hours','$in_time','$out_time','$ot_rate','$ot_limit',0,0)";

if ($conn->query($sql)) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "msg" => $conn->error]);
}
?>