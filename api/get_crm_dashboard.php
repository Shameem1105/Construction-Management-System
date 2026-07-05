<?php
// TEMPORARY DEVELOPMENT MODE - AUTH DISABLED
// require_once '../auth.php';
require_once '../db.php';

header('Content-Type: application/json');

// 1. KPI Stats
$kpiSql = "SELECT
    COUNT(*) AS total,
    COALESCE(SUM(status = 'New Lead' OR status = 'New'), 0) AS new_leads,
    COALESCE(SUM(status NOT IN ('Won','Lost','Hold','Not Interested','Duplicate') AND ((followup_date IS NOT NULL AND followup_date <= CURDATE()) OR (next_followup_date IS NOT NULL AND next_followup_date <= CURDATE()))), 0) AS followup_pending,
    COALESCE(SUM(status = 'Site Visit Scheduled'), 0) AS site_visits,
    COALESCE(SUM(status = 'Won'), 0) AS won,
    COALESCE(SUM(status = 'Lost'), 0) AS lost
    FROM leads";
$kpi = $conn->query($kpiSql)->fetch_assoc();

// 2. Funnel Status Counts
$statusSql = "SELECT status, COUNT(*) AS count FROM leads GROUP BY status";
$statusResult = $conn->query($statusSql);
$funnel = [];
while ($row = $statusResult->fetch_assoc()) {
    $funnel[$row['status']] = (int)$row['count'];
}

// 3. Source Breakdown Counts
$sourceSql = "SELECT source, COUNT(*) AS count FROM leads GROUP BY source";
$sourceResult = $conn->query($sourceSql);
$sources = [];
while ($row = $sourceResult->fetch_assoc()) {
    $sources[$row['source']] = (int)$row['count'];
}

// 4. Owner Performance
$ownerSql = "SELECT owner, COUNT(*) AS count FROM leads WHERE owner IS NOT NULL AND owner != '' GROUP BY owner ORDER BY count DESC";
$ownerResult = $conn->query($ownerSql);
$owners = [];
while ($row = $ownerResult->fetch_assoc()) {
    $owners[] = [
        'name' => $row['owner'],
        'count' => (int)$row['count']
    ];
}

// 5. Upcoming Followups
$followupSql = "SELECT id, lead_code, name, company, phone, email, followup_date 
    FROM leads 
    WHERE followup_date IS NOT NULL 
      AND status NOT IN ('Won', 'Lost', 'Hold', 'Not Interested', 'Duplicate')
    ORDER BY followup_date ASC 
    LIMIT 5";
$followupResult = $conn->query($followupSql);
$followups = [];
while ($row = $followupResult->fetch_assoc()) {
    $row['lead_id'] = $row['lead_code'] ?? '';
    
    // Calculate relative days
    $today = new DateTime();
    $today->setTime(0,0,0);
    $fDate = new DateTime($row['followup_date']);
    $fDate->setTime(0,0,0);
    $diff = $today->diff($fDate);
    $diffDays = (int)$diff->format("%r%a");
    
    $relative = '';
    if ($diffDays === 0) {
        $relative = 'Today';
    } elseif ($diffDays === 1) {
        $relative = 'Tomorrow';
    } elseif ($diffDays > 1) {
        $relative = 'in ' . $diffDays . ' days';
    } elseif ($diffDays < 0) {
        $relative = abs($diffDays) . ' days ago';
    }
    
    $row['relative_days'] = $relative;
    $row['diff_days'] = $diffDays;
    
    $followups[] = $row;
}

echo json_encode([
    'success' => true,
    'kpi' => $kpi,
    'funnel' => $funnel,
    'sources' => $sources,
    'owners' => $owners,
    'followups' => $followups
]);

$conn->close();
?>
