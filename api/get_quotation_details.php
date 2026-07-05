<?php
// TEMPORARY DEVELOPMENT MODE - AUTH DISABLED
// require_once '../auth.php';
require_once '../db.php';

header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
if ($id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid quotation ID']);
    exit;
}

// 1. Fetch Quotation Header
$stmt = $conn->prepare("SELECT * FROM quotations WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$quotation = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$quotation) {
    echo json_encode(['success' => false, 'message' => 'Quotation not found']);
    exit;
}

// 2. Fetch BOQ Items
$items = [];
$itemStmt = $conn->prepare("SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY category, id ASC");
$itemStmt->bind_param('i', $id);
$itemStmt->execute();
$result = $itemStmt->get_result();
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}
$itemStmt->close();

// 3. Fetch all other versions of the same quotation_number
$versions = [];
$verStmt = $conn->prepare("SELECT id, version, status, grand_total, created_at FROM quotations WHERE quotation_number = ? ORDER BY version DESC");
$verStmt->bind_param('s', $quotation['quotation_number']);
$verStmt->execute();
$verResult = $verStmt->get_result();
while ($row = $verResult->fetch_assoc()) {
    $versions[] = $row;
}
$verStmt->close();

echo json_encode([
    'success'   => true,
    'quotation' => $quotation,
    'items'     => $items,
    'versions'  => $versions
]);

$conn->close();
?>
