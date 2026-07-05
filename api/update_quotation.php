<?php
// TEMPORARY DEVELOPMENT MODE - AUTH DISABLED
// require_once '../auth.php';
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id             = intval($_POST['id'] ?? 0);
$title          = trim($_POST['title'] ?? '');
$client_name    = trim($_POST['client_name'] ?? '');
$client_company = trim($_POST['client_company'] ?? '');
$client_address = trim($_POST['client_address'] ?? '');
$status         = trim($_POST['status'] ?? 'Draft');
$subtotal       = floatval($_POST['subtotal'] ?? 0);
$tax_rate       = floatval($_POST['tax_rate'] ?? 18);
$tax_amount     = floatval($_POST['tax_amount'] ?? 0);
$discount       = floatval($_POST['discount'] ?? 0);
$grand_total    = floatval($_POST['grand_total'] ?? 0);
$notes          = trim($_POST['notes'] ?? '');
$items_json     = trim($_POST['items_json'] ?? '[]');

if ($id === 0 || $title === '' || $client_name === '') {
    echo json_encode(['success' => false, 'message' => 'ID, Title, and Client Name are required']);
    exit;
}

$items = json_decode($items_json, true);
if (!is_array($items)) {
    echo json_encode(['success' => false, 'message' => 'Invalid BOQ items format']);
    exit;
}

// 1. Verify Quotation is editable (only if Draft or Sent)
$checkQuery = $conn->prepare("SELECT status, lead_id, quotation_number, version FROM quotations WHERE id = ?");
$checkQuery->bind_param('i', $id);
$checkQuery->execute();
$qRow = $checkQuery->get_result()->fetch_assoc();
$checkQuery->close();

if (!$qRow) {
    echo json_encode(['success' => false, 'message' => 'Quotation not found']);
    exit;
}

if ($qRow['status'] === 'Approved' || $qRow['status'] === 'Revised') {
    echo json_encode(['success' => false, 'message' => 'This quotation is locked because it is ' . $qRow['status']]);
    exit;
}

// 2. Update Quotation Header
$stmt = $conn->prepare(
    "UPDATE quotations SET title=?, client_name=?, client_company=?, client_address=?, status=?,
     subtotal=?, tax_rate=?, tax_amount=?, discount=?, grand_total=?, notes=? WHERE id=?"
);
$stmt->bind_param(
    'sssssdddddsi',
    $title, $client_name, $client_company, $client_address, $status,
    $subtotal, $tax_rate, $tax_amount, $discount, $grand_total, $notes, $id
);

if ($stmt->execute()) {
    // 3. Recreate BOQ Items: Delete old and insert new
    $delStmt = $conn->prepare("DELETE FROM quotation_items WHERE quotation_id = ?");
    $delStmt->bind_param('i', $id);
    $delStmt->execute();
    $delStmt->close();

    $itemStmt = $conn->prepare(
        "INSERT INTO quotation_items (quotation_id, category, description, unit, quantity, rate, amount)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($items as $item) {
        $cat  = trim($item['category'] ?? 'General');
        $desc = trim($item['description'] ?? '');
        $unit = trim($item['unit'] ?? 'Nos');
        $qty  = floatval($item['quantity'] ?? 0);
        $rate = floatval($item['rate'] ?? 0);
        $amt  = $qty * $rate;

        if ($desc !== '') {
            $itemStmt->bind_param('isssddd', $id, $cat, $desc, $unit, $qty, $rate, $amt);
            $itemStmt->execute();
        }
    }
    $itemStmt->close();

    // 4. Log Activity in CRM Leads (if linked and status changed)
    $lead_id = intval($qRow['lead_id']);
    if ($lead_id > 0) {
        $activity_type = 'Quotation Updated';
        $act_desc = "Quotation " . $qRow['quotation_number'] . " (Version " . $qRow['version'] . ") updated. Status set to: $status. Total: ₹" . number_format($grand_total, 2);
        
        $actStmt = $conn->prepare("INSERT INTO lead_activities (lead_id, activity_type, description, user_name) VALUES (?, ?, ?, 'System')");
        $actStmt->bind_param('iss', $lead_id, $activity_type, $act_desc);
        $actStmt->execute();
        $actStmt->close();
    }

    echo json_encode(['success' => true, 'message' => 'Quotation updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
