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

function calcProjectProgress(array $project): int
{
    $startTime = !empty($project['start_date']) ? strtotime($project['start_date']) : false;
    $endTime = !empty($project['end_date']) ? strtotime($project['end_date']) : false;
    $now = time();

    if (!$startTime || !$endTime || $endTime <= $startTime) {
        return 0;
    }

    $duration = $endTime - $startTime;
    $elapsed = max(0, min($duration, $now - $startTime));

    return (int) round(($elapsed / $duration) * 100);
}

function calcProjectStatus(array $project, int $progress): string
{
    $startTime = !empty($project['start_date']) ? strtotime($project['start_date']) : false;
    $endTime = !empty($project['end_date']) ? strtotime($project['end_date']) : false;
    $now = time();

    if ($endTime && $now > $endTime) {
        return 'Completed';
    }

    if ($startTime && $now < $startTime) {
        return 'On Hold';
    }

    if ($progress >= 100) {
        return 'Completed';
    }

    if ($progress > 0) {
        return 'Active';
    }

    return 'On Hold';
}

function statusPriority(string $status): int
{
    return match ($status) {
        'Active' => 0,
        'On Hold' => 1,
        'Completed' => 2,
        default => 3,
    };
}

$projects = [];
$projectRows = preparedRows(
    $conn,
    'SELECT id, title, city, address, start_date, end_date, image, created_at FROM projects ORDER BY id DESC'
);

$totalIncome = (float) preparedScalar($conn, "SELECT COALESCE(SUM(amount), 0) FROM income_expense WHERE type = 'income'", '', [], 0);
$expenseTotal = (float) preparedScalar($conn, "SELECT COALESCE(SUM(amount), 0) FROM income_expense WHERE type = 'expense'", '', [], 0);
$materialTxnSpent = (float) preparedScalar($conn, 'SELECT COALESCE(SUM(total_amount), 0) FROM material_transactions', '', [], 0);
$spentTotal = $expenseTotal + $materialTxnSpent;
$remainingBudget = max($totalIncome - $spentTotal, 0);
$utilizationPct = $totalIncome > 0 ? (int) round(min(100, ($spentTotal / $totalIncome) * 100)) : 0;

$presentToday = 0;
$workerCount = 0;
$workerRows = preparedRows(
    $conn,
    'SELECT project_id, COUNT(*) AS total_workers, SUM(CASE WHEN COALESCE(shift_am, 0) = 1 OR COALESCE(shift_pm, 0) = 1 THEN 1 ELSE 0 END) AS present_workers FROM workers GROUP BY project_id'
);
$workerTotalsByProject = [];
foreach ($workerRows as $row) {
    $projectId = (int) ($row['project_id'] ?? 0);
    $totalWorkers = (int) ($row['total_workers'] ?? 0);
    $presentWorkers = (int) ($row['present_workers'] ?? 0);
    $workerTotalsByProject[$projectId] = [
        'total' => $totalWorkers,
        'present' => $presentWorkers,
    ];
    $workerCount += $totalWorkers;
    $presentToday += $presentWorkers;
}
$absentToday = max($workerCount - $presentToday, 0);

$activeSites = (int) preparedScalar($conn, "SELECT COUNT(*) FROM sites WHERE LOWER(status) = 'active'", '', [], 0);
if ($activeSites === 0) {
    $activeSites = (int) preparedScalar($conn, 'SELECT COUNT(*) FROM projects', '', [], 0);
}

$materialStockValue = (float) preparedScalar($conn, "SELECT COALESCE(SUM(total_amount), 0) FROM material_transactions", '', [], 0);
$materialQuantity = (float) preparedScalar($conn, 'SELECT COALESCE(SUM(current_stock), 0) FROM material_stock', '', [], 0);
$lowStockCount = (int) preparedScalar(
    $conn,
    'SELECT COUNT(*) FROM material_stock ms INNER JOIN materials m ON m.id = ms.material_id WHERE ms.current_stock <= m.minimum_stock',
    '',
    [],
    0
);
$pendingUpdatesCount = (int) preparedScalar($conn, "SELECT COUNT(*) FROM updates WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)", '', [], 0);

$lowStockRows = preparedRows(
    $conn,
    'SELECT m.material_name, s.site_name, ms.current_stock AS available_qty, m.minimum_stock AS min_qty, ms.updated_at AS last_movement_at
     FROM material_stock ms
    INNER JOIN materials m ON m.id = ms.material_id
    INNER JOIN sites s ON s.id = ms.site_id
     WHERE ms.current_stock <= m.minimum_stock
     ORDER BY (ms.current_stock / NULLIF(m.minimum_stock, 0)) ASC, ms.current_stock ASC
     LIMIT 6'
);

$updateRows = preparedRows(
    $conn,
    'SELECT title, description, update_type AS category, created_at
     FROM updates
     ORDER BY created_at DESC
     LIMIT 8'
);

$lowStockAlerts = array_map(static function (array $row): array {
    $availableQty = (float) ($row['available_qty'] ?? 0);
    $minQty = (float) ($row['min_qty'] ?? 0);
    return [
        'material_name' => $row['material_name'] ?? 'Material',
        'site_name' => $row['site_name'] ?? 'Site',
        'available_qty' => $availableQty,
        'min_qty' => $minQty,
        'last_movement_at' => $row['last_movement_at'] ?? null,
    ];
}, $lowStockRows);

foreach ($projectRows as $project) {
    $projectId = (int) $project['id'];
    $progress = calcProjectProgress($project);
    $status = calcProjectStatus($project, $progress);
    $workerInfo = $workerTotalsByProject[$projectId] ?? ['total' => 0, 'present' => 0];
    $workersTotal = (int) $workerInfo['total'];
    $activeWorkers = (int) $workerInfo['present'];
    $projectIncome = (float) preparedScalar($conn, "SELECT COALESCE(SUM(amount), 0) FROM income_expense WHERE project_id = ? AND type = 'income'", 'i', [$projectId], 0);
    $projectExpense = (float) preparedScalar($conn, "SELECT COALESCE(SUM(amount), 0) FROM income_expense WHERE project_id = ? AND type = 'expense'", 'i', [$projectId], 0);
    $projectRemaining = max($projectIncome - $projectExpense, 0);
    $projectStatusPriority = statusPriority($status);

    $projects[] = [
        'id' => $projectId,
        'title' => $project['title'] ?? '',
        'city' => $project['city'] ?? '',
        'address' => $project['address'] ?? '',
        'start_date' => $project['start_date'] ?? '',
        'end_date' => $project['end_date'] ?? '',
        'image' => $project['image'] ?? '',
        'progress' => $progress,
        'status' => $status,
        'status_priority' => $projectStatusPriority,
        'workers_total' => $workersTotal,
        'active_workers' => $activeWorkers,
        'budget' => $projectIncome,
        'expense' => $projectExpense,
        'remaining_budget' => $projectRemaining,
        'materials_total' => $materialQuantity,
    ];
}

usort($projects, static function (array $left, array $right): int {
    if ($left['status_priority'] !== $right['status_priority']) {
        return $left['status_priority'] <=> $right['status_priority'];
    }

    if ($left['progress'] !== $right['progress']) {
        return $right['progress'] <=> $left['progress'];
    }

    return strcmp((string) $left['title'], (string) $right['title']);
});

$projectCount = count($projects);
$averageProgress = $projectCount > 0 ? (int) round(array_sum(array_column($projects, 'progress')) / $projectCount) : 0;
$projectBudgetTotal = $totalIncome;
$projectSpentTotal = $spentTotal;
$projectPendingAmount = $remainingBudget;
$overdueProjects = 0;
$onHoldProjects = 0;
foreach ($projects as $project) {
    if (($project['status'] ?? '') === 'On Hold') {
        $onHoldProjects++;
    }

    if (!empty($project['end_date']) && strtotime($project['end_date']) < time() && (int) ($project['progress'] ?? 0) < 100) {
        $overdueProjects++;
    }
}

$latestUpdate = $updateRows[0]['created_at'] ?? null;
$lastUpdatedText = $latestUpdate ? date('d M Y, h:i A', strtotime($latestUpdate)) : date('d M Y, h:i A');

$alerts = [];
if ($overdueProjects > 0) {
    $alerts[] = [
        'title' => $overdueProjects . ' overdue project' . ($overdueProjects === 1 ? '' : 's'),
        'text' => 'Review delayed projects and update their schedules.',
        'meta' => 'Overdue',
        'severity' => 'danger',
    ];
}

if ($lowStockCount > 0) {
    $alerts[] = [
        'title' => $lowStockCount . ' low stock alert' . ($lowStockCount === 1 ? '' : 's'),
        'text' => 'Some materials have fallen below minimum stock levels.',
        'meta' => 'Low stock',
        'severity' => 'warning',
    ];
}

if ($pendingUpdatesCount > 0) {
    $alerts[] = [
        'title' => $pendingUpdatesCount . ' pending update' . ($pendingUpdatesCount === 1 ? '' : 's'),
        'text' => 'Recent updates should be reviewed and cleared.',
        'meta' => 'Review',
        'severity' => 'info',
    ];
}

if ($absentToday > 0) {
    $alerts[] = [
        'title' => $absentToday . ' worker' . ($absentToday === 1 ? '' : 's') . ' absent today',
        'text' => 'Check attendance and site coverage for today.',
        'meta' => 'Labour',
        'severity' => 'warning',
    ];
}

if (!$alerts) {
    $alerts[] = [
        'title' => 'All systems steady',
        'text' => 'No urgent alerts right now. Keep monitoring updates and stock.',
        'meta' => 'OK',
        'severity' => 'success',
    ];
}

$updates = array_map(static function (array $row): array {
    return [
        'title' => $row['title'] ?? 'Update',
        'description' => $row['description'] ?? '',
        'category' => $row['category'] ?? 'PROGRESS',
        'created_at' => $row['created_at'] ?? '',
    ];
}, $updateRows);

$activeProjectCount = 0;
foreach ($projects as $project) {
    if (($project['status'] ?? '') === 'Active') {
        $activeProjectCount++;
    }
}

$output = [
    'summary' => [
        'project_count' => $projectCount,
        'active_sites' => $activeSites,
        'total_cost' => $projectSpentTotal,
        'pending_amount' => $projectPendingAmount,
        'budget_total' => $projectBudgetTotal,
        'spent_total' => $projectSpentTotal,
        'remaining_budget' => $remainingBudget,
        'utilization_pct' => $utilizationPct,
        'average_progress' => $averageProgress,
        'worker_count' => $workerCount,
        'present_today' => $presentToday,
        'absent_today' => $absentToday,
        'low_stock_count' => $lowStockCount,
        'pending_updates_count' => $pendingUpdatesCount,
        'overdue_projects' => $overdueProjects,
        'on_hold_projects' => $onHoldProjects,
        'material_quantity' => $materialQuantity,
        'material_stock_value' => $materialStockValue,
        'last_updated' => $lastUpdatedText,
        'active_project_count' => $activeProjectCount,
    ],
    'projects' => $projects,
    'materials' => $lowStockAlerts,
    'alerts' => $alerts,
    'updates' => $updates,
];

echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
