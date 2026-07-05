<?php
// TEMPORARY DEVELOPMENT MODE - AUTH DISABLED
// require_once '../auth.php';
require_once '../db.php';

header('Content-Type: application/json');

function respond($success, $message, $extra = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function normalizeHeader($value) {
    $value = strtolower(trim((string) $value));
    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
    return preg_replace('/[^a-z0-9]+/', '', $value);
}

function cleanDateValue($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    if (is_numeric($value)) {
        $serial = (float) $value;
        if ($serial > 0) {
            $timestamp = (int) round(($serial - 25569) * 86400);
            if ($timestamp > 0) {
                return gmdate('Y-m-d', $timestamp);
            }
        }
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return null;
    }

    return date('Y-m-d', $timestamp);
}

function safeText($value) {
    return trim((string) $value);
}

function columnIndexFromRef($ref) {
    if (!preg_match('/^([A-Z]+)/', $ref, $match)) {
        return 0;
    }

    $letters = $match[1];
    $index = 0;
    $length = strlen($letters);
    for ($i = 0; $i < $length; $i++) {
        $index = ($index * 26) + (ord($letters[$i]) - 64);
    }

    return $index - 1;
}

function decodeXlsxText($value) {
    $value = (string) $value;
    if (function_exists('html_entity_decode')) {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
    return trim($value);
}

function parseCsvRows($filePath) {
    $handle = fopen($filePath, 'r');
    if (!$handle) {
        return false;
    }

    $rows = [];
    while (($row = fgetcsv($handle)) !== false) {
        if ($row === [null] || $row === false) {
            continue;
        }
        $rows[] = $row;
    }

    fclose($handle);
    return $rows;
}

function parseXlsxRows($filePath) {
    if (!class_exists('ZipArchive')) {
        return false;
    }

    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        return false;
    }

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $shared = simplexml_load_string($sharedXml);
        if ($shared && isset($shared->si)) {
            foreach ($shared->si as $item) {
                $sharedStrings[] = decodeXlsxText((string) $item->t);
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();

    if ($sheetXml === false) {
        return false;
    }

    $sheet = simplexml_load_string($sheetXml);
    if (!$sheet || !isset($sheet->sheetData->row)) {
        return false;
    }

    $rows = [];
    foreach ($sheet->sheetData->row as $row) {
        $cells = [];
        foreach ($row->c as $cell) {
            $index = columnIndexFromRef((string) $cell['r']);
            $type = (string) $cell['t'];
            $value = '';

            if ($type === 's') {
                $sharedIndex = isset($cell->v) ? (int) $cell->v : 0;
                $value = $sharedStrings[$sharedIndex] ?? '';
            } elseif ($type === 'inlineStr') {
                $value = isset($cell->is->t) ? (string) $cell->is->t : '';
            } elseif ($type === 'b') {
                $value = isset($cell->v) && (string) $cell->v === '1' ? 'TRUE' : 'FALSE';
            } else {
                $value = isset($cell->v) ? (string) $cell->v : '';
            }

            $cells[$index] = decodeXlsxText($value);
        }

        ksort($cells);
        $rows[] = array_values($cells);
    }

    return $rows;
}

function loadSpreadsheetRows($filePath, $extension) {
    if ($extension === 'csv') {
        return parseCsvRows($filePath);
    }
    if ($extension === 'xlsx') {
        return parseXlsxRows($filePath);
    }
    return false;
}

function generateSequentialLeadCode($conn, $yearOffset = 0) {
    $year = date('Y');
    $prefix = "JGC-LD-" . $year . "-";
    
    $codeStmt = $conn->prepare("SELECT lead_code FROM leads WHERE lead_code LIKE ? ORDER BY lead_code DESC LIMIT 1");
    $like = $prefix . '%';
    $codeStmt->bind_param('s', $like);
    $codeStmt->execute();
    $codeResult = $codeStmt->get_result()->fetch_assoc();
    $codeStmt->close();

    if ($codeResult) {
        $lastCode = $codeResult['lead_code'];
        $parts = explode('-', $lastCode);
        $lastSeq = intval(end($parts));
        $newSeq = $lastSeq + 1 + $yearOffset;
    } else {
        $newSeq = 1 + $yearOffset;
    }
    return $prefix . str_pad((string)$newSeq, 5, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    respond(false, 'File upload failed.');
}

$file = $_FILES['file'];
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($extension, ['csv', 'xlsx'], true)) {
    respond(false, 'Only CSV and XLSX file formats are supported.');
}

$rows = loadSpreadsheetRows($file['tmp_name'], $extension);
if (!$rows || count($rows) < 2) {
    respond(false, 'Spreadsheet must contain a header row and at least one data row.');
}

$headerRow = array_shift($rows);
$headerMap = [];
foreach ($headerRow as $index => $header) {
    $headerMap[normalizeHeader($header)] = $index;
}

// Check header mapping for core fields
$nameKeys = ['leadname', 'name', 'contactperson', 'contactname'];
$phoneKeys = ['phone', 'phonenumber', 'mobile', 'mobileconnect'];

$nameColIndex = null;
foreach ($nameKeys as $k) {
    if (isset($headerMap[$k])) {
        $nameColIndex = $headerMap[$k];
        break;
    }
}

$phoneColIndex = null;
foreach ($phoneKeys as $k) {
    if (isset($headerMap[$k])) {
        $phoneColIndex = $headerMap[$k];
        break;
    }
}

if ($nameColIndex === null || $phoneColIndex === null) {
    respond(false, 'Could not map required columns: "Lead Name" and "Phone" must be present in the header row.');
}

// Map optional fields
$fieldMappings = [
    'company' => ['company', 'companyname', 'organization'],
    'email' => ['email', 'emailaddress'],
    'gst_number' => ['gstnumber', 'gstin', 'gst'],
    'address' => ['address', 'siteaddress', 'location'],
    'owner' => ['owner', 'leadowner', 'assignedto', 'assignedowner'],
    'source' => ['leadsource', 'source', 'sourcechannel'],
    'status' => ['leadstatus', 'status', 'stage', 'pipelinestage'],
    'budget' => ['budget', 'estimatedbudget', 'value'],
    'followup_date' => ['followupdate', 'followup', 'nextfollowup'],
    'notes' => ['notes', 'comments', 'description', 'requirement'],
    'lead_code' => ['leadcode', 'code', 'leadid']
];

$colMap = [];
foreach ($fieldMappings as $field => $keys) {
    foreach ($keys as $k) {
        if (isset($headerMap[$k])) {
            $colMap[$field] = $headerMap[$k];
            break;
        }
    }
}

$importedCount = 0;
$failedCount = 0;
$successList = [];
$errorList = [];
$generatedCount = 0;

$conn->begin_transaction();

try {
    foreach ($rows as $rowNumber => $row) {
        $excelRow = $rowNumber + 2;
        
        // Skip completely empty rows
        $nonEmpty = array_filter($row, function($v) { return trim((string)$v) !== ''; });
        if (empty($nonEmpty)) {
            continue;
        }

        $get = function ($field) use ($colMap, $row) {
            if (!isset($colMap[$field])) return '';
            $idx = $colMap[$field];
            return isset($row[$idx]) ? trim((string)$row[$idx]) : '';
        };

        // Extract values
        $name = safeText(isset($row[$nameColIndex]) ? $row[$nameColIndex] : '');
        $phone = safeText(isset($row[$phoneColIndex]) ? $row[$phoneColIndex] : '');
        
        if ($name === '' || $phone === '') {
            $failedCount++;
            $errorList[] = "Row {$excelRow}: Skipped - Lead Name and Phone are required.";
            continue;
        }

        $company = safeText($get('company'));
        $email = safeText($get('email'));
        $gst_number = safeText($get('gst_number'));
        $address = safeText($get('address'));
        $owner = safeText($get('owner'));
        $source = safeText($get('source'));
        $status = safeText($get('status'));
        $budget = safeText($get('budget'));
        $followup_raw = safeText($get('followup_date'));
        $notes = safeText($get('notes'));
        $lead_code = safeText($get('lead_code'));

        // Clean source, status
        if ($source === '') { $source = 'Other'; }
        if ($status === '') { $status = 'New Lead'; }

        // Clean values
        $followup_date = cleanDateValue($followup_raw);
        $next_followup_date = $followup_date;
        $company_name = $company;
        $requirement_type = ''; // placeholder
        $lead_source = $source;
        $lead_status = $status;
        $assigned_to = $owner;
        $budget_val = ($budget !== '') ? floatval(str_replace([',', '₹', ' '], '', $budget)) : null;

        // Validation 1: GSTIN Format Check
        if ($gst_number !== '') {
            if (!preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[A-Z0-9]{1}Z[A-Z0-9]{1}$/', $gst_number)) {
                $failedCount++;
                $errorList[] = "Row {$excelRow} (Lead: '{$name}'): Invalid GSTIN format: '{$gst_number}'. Expected 22AAAAA0000A1Z5.";
                continue;
            }
        }

        // Validation 2: Duplicate check on Phone Number
        $dupStmt = $conn->prepare("SELECT id, name FROM leads WHERE phone = ? LIMIT 1");
        $dupStmt->bind_param('s', $phone);
        $dupStmt->execute();
        $dupResult = $dupStmt->get_result()->fetch_assoc();
        $dupStmt->close();

        if ($dupResult) {
            $failedCount++;
            $errorList[] = "Row {$excelRow} (Lead: '{$name}'): Skipped - Duplicate phone '{$phone}' already belongs to Lead ID '{$dupResult['name']}'.";
            continue;
        }

        // Generate or check Lead Code
        if ($lead_code !== '') {
            // Check uniqueness of provided Lead Code
            $codeCheck = $conn->prepare("SELECT id FROM leads WHERE lead_code = ? LIMIT 1");
            $codeCheck->bind_param('s', $lead_code);
            $codeCheck->execute();
            $codeExists = $codeCheck->get_result()->fetch_assoc();
            $codeCheck->close();

            if ($codeExists) {
                $failedCount++;
                $errorList[] = "Row {$excelRow} (Lead: '{$name}'): Skipped - Lead Code '{$lead_code}' is already assigned in database.";
                continue;
            }
        } else {
            // Auto generate sequential code
            $lead_code = generateSequentialLeadCode($conn, $generatedCount);
            $generatedCount++;
        }

        // Perform Database Insert
        $stmt = $conn->prepare(
            "INSERT INTO leads (name, company, phone, email, source, status, owner, project_type, notes, followup_date, gst_number, company_name, address, requirement_type, budget, lead_source, lead_status, assigned_to, next_followup_date, lead_code)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'sssssssssssssssdssss',
            $name, $company, $phone, $email,
            $source, $status, $owner, $requirement_type, $notes, $followup_date,
            $gst_number, $company_name, $address, $requirement_type, $budget_val, $lead_source, $lead_status, $assigned_to, $next_followup_date, $lead_code
        );

        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            
            // Log Activity Timeline
            $activity_type = 'Lead Created';
            $act_desc = "Lead imported via spreadsheet. Assigned code: " . $lead_code;
            $actStmt = $conn->prepare("INSERT INTO lead_activities (lead_id, activity_type, description, user_name) VALUES (?, ?, ?, 'System')");
            $actStmt->bind_param('iss', $new_id, $activity_type, $act_desc);
            $actStmt->execute();
            $actStmt->close();
            
            $importedCount++;
            $successList[] = "Row {$excelRow} (Lead: '{$name}'): Imported successfully as '{$lead_code}'.";
        } else {
            $failedCount++;
            $errorList[] = "Row {$excelRow} (Lead: '{$name}'): SQL Insert failed - " . $stmt->error;
        }
        $stmt->close();
    }

    $conn->commit();
    respond(true, "Spreadsheet leads import completed. Imported: {$importedCount}, Failed: {$failedCount}.", [
        'imported_count' => $importedCount,
        'failed_count' => $failedCount,
        'successes' => $successList,
        'errors' => $errorList
    ]);

} catch (Throwable $e) {
    $conn->rollback();
    respond(false, 'Transaction rolled back due to error: ' . $e->getMessage());
}

$conn->close();
?>
