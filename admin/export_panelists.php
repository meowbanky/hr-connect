<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings.php';

session_start();

// Security Check
if (!isset($_SESSION['user_role']) || 
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff' && $_SESSION['user_role'] !== 'superadmin')) {
    header('HTTP/1.0 403 Forbidden');
    exit('Unauthorized');
}

// Params
$search = $_GET['search'] ?? '';

// Build Query
$params = [];
$sql = "SELECT p.name, p.email, p.phone, p.role, p.status, p.created_at,
        (SELECT COUNT(*) FROM panelist_jobs pj WHERE pj.panelist_id = p.id) as job_count
        FROM panelists p";

if ($search) {
    $sql .= " WHERE p.name LIKE ? OR p.email LIKE ? OR p.username LIKE ?";
    $term = "%$search%";
    $params = [$term, $term, $term];
}

$sql .= " ORDER BY p.created_at DESC";

// Execute
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$panelists = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Headers for Download
$filename = "panelists_export_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Open Output Stream
$output = fopen('php://output', 'w');

// CSV Headers
fputcsv($output, ['Name', 'Email', 'Phone', 'Role', 'Account Status', 'Assigned Jobs', 'Date Added']);

// Data Rows
foreach ($panelists as $p) {
    fputcsv($output, [
        $p['name'],
        $p['email'],
        $p['phone'] ?? 'N/A', // Handle if phone is missing or NULL
        ucfirst($p['role']),
        ucfirst($p['status']),
        $p['job_count'],
        date('M d, Y', strtotime($p['created_at']))
    ]);
}

fclose($output);
exit;
