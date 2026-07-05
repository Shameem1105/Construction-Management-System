<?php
include __DIR__ . '/db.php';

function cleanDate($value) {
    $value = trim((string) $value);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
}

function bindParams($stmt, $types, $params) {
    if ($types === '' || !$params) {
        return true;
    }

    $refs = [];
    foreach ($params as $key => $value) {
        $refs[$key] = &$params[$key];
    }

    array_unshift($refs, $types);
    return call_user_func_array([$stmt, 'bind_param'], $refs);
}

function fetchRows($conn, $filters) {
    $sql = 'SELECT ie.id, ie.project_id, ie.type, ie.category, ie.amount, ie.payment_method, ie.party_name, ie.description, ie.receipt, ie.entry_date, ie.created_at, COALESCE(p.title, CONCAT("Project #", ie.project_id)) AS project_title FROM income_expense ie LEFT JOIN projects p ON p.id = ie.project_id WHERE 1=1';
    $types = '';
    $params = [];

    if (!empty($filters['project_id']) && $filters['project_id'] !== 'all') {
        $sql .= ' AND ie.project_id = ?';
        $types .= 'i';
        $params[] = (int) $filters['project_id'];
    }

    if (!empty($filters['from_date'])) {
        $sql .= ' AND ie.entry_date >= ?';
        $types .= 's';
        $params[] = $filters['from_date'];
    }

    if (!empty($filters['to_date'])) {
        $sql .= ' AND ie.entry_date <= ?';
        $types .= 's';
        $params[] = $filters['to_date'];
    }

    if (!empty($filters['search'])) {
        $sql .= ' AND (ie.category LIKE ? OR ie.party_name LIKE ? OR ie.description LIKE ? OR p.title LIKE ?)';
        $types .= 'ssss';
        $like = '%' . $filters['search'] . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= ' ORDER BY ie.entry_date DESC, ie.created_at DESC, ie.id DESC';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    bindParams($stmt, $types, $params);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    return $rows;
}

function resolveProjectLabel($conn, $projectId) {
    $projectId = (int) $projectId;
    if ($projectId <= 0) {
        return 'All Projects';
    }

    $stmt = $conn->prepare('SELECT title FROM projects WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return 'Project #' . $projectId;
    }

    $stmt->bind_param('i', $projectId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;

    return $row['title'] ?? ('Project #' . $projectId);
}

function computeSummary($rows) {
    $totalIncome = 0;
    $totalExpense = 0;

    foreach ($rows as $row) {
        $amount = (float) ($row['amount'] ?? 0);
        if (($row['type'] ?? '') === 'expense') {
            $totalExpense += $amount;
        } else {
            $totalIncome += $amount;
        }
    }

    return [
        'total_income' => $totalIncome,
        'total_expense' => $totalExpense,
        'net_profit' => $totalIncome - $totalExpense,
        'transaction_count' => count($rows)
    ];
}

function pdfText($value) {
    $text = (string) $value;
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
        if ($converted !== false) {
            $text = $converted;
        }
    }

    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function shortenText($value, $limit) {
    $text = trim((string) $value);
    if (strlen($text) <= $limit) {
        return $text;
    }

    return rtrim(substr($text, 0, max(0, $limit - 3))) . '...';
}

function moneyText($value) {
    return 'Rs ' . number_format((float) $value, 2);
}

function pdfLine($x, $y, $font, $size, $text) {
    return sprintf('BT /%s %d Tf 1 0 0 1 %d %d Tm (%s) Tj ET', $font, $size, $x, $y, pdfText($text));
}

function buildPageContent($pageRows, $summary, $filters, $pageNumber, $totalPages) {
    $lines = [];

    $lines[] = pdfLine(40, 800, 'F2', 18, 'JGC Income & Expense Report');
    $lines[] = pdfLine(40, 782, 'F1', 10, 'Premium gold summary for the selected project and date range');

    $filterProject = !empty($filters['project_id']) && $filters['project_id'] !== 'all'
        ? resolveProjectLabel($GLOBALS['conn'], $filters['project_id'])
        : 'All Projects';
    $filterDates = ($filters['from_date'] ?? '') . ' to ' . ($filters['to_date'] ?? '');
    $lines[] = pdfLine(40, 764, 'F1', 9, 'Filters: ' . $filterProject . ' | Date Range: ' . $filterDates);

    $summaryLine = sprintf(
        'Income: %s   Expense: %s   Net: %s   Transactions: %d',
        moneyText($summary['total_income'] ?? 0),
        moneyText($summary['total_expense'] ?? 0),
        moneyText($summary['net_profit'] ?? 0),
        (int) ($summary['transaction_count'] ?? 0)
    );
    $lines[] = pdfLine(40, 744, 'F2', 10, $summaryLine);

    $lines[] = pdfLine(40, 710, 'F2', 9, 'Date        Type     Category           Project              Party                Method      Amount');
    $lines[] = pdfLine(40, 705, 'F1', 9, str_repeat('-', 96));

    $y = 688;
    if (!$pageRows) {
        $lines[] = pdfLine(40, 670, 'F1', 10, 'No transactions found for the selected filters.');
    } else {
        foreach ($pageRows as $row) {
            $date = shortenText($row['entry_date'] ?? '-', 10);
            $type = shortenText(ucfirst((string) ($row['type'] ?? '-')), 7);
            $category = shortenText($row['category'] ?? '-', 17);
            $project = shortenText($row['project_title'] ?? '-', 18);
            $party = shortenText($row['party_name'] ?? '-', 18);
            $method = shortenText($row['payment_method'] ?? '-', 10);
            $amount = shortenText(moneyText($row['amount'] ?? 0), 11);
            $rowText = sprintf('%-10s %-8s %-18s %-20s %-20s %-10s %11s', $date, $type, $category, $project, $party, $method, $amount);
            $lines[] = pdfLine(40, $y, 'F3', 8, $rowText);
            $y -= 16;
        }
    }

    $lines[] = pdfLine(40, 36, 'F1', 8, 'Page ' . $pageNumber . ' of ' . $totalPages);

    return implode("\n", $lines);
}

function buildPdf(array $pages) {
    $catalogId = 1;
    $pagesId = 2;
    $fontRegularId = 3;
    $fontBoldId = 4;
    $fontMonoId = 5;

    $pageObjects = [];
    $contentObjects = [];
    $objectId = 6;

    foreach ($pages as $page) {
        $contentObjects[] = $objectId++;
        $pageObjects[] = $objectId++;
    }

    $objects = [];
    $objects[$catalogId] = '<< /Type /Catalog /Pages ' . $pagesId . ' 0 R >>';
    $kids = [];
    foreach ($pageObjects as $pageObjectId) {
        $kids[] = $pageObjectId . ' 0 R';
    }

    $objects[$pagesId] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($pageObjects) . ' >>';
    $objects[$fontRegularId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
    $objects[$fontBoldId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
    $objects[$fontMonoId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>';

    foreach ($pages as $index => $pageContent) {
        $contentId = $contentObjects[$index];
        $pageId = $pageObjects[$index];
        $stream = $pageContent;
        $objects[$contentId] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
        $objects[$pageId] = '<< /Type /Page /Parent ' . $pagesId . ' 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 ' . $fontRegularId . ' 0 R /F2 ' . $fontBoldId . ' 0 R /F3 ' . $fontMonoId . ' 0 R >> >> /Contents ' . $contentId . ' 0 R >>';
    }

    ksort($objects);

    $pdf = "%PDF-1.4\n";
    $offsets = [0 => 0];

    foreach ($objects as $id => $body) {
        $offsets[$id] = strlen($pdf);
        $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
    }

    $xrefOffset = strlen($pdf);
    $count = max(array_keys($objects)) + 1;
    $pdf .= 'xref' . "\n0 " . $count . "\n";
    $pdf .= sprintf("%010d %05d f \n", 0, 65535);
    for ($i = 1; $i < $count; $i++) {
        $pdf .= sprintf("%010d %05d n \n", $offsets[$i] ?? 0, 0);
    }
    $pdf .= 'trailer << /Size ' . $count . ' /Root ' . $catalogId . ' 0 R >>' . "\n";
    $pdf .= 'startxref' . "\n" . $xrefOffset . "\n%%EOF";

    return $pdf;
}

$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'project_id' => trim($_GET['project_id'] ?? 'all'),
    'from_date' => cleanDate($_GET['from_date'] ?? ''),
    'to_date' => cleanDate($_GET['to_date'] ?? '')
];

if (!$filters['from_date'] || !$filters['to_date']) {
    $today = new DateTimeImmutable('today');
    $filters['to_date'] = $filters['to_date'] ?: $today->format('Y-m-d');
    $filters['from_date'] = $filters['from_date'] ?: $today->modify('first day of this month')->format('Y-m-d');
}

$rows = fetchRows($conn, $filters);
$summary = computeSummary($rows);

$rowsPerPage = 22;
$pages = [];
$chunks = array_chunk($rows, $rowsPerPage);
if (!$chunks) {
    $chunks = [[]];
}

$totalPages = count($chunks);
foreach ($chunks as $index => $chunk) {
    $pages[] = buildPageContent($chunk, $summary, $filters, $index + 1, $totalPages);
}

$pdf = buildPdf($pages);

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="income_expense_report.pdf"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;