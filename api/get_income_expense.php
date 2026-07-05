<?php
header('Content-Type: application/json');
include __DIR__ . '/../db.php';

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

function computeSummary($rows, $currentRange = null, $previousRange = null, $previousRows = []) {
    $totalIncome = 0;
    $totalExpense = 0;
    $totalTransactions = count($rows);
    $daily = [];

    foreach ($rows as $row) {
        $amount = (float) ($row['amount'] ?? 0);
        $date = $row['entry_date'] ?? date('Y-m-d');
        if (!isset($daily[$date])) {
            $daily[$date] = ['income' => 0, 'expense' => 0, 'transactions' => 0];
        }

        $daily[$date]['transactions'] += 1;

        if (($row['type'] ?? '') === 'expense') {
            $totalExpense += $amount;
            $daily[$date]['expense'] += $amount;
        } else {
            $totalIncome += $amount;
            $daily[$date]['income'] += $amount;
        }
    }

    ksort($daily);
    $seriesIncome = [];
    $seriesExpense = [];
    $seriesProfit = [];
    $seriesTransactions = [];

    foreach ($daily as $bucket) {
        $seriesIncome[] = $bucket['income'];
        $seriesExpense[] = $bucket['expense'];
        $seriesProfit[] = $bucket['income'] - $bucket['expense'];
        $seriesTransactions[] = $bucket['transactions'];
    }

    $previousTotals = ['income' => 0, 'expense' => 0, 'profit' => 0, 'transactions' => 0];
    foreach ($previousRows as $row) {
        $amount = (float) ($row['amount'] ?? 0);
        $previousTotals['transactions'] += 1;
        if (($row['type'] ?? '') === 'expense') {
            $previousTotals['expense'] += $amount;
        } else {
            $previousTotals['income'] += $amount;
        }
    }
    $previousTotals['profit'] = $previousTotals['income'] - $previousTotals['expense'];

    $trend = function ($current, $previous) {
        if ((float) $previous === 0.0) {
            return null;
        }
        return (($current - $previous) / $previous) * 100;
    };

    return [
        'total_income' => $totalIncome,
        'total_expense' => $totalExpense,
        'net_profit' => $totalIncome - $totalExpense,
        'total_transactions' => $totalTransactions,
        'income_trend' => $trend($totalIncome, $previousTotals['income']),
        'expense_trend' => $trend($totalExpense, $previousTotals['expense']),
        'profit_trend' => $trend($totalIncome - $totalExpense, $previousTotals['profit']),
        'transaction_trend' => $trend($totalTransactions, $previousTotals['transactions']),
        'series_income' => $seriesIncome,
        'series_expense' => $seriesExpense,
        'series_profit' => $seriesProfit,
        'series_transactions' => $seriesTransactions,
        'opening_balance' => 0
    ];
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

$rangeDays = max((int) (strtotime($filters['to_date']) - strtotime($filters['from_date'])) / 86400 + 1, 1);
$previousFrom = date('Y-m-d', strtotime($filters['from_date'] . ' -' . $rangeDays . ' days'));
$previousTo = date('Y-m-d', strtotime($filters['from_date'] . ' -1 day'));

$previousFilters = $filters;
$previousFilters['from_date'] = $previousFrom;
$previousFilters['to_date'] = $previousTo;
$previousRows = fetchRows($conn, $previousFilters);

$summary = computeSummary($rows, null, null, $previousRows);

echo json_encode([
    'rows' => $rows,
    'summary' => $summary
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
