<?php
// TEMPORARY DEVELOPMENT MODE - AUTH DISABLED
// require_once '../auth.php';
require_once '../db.php';

header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
if ($id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid lead ID']);
    exit;
}

// 1. Fetch Lead details
$stmt = $conn->prepare("SELECT * FROM leads WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$lead = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lead) {
    echo json_encode(['success' => false, 'message' => 'Lead not found']);
    exit;
}
$lead['lead_id'] = $lead['lead_code'] ?? '';

// 2. Fetch Activity Timeline & Status History
$activities = [];
$actQuery = $conn->prepare("SELECT * FROM lead_activities WHERE lead_id = ? ORDER BY created_at DESC, id DESC");
$actQuery->bind_param('i', $id);
$actQuery->execute();
$actResult = $actQuery->get_result();
while ($row = $actResult->fetch_assoc()) {
    $activities[] = $row;
}
$actQuery->close();

// 3. Fetch Follow-up History
$followups = [];
$folQuery = $conn->prepare("SELECT * FROM lead_followups WHERE lead_id = ? ORDER BY followup_date DESC, followup_time DESC, id DESC");
$folQuery->bind_param('i', $id);
$folQuery->execute();
$folResult = $folQuery->get_result();
while ($row = $folResult->fetch_assoc()) {
    $followups[] = $row;
}
$folQuery->close();

// 4. Fetch Site Visit History
$sitevisits = [];
$svQuery = $conn->prepare("SELECT * FROM lead_site_visits WHERE lead_id = ? ORDER BY visit_date DESC, id DESC");
$svQuery->bind_param('i', $id);
$svQuery->execute();
$svResult = $svQuery->get_result();
while ($row = $svResult->fetch_assoc()) {
    $sitevisits[] = $row;
}
$svQuery->close();

echo json_encode([
    'success'    => true,
    'lead'       => $lead,
    'activities' => $activities,
    'followups'  => $followups,
    'sitevisits' => $sitevisits
]);

$conn->close();
?>
