<?php
// TEMPORARY DEVELOPMENT MODE - AUTH DISABLED
// require_once '../auth.php';
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid quotation ID']);
    exit;
}

// 1. Fetch details for validation & logging
$checkStmt = $conn->prepare("SELECT quotation_number, status, lead_id, version FROM quotations WHERE id = ?");
$checkStmt->bind_param('i', $id);
$checkStmt->execute();
$qRow = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if (!$qRow) {
    echo json_encode(['success' => false, 'message' => 'Quotation not found']);
    exit;
}

if ($qRow['status'] === 'Approved') {
    echo json_encode(['success' => false, 'message' => 'Approved quotations cannot be deleted to preserve contract audits']);
    exit;
}

// 2. Execute deletion (Cascade delete will remove quotation_items automatically)
$stmt = $conn->prepare("DELETE FROM quotations WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    // 3. Log to lead activities if linked
    $lead_id = intval($qRow['lead_id']);
    if ($lead_id > 0) {
        $activity_type = 'Quotation Deleted';
        $act_desc = "Quotation " . $qRow['quotation_number'] . " (Version " . $qRow['version'] . ") was deleted from the system.";
        
        $actStmt = $conn->prepare("INSERT INTO lead_activities (lead_id, activity_type, description, user_name) VALUES (?, ?, ?, 'System')");
        $actStmt->bind_param('iss', $lead_id, $activity_type, $act_desc);
        $actStmt->execute();
        $actStmt->close();
    }

    echo json_encode(['success' => true, 'message' => 'Quotation deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
