<?php
// TEMPORARY DEVELOPMENT MODE - AUTH DISABLED
// require_once '../auth.php';
require_once '../db.php';

header('Content-Type: application/json');

$status  = trim($_GET['status'] ?? '');
$lead_id = intval($_GET['lead_id'] ?? 0);
$q       = trim($_GET['q'] ?? '');

$client  = trim($_GET['client'] ?? '');
$project = trim($_GET['project'] ?? '');
$sales   = trim($_GET['sales'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to   = trim($_GET['date_to'] ?? '');

$page    = max(1, intval($_GET['page'] ?? 1));
$limit   = max(1, min(100, intval($_GET['limit'] ?? 10)));
$offset  = ($page - 1) * $limit;

$where  = [];
$params = [];
$types  = '';

if ($status !== '') {
    $where[] = 'q.status = ?';
    $params[] = $status;
    $types .= 's';
}
if ($lead_id > 0) {
    $where[] = 'q.lead_id = ?';
    $params[] = $lead_id;
    $types .= 'i';
}
if ($client !== '') {
    $where[] = '(q.client_name LIKE ? OR q.client_company LIKE ?)';
    $likeClient = '%' . $client . '%';
    $params[] = $likeClient;
    $params[] = $likeClient;
    $types .= 'ss';
}
if ($project !== '') {
    $where[] = 'p.title LIKE ?';
    $params[] = '%' . $project . '%';
    $types .= 's';
}
if ($sales !== '') {
    $where[] = 'q.created_by LIKE ?';
    $params[] = '%' . $sales . '%';
    $types .= 's';
}
if ($date_from !== '') {
    $where[] = 'DATE(q.created_at) >= ?';
    $params[] = $date_from;
    $types .= 's';
}
if ($date_to !== '') {
    $where[] = 'DATE(q.created_at) <= ?';
    $params[] = $date_to;
    $types .= 's';
}

if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = '(q.quotation_number LIKE ? OR q.title LIKE ? OR q.client_name LIKE ? OR q.client_company LIKE ? OR q.created_by LIKE ? OR p.title LIKE ?)';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'ssssss';
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$joinClause = "LEFT JOIN projects p ON q.project_id = p.id";

// Count total
$countSql = "SELECT COUNT(*) as total FROM quotations q $joinClause $whereClause";
$countStmt = $conn->prepare($countSql);
if ($types !== '') { $countStmt->bind_param($types, ...$params); }
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

// Fetch rows
$sql = "SELECT q.*, p.title as project_name FROM quotations q $joinClause $whereClause ORDER BY q.created_at DESC, q.id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if ($types !== '') { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

$quotations = [];
while ($row = $result->fetch_assoc()) {
    $quotations[] = $row;
}
$stmt->close();

// Calculate simple status counts for KPIs (unfiltered)
$kpiSql = "SELECT 
    COUNT(*) AS total,
    COALESCE(SUM(status = 'Draft'), 0) AS draft,
    COALESCE(SUM(status = 'Sent'), 0) AS sent,
    COALESCE(SUM(status = 'Approved'), 0) AS approved,
    COALESCE(SUM(status = 'Revised'), 0) AS revised,
    COALESCE(SUM(status = 'Rejected'), 0) AS rejected,
    COALESCE(SUM(grand_total), 0) AS total_value
    FROM quotations";
$kpiResult = $conn->query($kpiSql)->fetch_assoc();

echo json_encode([
    'success'    => true,
    'total'      => (int)$total,
    'page'       => $page,
    'limit'      => $limit,
    'quotations' => $quotations,
    'kpi'        => $kpiResult
]);

$conn->close();
?>
