<?php
header('Content-Type: application/json');

include 'db.php';
mysqli_report(MYSQLI_REPORT_OFF);

function bindParams(mysqli_stmt $stmt, string $types, array $params): void
{
    if ($types === '' || !$params) {
        return;
    }

    $bind = [$types];
    foreach ($params as $index => $value) {
        $bind[] = &$params[$index];
    }

    call_user_func_array([$stmt, 'bind_param'], $bind);
}

function preparedRow(mysqli $conn, string $sql, string $types = '', array $params = []): ?array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }

    bindParams($stmt, $types, $params);

    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ?: null;
}

function preparedRows(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    bindParams($stmt, $types, $params);

    if (!$stmt->execute()) {
        $stmt->close();
        return [];
    }

    $result = $stmt->get_result();
    $rows = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }

    $stmt->close();
    return $rows;
}

function preparedScalar(mysqli $conn, string $sql, string $types = '', array $params = [], $default = 0)
{
    $row = preparedRow($conn, $sql, $types, $params);
    if (!$row) {
        return $default;
    }

    return array_values($row)[0] ?? $default;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['error' => 'Invalid project id']);
    exit;
}

$project = preparedRow(
    $conn,
    'SELECT id, title, city, address, start_date, end_date, image, created_at FROM projects WHERE id = ? LIMIT 1',
    'i',
    [$id]
);

if (!$project) {
    echo json_encode(['error' => 'Project not found']);
    exit;
}

$hasReportFinancialColumns = preparedScalar(
    $conn,
    "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reports' AND COLUMN_NAME IN ('budget', 'expense', 'progress', 'days')",
    '',
    [],
    0
);

$latestReport = preparedRow(
    $conn,
    'SELECT id, report_title, report_description, report_date, created_at FROM reports WHERE project_id = ? ORDER BY created_at DESC, id DESC LIMIT 1',
    'i',
    [$id]
);

$workerRows = preparedRows(
    $conn,
    'SELECT id, name, COALESCE(NULLIF(worker_type, ""), NULLIF(role, ""), "Worker") AS type, phone, COALESCE(NULLIF(role, ""), "") AS extra, shift_am, shift_pm FROM workers WHERE project_id = ? ORDER BY id DESC LIMIT 6',
    'i',
    [$id]
);

$materialRows = preparedRows(
    $conn,
    'SELECT id, material_name AS name, minimum_stock AS quantity, unit FROM materials ORDER BY id DESC LIMIT 6'
);

$workerCount = (int) preparedScalar($conn, 'SELECT COUNT(*) FROM workers WHERE project_id = ?', 'i', [$id], 0);
$materialCount = (int) preparedScalar($conn, 'SELECT COUNT(*) FROM materials', '', [], 0);

$incomeTotal = (float) preparedScalar(
    $conn,
    "SELECT COALESCE(SUM(amount), 0) FROM income_expense WHERE project_id = ? AND type = 'income'",
    'i',
    [$id],
    0
);

$expense = (float) preparedScalar(
    $conn,
    "SELECT COALESCE(SUM(amount), 0) FROM income_expense WHERE project_id = ? AND type = 'expense'",
    'i',
    [$id],
    0
);

$budget = $incomeTotal;
$progress = 0;
$days = 0;

if ($hasReportFinancialColumns >= 4) {
    $financialReport = preparedRow(
        $conn,
        'SELECT budget, expense, progress, days, created_at FROM reports WHERE project_id = ? ORDER BY created_at DESC, id DESC LIMIT 1',
        'i',
        [$id]
    );

    if ($financialReport) {
        $budget = (float) ($financialReport['budget'] ?? $budget);
        $expense = (float) ($financialReport['expense'] ?? $expense);
        $progress = (int) ($financialReport['progress'] ?? $progress);
        $days = (int) ($financialReport['days'] ?? $days);
        $latestReport = $financialReport + ($latestReport ?: []);
    }
}

$startTime = !empty($project['start_date']) ? strtotime($project['start_date']) : false;
$endTime = !empty($project['end_date']) ? strtotime($project['end_date']) : false;
$now = time();

if ($progress <= 0 && $startTime && $endTime && $endTime > $startTime) {
    $duration = $endTime - $startTime;
    $elapsed = max(0, min($duration, $now - $startTime));
    $progress = (int) round(($elapsed / $duration) * 100);
}

$progress = max(0, min(100, $progress));
$remainingBudget = max($budget - $expense, 0);

$status = 'Active';
if ($endTime && $now > $endTime) {
    $status = 'Completed';
}

$durationDays = ($startTime && $endTime && $endTime >= $startTime)
    ? (int) floor(($endTime - $startTime) / 86400) + 1
    : 0;

$materialCost = (float) preparedScalar(
    $conn,
    "SELECT COALESCE(SUM(amount), 0) FROM income_expense WHERE project_id = ? AND type = 'expense' AND (category LIKE 'Material%' OR category = 'Materials')",
    'i',
    [$id],
    0
);

$labourCost = (float) preparedScalar(
    $conn,
    "SELECT COALESCE(SUM(amount), 0) FROM income_expense WHERE project_id = ? AND type = 'expense' AND (category LIKE 'Labour%' OR category LIKE 'Labor%' OR category = 'Worker%')",
    'i',
    [$id],
    0
);

$amountReceived = $incomeTotal;
$amountPending = max(0, $budget - $amountReceived);
$otherCost = max(0, $expense - $materialCost - $labourCost);
$netProfit = $amountReceived - $expense;
$profitPercent = $amountReceived > 0 ? ($netProfit / $amountReceived) * 100 : 0;

$daysRemaining = $endTime ? (int) floor(($endTime - $now) / 86400) : 0;

$projectCode = 'PRJ' . str_pad((string) $project['id'], 4, '0', STR_PAD_LEFT);
$lastUpdated = $latestReport['created_at'] ?? $project['created_at'] ?? '';

echo json_encode([
    'project' => [
        'id' => (int) $project['id'],
        'project_code' => $projectCode,
        'title' => $project['title'] ?? '',
        'city' => $project['city'] ?? '',
        'address' => $project['address'] ?? '',
        'start_date' => $project['start_date'] ?? '',
        'end_date' => $project['end_date'] ?? '',
        'image' => $project['image'] ?? '',
        'status' => $status,
        'progress' => $progress,
        'budget' => $budget,
        'expense' => $expense,
        'remaining_budget' => $remainingBudget,
        'days' => $days,
        'duration_days' => $durationDays,
        'days_remaining' => $daysRemaining,
        'last_updated' => $lastUpdated,
        'worker_count' => $workerCount,
        'material_count' => $materialCount,
        'workers' => $workerRows,
        'materials' => $materialRows,
        'amount_received' => $amountReceived,
        'amount_pending' => $amountPending,
        'material_cost' => $materialCost,
        'labour_cost' => $labourCost,
        'other_cost' => $otherCost,
        'net_profit' => $netProfit,
        'profit_percent' => $profitPercent
    ]
]);