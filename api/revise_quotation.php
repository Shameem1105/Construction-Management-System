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

// 1. Fetch source quotation details
$stmt = $conn->prepare("SELECT * FROM quotations WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$srcQ = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$srcQ) {
    echo json_encode(['success' => false, 'message' => 'Source quotation not found']);
    exit;
}

// 2. Query the highest version of this quotation number to ensure linear sequence
$seqStmt = $conn->prepare("SELECT id, version, status FROM quotations WHERE quotation_number = ? ORDER BY version DESC LIMIT 1");
$seqStmt->bind_param('s', $srcQ['quotation_number']);
$seqStmt->execute();
$latestQ = $seqStmt->get_result()->fetch_assoc();
$seqStmt->close();

if (!$latestQ) {
    echo json_encode(['success' => false, 'message' => 'Logical quotation versions not found']);
    exit;
}

// 3. Mark the latest version as Revised (if it's not already)
if ($latestQ['status'] !== 'Revised') {
    $updStatus = $conn->prepare("UPDATE quotations SET status = 'Revised' WHERE id = ?");
    $updStatus->bind_param('i', $latestQ['id']);
    $updStatus->execute();
    $updStatus->close();
}

// 4. Create new Quotation Revision header as Draft
$nextVersion = $latestQ['version'] + 1;
$newStatus = 'Draft';

$insStmt = $conn->prepare(
    "INSERT INTO quotations (lead_id, project_id, quotation_number, version, title, status, client_name, client_company, client_address, subtotal, tax_rate, tax_amount, discount, grand_total, notes, created_by)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$insStmt->bind_param(
    'iissssssdddddsss',
    $srcQ['lead_id'], $srcQ['project_id'], $srcQ['quotation_number'], $nextVersion, $srcQ['title'], $newStatus,
    $srcQ['client_name'], $srcQ['client_company'], $srcQ['client_address'],
    $srcQ['subtotal'], $srcQ['tax_rate'], $srcQ['tax_amount'], $srcQ['discount'], $srcQ['grand_total'], $srcQ['notes'], $srcQ['created_by']
);

if ($insStmt->execute()) {
    $new_id = $conn->insert_id;

    // 5. Clone BOQ items from the latest version to link with the new revision ID
    $itemFetch = $conn->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?");
    $itemFetch->bind_param('i', $latestQ['id']);
    $itemFetch->execute();
    $itemsResult = $itemFetch->get_result();

    $itemInsert = $conn->prepare(
        "INSERT INTO quotation_items (quotation_id, category, description, unit, quantity, rate, amount)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    while ($item = $itemsResult->fetch_assoc()) {
        $itemInsert->bind_param(
            'isssddd',
            $new_id, $item['category'], $item['description'], $item['unit'], $item['quantity'], $item['rate'], $item['amount']
        );
        $itemInsert->execute();
    }
    $itemInsert->close();
    $itemFetch->close();

    // 6. Log Activity in CRM Leads (if linked)
    $lead_id = intval($srcQ['lead_id']);
    if ($lead_id > 0) {
        $activity_type = 'Quotation Revised';
        $act_desc = "Quotation " . $srcQ['quotation_number'] . " revised. Version $nextVersion (Draft) was created.";
        
        $actStmt = $conn->prepare("INSERT INTO lead_activities (lead_id, activity_type, description, user_name) VALUES (?, ?, ?, 'System')");
        $actStmt->bind_param('iss', $lead_id, $activity_type, $act_desc);
        $actStmt->execute();
        $actStmt->close();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Quotation revised successfully. New revision created as Draft.',
        'new_id'  => $new_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $insStmt->error]);
}

$insStmt->close();
$conn->close();
?>
