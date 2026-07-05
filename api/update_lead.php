<?php
// TEMPORARY DEVELOPMENT MODE - AUTH DISABLED
// require_once '../auth.php';
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id           = intval($_POST['id'] ?? 0);
$name         = trim($_POST['lead_name']    ?? '');
$company      = trim($_POST['company']      ?? '');
$phone        = trim($_POST['phone']        ?? '');
$email        = trim($_POST['email']        ?? '');
$source       = trim($_POST['source']       ?? 'Other');
$status       = trim($_POST['status']       ?? 'New Lead');
$owner        = trim($_POST['owner']        ?? '');
$project_type = trim($_POST['project_type'] ?? '');
$notes        = trim($_POST['notes']        ?? '');
$followup_raw = trim($_POST['followup_date']?? '');
$gst_number   = trim($_POST['gst_number']   ?? '');
$address      = trim($_POST['address']      ?? '');
$budget       = trim($_POST['budget']       ?? '');

if ($id === 0 || $name === '' || $phone === '') {
    echo json_encode(['success' => false, 'message' => 'ID, name and phone are required']);
    exit;
}

if ($gst_number !== '') {
    if (!preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[A-Z0-9]{1}Z[A-Z0-9]{1}$/', $gst_number)) {
        echo json_encode(['success' => false, 'message' => 'Invalid GSTIN format. Expected format: 22AAAAA0000A1Z5']);
        exit;
    }
}

$followup_date = ($followup_raw !== '') ? $followup_raw : null;
$next_followup_date = $followup_date;
$company_name = $company;
$requirement_type = $project_type;
$lead_source = $source;
$lead_status = $status;
$assigned_to = $owner;
$budget_val = ($budget !== '') ? floatval($budget) : null;

// Fetch previous status & owner for activity logging
$prevStatus = '';
$prevOwner = '';
$prevQuery = $conn->prepare("SELECT status, owner FROM leads WHERE id = ?");
if ($prevQuery) {
    $prevQuery->bind_param('i', $id);
    $prevQuery->execute();
    $prevResult = $prevQuery->get_result()->fetch_assoc();
    if ($prevResult) {
        $prevStatus = $prevResult['status'] ?? '';
        $prevOwner = $prevResult['owner'] ?? '';
    }
    $prevQuery->close();
}

$stmt = $conn->prepare(
    "UPDATE leads SET name=?, company=?, phone=?, email=?, source=?, status=?,
     owner=?, project_type=?, notes=?, followup_date=?, gst_number=?, company_name=?,
     address=?, requirement_type=?, budget=?, lead_source=?, lead_status=?, assigned_to=?,
     next_followup_date=? WHERE id=?"
);
$stmt->bind_param(
    'sssssssssssssssdsssi',
    $name, $company, $phone, $email,
    $source, $status, $owner, $project_type, $notes, $followup_date,
    $gst_number, $company_name, $address, $requirement_type, $budget_val, $lead_source, $lead_status, $assigned_to, $next_followup_date, $id
);

if ($stmt->execute()) {
    // Log Status Change Activity
    if ($status !== '' && $status !== $prevStatus) {
        $activity_type = 'Status Change';
        $act_desc = "Status updated from '$prevStatus' to '$status'";
        $actStmt = $conn->prepare("INSERT INTO lead_activities (lead_id, activity_type, description, user_name) VALUES (?, ?, ?, 'System')");
        $actStmt->bind_param('iss', $id, $activity_type, $act_desc);
        $actStmt->execute();
        $actStmt->close();
    }
    // Log Owner Change Activity
    if ($owner !== '' && $owner !== $prevOwner) {
        $activity_type = 'Owner Assignment';
        $act_desc = "Owner assigned from '$prevOwner' to '$owner'";
        $actStmt = $conn->prepare("INSERT INTO lead_activities (lead_id, activity_type, description, user_name) VALUES (?, ?, ?, 'System')");
        $actStmt->bind_param('iss', $id, $activity_type, $act_desc);
        $actStmt->execute();
        $actStmt->close();
    }

    echo json_encode(['success' => true, 'message' => 'Lead updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
