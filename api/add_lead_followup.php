<?php
// TEMPORARY DEVELOPMENT MODE - AUTH DISABLED
// require_once '../auth.php';
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$lead_id            = intval($_POST['lead_id'] ?? 0);
$type               = trim($_POST['type'] ?? 'Call');
$notes              = trim($_POST['notes'] ?? '');
$followup_date      = trim($_POST['followup_date'] ?? '');
$followup_time      = trim($_POST['followup_time'] ?? '');
$next_followup_date = trim($_POST['next_followup_date'] ?? '');
$outcome            = trim($_POST['outcome'] ?? '');

if ($lead_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid lead ID']);
    exit;
}

$f_date = ($followup_date !== '') ? $followup_date : date('Y-m-d');
$f_time = ($followup_time !== '') ? $followup_time : date('H:i:s');
$next_f_date = ($next_followup_date !== '') ? $next_followup_date : null;

// 1. Insert into lead_followups
$stmt = $conn->prepare(
    "INSERT INTO lead_followups (lead_id, type, notes, followup_date, followup_time, next_followup_date, outcome)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('issssss', $lead_id, $type, $notes, $f_date, $f_time, $next_f_date, $outcome);

if ($stmt->execute()) {
    // 2. Update next follow-up dates in the main leads table
    $updateStmt = $conn->prepare("UPDATE leads SET followup_date = ?, next_followup_date = ? WHERE id = ?");
    $updateStmt->bind_param('ssi', $next_f_date, $next_f_date, $lead_id);
    $updateStmt->execute();
    $updateStmt->close();

    // 3. Log into activity timeline
    $activity_type = $type; // e.g. Call, Meeting, WhatsApp, Email
    $act_desc = "Recorded a follow-up $type. Outcome: " . ($outcome !== '' ? $outcome : 'N/A') . ". Notes: " . ($notes !== '' ? $notes : 'None');
    
    $actStmt = $conn->prepare("INSERT INTO lead_activities (lead_id, activity_type, description, user_name) VALUES (?, ?, ?, 'System')");
    $actStmt->bind_param('iss', $lead_id, $activity_type, $act_desc);
    $actStmt->execute();
    $actStmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Follow-up recorded successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
