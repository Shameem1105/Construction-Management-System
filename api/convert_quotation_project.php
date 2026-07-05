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

// 1. Retrieve Quotation details
$stmt = $conn->prepare("SELECT * FROM quotations WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$q = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$q) {
    echo json_encode(['success' => false, 'message' => 'Quotation not found']);
    exit;
}

if ($q['status'] !== 'Approved') {
    echo json_encode(['success' => false, 'message' => 'Only Approved quotations can be converted to Projects']);
    exit;
}

if (intval($q['project_id']) > 0) {
    echo json_encode(['success' => false, 'message' => 'This quotation has already been converted to a Project (Project ID: ' . $q['project_id'] . ')']);
    exit;
}

// 2. Insert project record into projects table
$pTitle     = $q['title'];
$pAddress   = $q['client_address'];
$pCity      = ''; // Attempt simple city parse or default to Bangalore
if ($pAddress !== '') {
    $parts = explode(',', $pAddress);
    $pCity = trim(end($parts));
    if (strlen($pCity) > 50 || empty($pCity)) {
         $pCity = 'Bangalore';
    }
} else {
    $pCity = 'Bangalore';
}

$startDate  = date('Y-m-d');
$endDate    = date('Y-m-d', strtotime('+6 months')); // Default 6 months completion timeline
$pImage     = ''; // No image uploaded initially

$pStmt = $conn->prepare("INSERT INTO projects (title, city, address, start_date, end_date, image) VALUES (?, ?, ?, ?, ?, ?)");
$pStmt->bind_param('ssssss', $pTitle, $pCity, $pAddress, $startDate, $endDate, $pImage);

if ($pStmt->execute()) {
    $projectId = $conn->insert_id;

    // 3. Link project_id back in quotations table
    $updQuot = $conn->prepare("UPDATE quotations SET project_id = ? WHERE id = ?");
    $updQuot->bind_param('ii', $projectId, $id);
    $updQuot->execute();
    $updQuot->close();

    // 4. Update CRM Lead Status to Won (if linked)
    $lead_id = intval($q['lead_id']);
    if ($lead_id > 0) {
        $updLead = $conn->prepare("UPDATE leads SET status = 'Won', lead_status = 'Won' WHERE id = ?");
        $updLead->bind_param('i', $lead_id);
        $updLead->execute();
        $updLead->close();

        // 5. Log activity to CRM Lead timeline
        $activity_type = 'Status Change';
        $act_desc = "Quotation " . $q['quotation_number'] . " (Version " . $q['version'] . ") converted to Project: '$pTitle'. Lead set to closed Won.";
        
        $actStmt = $conn->prepare("INSERT INTO lead_activities (lead_id, activity_type, description, user_name) VALUES (?, ?, ?, 'System')");
        $actStmt->bind_param('iss', $lead_id, $activity_type, $act_desc);
        $actStmt->execute();
        $actStmt->close();
    }

    echo json_encode([
        'success'    => true,
        'message'    => 'Quotation successfully converted to Project Site',
        'project_id' => $projectId
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $pStmt->error]);
}

$pStmt->close();
$conn->close();
?>
