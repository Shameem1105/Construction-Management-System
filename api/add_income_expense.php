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

function cleanDate($value) {
    $value = trim((string) $value);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
}

function sanitizeText($value) {
    return trim((string) $value);
}

function uploadReceipt($file) {
    if (!isset($file) || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        respond(false, 'Unable to upload receipt file.');
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        respond(false, 'Receipt file must be 5MB or smaller.');
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];

    $originalName = $file['name'] ?? '';
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        respond(false, 'Only JPG, PNG, and PDF files are allowed.');
    }

    $mimeType = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }
    }

    if ($mimeType && !in_array($mimeType, $allowedMimeTypes, true)) {
        respond(false, 'Invalid receipt file type.');
    }

    $uploadDir = realpath(__DIR__ . '/../uploads/income_expense');
    if ($uploadDir === false) {
        respond(false, 'Upload directory is not available.');
    }

    $safeName = uniqid('receipt_', true) . '.' . $extension;
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        respond(false, 'Failed to save the receipt file.');
    }

    return 'uploads/income_expense/' . $safeName;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

$entryId = isset($_POST['entry_id']) ? (int) $_POST['entry_id'] : 0;
$projectId = isset($_POST['project_id']) ? (int) $_POST['project_id'] : 0;
$type = strtolower(sanitizeText($_POST['type'] ?? 'income'));
$category = sanitizeText($_POST['category'] ?? '');
$amount = (float) ($_POST['amount'] ?? 0);
$paymentMethod = sanitizeText($_POST['payment_method'] ?? '');
$partyName = sanitizeText($_POST['party_name'] ?? '');
$description = sanitizeText($_POST['description'] ?? '');
$entryDate = cleanDate($_POST['entry_date'] ?? '');

if ($projectId <= 0) {
    respond(false, 'Please select a valid project.');
}

if (!in_array($type, ['income', 'expense'], true)) {
    respond(false, 'Invalid transaction type.');
}

if ($category === '') {
    respond(false, 'Please select a category.');
}

if ($amount <= 0) {
    respond(false, 'Please enter a valid amount.');
}

if ($entryDate === null) {
    respond(false, 'Please select a valid date.');
}

$receiptPath = null;
$existingReceipt = null;

if ($entryId > 0) {
    $existing = $conn->prepare('SELECT receipt FROM income_expense WHERE id = ?');
    $existing->bind_param('i', $entryId);
    $existing->execute();
    $existingResult = $existing->get_result();
    $existingRow = $existingResult ? $existingResult->fetch_assoc() : null;
    $existingReceipt = $existingRow['receipt'] ?? null;
}

if (!empty($_FILES['receipt']['name'])) {
    $receiptPath = uploadReceipt($_FILES['receipt']);
}

if ($entryId > 0) {
    if ($receiptPath === null) {
        $receiptPath = $existingReceipt;
    } elseif ($existingReceipt) {
        $oldFile = __DIR__ . '/../' . $existingReceipt;
        if (is_file($oldFile)) {
            @unlink($oldFile);
        }
    }

    $stmt = $conn->prepare('UPDATE income_expense SET project_id = ?, type = ?, category = ?, amount = ?, payment_method = ?, party_name = ?, description = ?, receipt = ?, entry_date = ? WHERE id = ?');
    $stmt->bind_param('issdsssssi', $projectId, $type, $category, $amount, $paymentMethod, $partyName, $description, $receiptPath, $entryDate, $entryId);
    $success = $stmt->execute();

    if (!$success) {
        respond(false, 'Failed to update entry.', ['error' => $stmt->error]);
    }

    respond(true, 'Entry updated successfully.', ['id' => $entryId]);
}

$stmt = $conn->prepare('INSERT INTO income_expense (project_id, type, category, amount, payment_method, party_name, description, receipt, entry_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
$stmt->bind_param('issdsssss', $projectId, $type, $category, $amount, $paymentMethod, $partyName, $description, $receiptPath, $entryDate);
$success = $stmt->execute();

if (!$success) {
    respond(false, 'Failed to save entry.', ['error' => $stmt->error]);
}

respond(true, 'Entry saved successfully.', ['id' => $stmt->insert_id]);
