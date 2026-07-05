<?php
header('Content-Type: application/json');
include __DIR__ . '/db.php';

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

function lookupProjectId(mysqli $conn, $projectValue) {
    static $cache = [];
    $rawValue = trim((string) $projectValue);
    if ($rawValue === '') {
        return null;
    }

    $cacheKey = strtolower($rawValue);
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    if (ctype_digit($rawValue)) {
        $projectId = (int) $rawValue;
        $stmt = $conn->prepare('SELECT id FROM projects WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $projectId);
    } else {
        $stmt = $conn->prepare('SELECT id FROM projects WHERE LOWER(title) = LOWER(?) LIMIT 1');
        $stmt->bind_param('s', $rawValue);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $projectId = isset($row['id']) ? (int) $row['id'] : null;
    $cache[$cacheKey] = $projectId;

    return $projectId;
}

function extractRows(mysqli $conn, $rows) {
    if (!is_array($rows) || count($rows) < 2) {
        return [[], ['The spreadsheet must contain a header row and at least one data row.']];
    }

    $headerRow = array_shift($rows);
    $headerMap = [];
    foreach ($headerRow as $index => $header) {
        $headerMap[normalizeHeader($header)] = $index;
    }

    $requiredHeaders = [
        'date' => 'Date',
        'type' => 'Type',
        'category' => 'Category',
        'project' => 'Project',
        'amount' => 'Amount',
        'paymentmethod' => 'Payment Method'
    ];

    $missing = [];
    foreach ($requiredHeaders as $key => $label) {
        if (!array_key_exists($key, $headerMap)) {
            $missing[] = $label;
        }
    }

    if ($missing) {
        return [[], ['Missing required columns: ' . implode(', ', $missing) . '.']];
    }

    $records = [];
    $errors = [];

    foreach ($rows as $rowNumber => $row) {
        $excelRowNumber = $rowNumber + 2;
        $get = function ($key) use ($headerMap, $row) {
            $index = $headerMap[$key] ?? null;
            return ($index !== null && array_key_exists($index, $row)) ? trim((string) $row[$index]) : '';
        };

        $entryDate = cleanDateValue($get('date'));
        $type = strtolower($get('type'));
        $category = safeText($get('category'));
        $project = safeText($get('project'));
        $description = safeText($get(normalizeHeader('Description')));
        $party = safeText($get(normalizeHeader('Party')));
        $amountValue = str_replace([',', '₹', ' '], '', $get('amount'));
        $paymentMethod = safeText($get('paymentmethod'));

        $rowErrors = [];

        if ($entryDate === null) {
            $rowErrors[] = 'invalid or missing Date';
        }

        if (!in_array($type, ['income', 'expense'], true)) {
            $rowErrors[] = 'Type must be Income or Expense';
        }

        if ($category === '') {
            $rowErrors[] = 'Category is required';
        }

        if ($project === '') {
            $rowErrors[] = 'Project is required';
        }

        if ($amountValue === '' || !is_numeric($amountValue) || (float) $amountValue <= 0) {
            $rowErrors[] = 'Amount must be a positive number';
        }

        if ($paymentMethod === '') {
            $rowErrors[] = 'Payment Method is required';
        }

        if ($rowErrors) {
            $errors[] = 'Row ' . $excelRowNumber . ': ' . implode('; ', $rowErrors) . '.';
          continue;
        }

        $projectId = lookupProjectId($conn, $project);
        if (!$projectId) {
            $errors[] = 'Row ' . $excelRowNumber . ": project not found for '" . $project . "'.";
            continue;
        }

        $records[] = [
            'project_id' => $projectId,
            'type' => $type,
            'category' => $category,
            'amount' => (float) $amountValue,
            'payment_method' => $paymentMethod,
            'party_name' => $party,
            'description' => $description,
            'entry_date' => $entryDate
        ];
    }

    return [$records, $errors];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
    respond(false, 'Please upload a CSV or XLSX file.');
}

$file = $_FILES['file'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    respond(false, 'Unable to read the uploaded file.');
}

$extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
if (!in_array($extension, ['csv', 'xlsx'], true)) {
    respond(false, 'Only CSV and XLSX files are supported.');
}

$rows = loadSpreadsheetRows($file['tmp_name'], $extension);
if ($rows === false) {
    respond(false, 'Unable to parse the spreadsheet file.');
}

[$records, $errors] = extractRows($conn, $rows);
if ($errors) {
    respond(false, 'Import validation failed.', ['errors' => $errors]);
}

if (!$records) {
    respond(false, 'No valid records were found in the file.');
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare('INSERT INTO income_expense (project_id, type, category, amount, payment_method, party_name, description, receipt, entry_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?, NOW())');
    if (!$stmt) {
        throw new Exception('Failed to prepare insert statement.');
    }

    foreach ($records as $record) {
        $stmt->bind_param(
            'issdssss',
            $record['project_id'],
            $record['type'],
            $record['category'],
            $record['amount'],
            $record['payment_method'],
            $record['party_name'],
            $record['description'],
            $record['entry_date']
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to save one or more rows.');
        }
    }

    $conn->commit();
    respond(true, 'Imported ' . count($records) . ' transaction' . (count($records) === 1 ? '' : 's') . ' successfully.');
} catch (Throwable $throwable) {
    $conn->rollback();
    respond(false, 'Import failed: ' . $throwable->getMessage());
}