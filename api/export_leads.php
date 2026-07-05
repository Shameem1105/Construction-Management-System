<?php
// TEMPORARY DEVELOPMENT MODE - AUTH DISABLED
// require_once '../auth.php';
require_once '../db.php';

$status = trim($_GET['status'] ?? '');
$source = trim($_GET['source'] ?? '');
$owner  = trim($_GET['owner']  ?? '');
$q      = trim($_GET['q']      ?? '');
$format = trim($_GET['format'] ?? 'csv');

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
$sql  = "SELECT * FROM leads $whereClause ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if ($types) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

$leads = [];
while ($row = $result->fetch_assoc()) {
    $leads[] = $row;
}
$stmt->close();
$conn->close();

$filename = 'JGC_CRM_Leads_' . date('Y-m-d');

if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output HTML Table which Excel parses perfectly
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>';
    echo '<body>';
    echo '<table border="1">';
    echo '<tr style="background-color: #b68d40; color: #ffffff; font-weight: bold;">';
    echo '<th>Lead ID</th><th>Name</th><th>Company</th><th>Phone</th><th>Email</th><th>GST Number</th><th>Source</th><th>Status</th><th>Owner</th><th>Budget</th><th>Follow-up Date</th><th>Notes</th><th>Created At</th>';
    echo '</tr>';
    
    foreach ($leads as $lead) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($lead['lead_code'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($lead['name'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($lead['company'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($lead['phone'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($lead['email'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($lead['gst_number'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($lead['source'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($lead['status'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($lead['owner'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($lead['budget'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($lead['followup_date'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($lead['notes'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($lead['created_at'] ?? '') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</body>';
    echo '</html>';
} else {
    // Default CSV
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    // Add UTF-8 BOM
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers
    fputcsv($output, ['Lead ID', 'Name', 'Company', 'Phone', 'Email', 'GST Number', 'Source', 'Status', 'Owner', 'Budget', 'Follow-up Date', 'Notes', 'Created At']);
    
    foreach ($leads as $lead) {
        fputcsv($output, [
            $lead['lead_code'] ?? '',
            $lead['name'] ?? '',
            $lead['company'] ?? '',
            $lead['phone'] ?? '',
            $lead['email'] ?? '',
            $lead['gst_number'] ?? '',
            $lead['source'] ?? '',
            $lead['status'] ?? '',
            $lead['owner'] ?? '',
            $lead['budget'] ?? '',
            $lead['followup_date'] ?? '',
            $lead['notes'] ?? '',
            $lead['created_at'] ?? ''
        ]);
    }
    fclose($output);
}
exit;
