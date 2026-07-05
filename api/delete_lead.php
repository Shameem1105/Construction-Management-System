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
    echo json_encode(['success' => false, 'message' => 'Invalid lead ID']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM leads WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Lead deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
