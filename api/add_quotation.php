<?php
// TEMPORARY DEVELOPMENT MODE - AUTH DISABLED
// require_once '../auth.php';
require_once '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$lead_id        = intval($_POST['lead_id'] ?? 0);
$title          = trim($_POST['title'] ?? '');
$client_name    = trim($_POST['client_name'] ?? '');
$client_company = trim($_POST['client_company'] ?? '');
$client_address = trim($_POST['client_address'] ?? '');
$subtotal       = floatval($_POST['subtotal'] ?? 0);
$tax_rate       = floatval($_POST['tax_rate'] ?? 18);
$tax_amount     = floatval($_POST['tax_amount'] ?? 0);
$discount       = floatval($_POST['discount'] ?? 0);
$grand_total    = floatval($_POST['grand_total'] ?? 0);
$notes          = trim($_POST['notes'] ?? '');
$items_json     = trim($_POST['items_json'] ?? '[]');

if ($title === '' || $client_name === '') {
    echo json_encode(['success' => false, 'message' => 'Title and Client Name are required']);
    exit;
}

$items = json_decode($items_json, true);
if (!is_array($items)) {
    echo json_encode(['success' => false, 'message' => 'Invalid BOQ items format']);
    exit;
}

// 1. Generate unique Quotation Number: QTN-YYYY-XXXX
$year = date('Y');
$prefix = "QTN-$year-";
$maxStmt = $conn->prepare("SELECT quotation_number FROM quotations WHERE quotation_number LIKE ? ORDER BY id DESC LIMIT 1");
$likePrefix = $prefix . '%';
$maxStmt->bind_param('s', $likePrefix);
$maxStmt->execute();
$maxRow = $maxStmt->get_result()->fetch_assoc();
$maxStmt->close();

$num = 1;
if ($maxRow) {
    $last_num = intval(substr($maxRow['quotation_number'], strlen($prefix)));
    $num = $last_num + 1;
}
$quotation_number = $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);

$lead_id_param = ($lead_id > 0) ? $lead_id : null;

// 2. Insert Quotation Header
$stmt = $conn->prepare(
    "INSERT INTO quotations (lead_id, quotation_number, version, title, status, client_name, client_company, client_address, subtotal, tax_rate, tax_amount, discount, grand_total, notes)
     VALUES (?, ?, 1, ?, 'Draft', ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    'isssssddddds',
    $lead_id_param, $quotation_number, $title, $client_name, $client_company, $client_address,
    $subtotal, $tax_rate, $tax_amount, $discount, $grand_total, $notes
);

if ($stmt->execute()) {
    $quotation_id = $conn->insert_id;

    // 3. Insert BOQ Items
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
            $itemStmt->bind_param('isssddd', $quotation_id, $cat, $desc, $unit, $qty, $rate, $amt);
            $itemStmt->execute();
        }
    }
    $itemStmt->close();

    // 4. Log Activity in CRM Leads (if linked)
    if ($lead_id > 0) {
        $activity_type = 'Quotation Created';
        $act_desc = "Quotation Draft $quotation_number (Version 1) created for amount: ₹" . number_format($grand_total, 2);
        $actStmt = $conn->prepare("INSERT INTO lead_activities (lead_id, activity_type, description, user_name) VALUES (?, ?, ?, 'System')");
        $actStmt->bind_param('iss', $lead_id, $activity_type, $act_desc);
        $actStmt->execute();
        $actStmt->close();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Quotation created successfully',
        'id'      => $quotation_id,
        'quotation_number' => $quotation_number
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
