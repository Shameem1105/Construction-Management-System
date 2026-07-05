<?php
// TEMPORARY DEVELOPMENT MODE - AUTH DISABLED
// require_once '../auth.php';
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$lead_id      = intval($_POST['lead_id'] ?? 0);
$visit_date   = trim($_POST['visit_date'] ?? '');
$engineer     = trim($_POST['engineer'] ?? '');
$site_address = trim($_POST['site_address'] ?? '');
$remarks      = trim($_POST['remarks'] ?? '');
$status       = trim($_POST['status'] ?? 'Scheduled');

if ($lead_id === 0 || $visit_date === '' || $engineer === '') {
    echo json_encode(['success' => false, 'message' => 'Lead ID, Visit Date and Engineer are required']);
    exit;
}

// 1. Insert into lead_site_visits
$stmt = $conn->prepare(
    "INSERT INTO lead_site_visits (lead_id, visit_date, engineer, site_address, remarks, status)
     VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('isssss', $lead_id, $visit_date, $engineer, $site_address, $remarks, $status);

if ($stmt->execute()) {
    // 2. Update address in the leads table if lead address is empty
    if ($site_address !== '') {
        $checkStmt = $conn->prepare("SELECT address FROM leads WHERE id = ?");
        $checkStmt->bind_param('i', $lead_id);
        $checkStmt->execute();
        $leadRow = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if (empty($leadRow['address'])) {
            $updAddr = $conn->prepare("UPDATE leads SET address = ? WHERE id = ?");
            $updAddr->bind_param('si', $site_address, $lead_id);
            $updAddr->execute();
            $updAddr->close();
        }
    }

    // 3. Log into activity timeline
    $activity_type = 'Site Visit';
    $act_desc = "Site Visit $status by Engineer $engineer. Address: " . ($site_address !== '' ? $site_address : 'N/A') . ". Remarks: " . ($remarks !== '' ? $remarks : 'None');
    
    $actStmt = $conn->prepare("INSERT INTO lead_activities (lead_id, activity_type, description, user_name) VALUES (?, ?, ?, 'System')");
    $actStmt->bind_param('iss', $lead_id, $activity_type, $act_desc);
    $actStmt->execute();
    $actStmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Site visit recorded successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
