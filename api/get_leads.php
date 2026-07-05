<?php
// TEMPORARY DEVELOPMENT MODE - AUTH DISABLED
// require_once '../auth.php';
require_once '../db.php';

header('Content-Type: application/json');

/* ── Filters ── */
$status = trim($_GET['status'] ?? '');
$source = trim($_GET['source'] ?? '');
$owner  = trim($_GET['owner']  ?? '');
$q      = trim($_GET['q']      ?? '');
$page   = max(1, intval($_GET['page']     ?? 1));
$limit  = max(1, min(100, intval($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;

$where  = [];
$params = [];
$types  = '';

if ($status !== '') { $where[] = 'status = ?';       $params[] = $status; $types .= 's'; }
if ($source !== '') { $where[] = 'source = ?';       $params[] = $source; $types .= 's'; }
if ($owner  !== '') { $where[] = 'owner  = ?';       $params[] = $owner;  $types .= 's'; }
if ($q      !== '') {
    $like     = '%' . $q . '%';
    $where[]  = '(name LIKE ? OR company LIKE ? OR email LIKE ? OR phone LIKE ?)';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types   .= 'ssss';
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ── Count ── */
$countSql = "SELECT COUNT(*) as total FROM leads $whereClause";
$countStmt = $conn->prepare($countSql);
if ($types) { $countStmt->bind_param($types, ...$params); }
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

/* ── Rows ── */
$sql  = "SELECT * FROM leads $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types   .= 'ii';

$stmt = $conn->prepare($sql);
if ($types) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

$leads = [];
while ($row = $result->fetch_assoc()) {
    $row['lead_id'] = $row['lead_code'] ?? '';
    $leads[] = $row;
}
$stmt->close();

/* ── KPI summary ── */
$kpiSql = "SELECT
    COUNT(*) AS total,
    COALESCE(SUM(status = 'New Lead'), 0) AS new_leads,
    COALESCE(SUM(status NOT IN ('Won','Lost','Hold','Not Interested','Duplicate') AND ((followup_date IS NOT NULL AND followup_date <= CURDATE()) OR (next_followup_date IS NOT NULL AND next_followup_date <= CURDATE()))), 0) AS followup_pending,
    COALESCE(SUM(status = 'Site Visit Scheduled'), 0) AS site_visits,
    COALESCE(SUM(status = 'Quotation Sent'), 0) AS quotations_sent,
    COALESCE(SUM(status = 'Won'), 0) AS won,
    COALESCE(SUM(status = 'Lost'), 0) AS lost
    FROM leads";
$kpiResult = $conn->query($kpiSql)->fetch_assoc();

/* ── Owners list ── */
$ownersResult = $conn->query("SELECT DISTINCT owner FROM leads WHERE owner != '' AND owner IS NOT NULL ORDER BY owner");
$owners = [];
while ($o = $ownersResult->fetch_assoc()) { $owners[] = $o['owner']; }

echo json_encode([
    'success' => true,
    'total'   => (int)$total,
    'page'    => $page,
    'limit'   => $limit,
    'leads'   => $leads,
    'kpi'     => $kpiResult,
    'owners'  => $owners,
]);

$conn->close();
?>
