<?php
// TEMPORARY DEVELOPMENT MODE - AUTH DISABLED
// require_once '../auth.php';
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$name         = trim($_POST['lead_name']   ?? '');
$company      = trim($_POST['company']     ?? '');
$phone        = trim($_POST['phone']       ?? '');
$email        = trim($_POST['email']       ?? '');
$source       = trim($_POST['source']      ?? 'Other');
$status       = trim($_POST['status']      ?? 'New Lead');
$owner        = trim($_POST['owner']       ?? '');
$project_type = trim($_POST['project_type']?? '');
$notes        = trim($_POST['notes']       ?? '');
$followup_raw = trim($_POST['followup_date'] ?? '');
$gst_number   = trim($_POST['gst_number']   ?? '');
$address      = trim($_POST['address']      ?? '');
$budget       = trim($_POST['budget']       ?? '');

if ($name === '' || $phone === '') {
    echo json_encode(['success' => false, 'message' => 'Name and phone are required']);
    exit;
}

if ($gst_number !== '') {
    if (!preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[A-Z0-9]{1}Z[A-Z0-9]{1}$/', $gst_number)) {
        echo json_encode(['success' => false, 'message' => 'Invalid GSTIN format. Expected format: 22AAAAA0000A1Z5']);
        exit;
    }
}

// Generate sequential lead code (e.g. JGC-LD-2026-00001)
$year = date('Y');
$prefix = "JGC-LD-" . $year . "-";
$codeStmt = $conn->prepare("SELECT lead_code FROM leads WHERE lead_code LIKE ? ORDER BY lead_code DESC LIMIT 1");
$like = $prefix . '%';
$codeStmt->bind_param('s', $like);
$codeStmt->execute();
$codeResult = $codeStmt->get_result()->fetch_assoc();
$codeStmt->close();

if ($codeResult) {
    $lastCode = $codeResult['lead_code'];
    $parts = explode('-', $lastCode);
    $lastSeq = intval(end($parts));
    $newSeq = $lastSeq + 1;
} else {
    $newSeq = 1;
}
$lead_code = $prefix . str_pad((string)$newSeq, 5, '0', STR_PAD_LEFT);

$followup_date = ($followup_raw !== '') ? $followup_raw : null;
$next_followup_date = $followup_date;
$company_name = $company;
$requirement_type = $project_type;
$lead_source = $source;
$lead_status = $status;
$assigned_to = $owner;
$budget_val = ($budget !== '') ? floatval($budget) : null;

$stmt = $conn->prepare(
    "INSERT INTO leads (name, company, phone, email, source, status, owner, project_type, notes, followup_date, gst_number, company_name, address, requirement_type, budget, lead_source, lead_status, assigned_to, next_followup_date, lead_code)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

$stmt->bind_param(
    'sssssssssssssssdssss',
    $name, $company, $phone, $email,
    $source, $status, $owner, $project_type, $notes, $followup_date,
    $gst_number, $company_name, $address, $requirement_type, $budget_val, $lead_source, $lead_status, $assigned_to, $next_followup_date, $lead_code
);

if ($stmt->execute()) {
    $new_id = $conn->insert_id;
    
    // Log Activity
    $activity_type = 'Lead Created';
    $act_desc = "Lead created with status: " . $status;
    $actStmt = $conn->prepare("INSERT INTO lead_activities (lead_id, activity_type, description, user_name) VALUES (?, ?, ?, 'System')");
    $actStmt->bind_param('iss', $new_id, $activity_type, $act_desc);
    $actStmt->execute();
    $actStmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Lead added successfully',
        'id'      => $new_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
