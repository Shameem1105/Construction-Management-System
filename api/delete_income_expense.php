<?php
header('Content-Type: application/json');
include __DIR__ . '/../db.php';

function respond($success, $message, $extra = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    respond(false, 'Invalid entry id.');
}

$stmt = $conn->prepare('SELECT receipt FROM income_expense WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$receipt = $row['receipt'] ?? null;

$delete = $conn->prepare('DELETE FROM income_expense WHERE id = ?');
$delete->bind_param('i', $id);
if (!$delete->execute()) {
    respond(false, 'Failed to delete entry.');
}

if ($receipt) {
    $filePath = __DIR__ . '/../' . $receipt;
    if (is_file($filePath)) {
        @unlink($filePath);
    }
}

respond(true, 'Entry deleted successfully.');
