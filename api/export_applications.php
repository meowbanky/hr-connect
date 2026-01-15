<?php
require_once __DIR__ . '/../config/db.php';
session_start();

// Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff')) {
    header('Location: /admin/login');
    exit;
}

// Parameters (Same as fetch_applications)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : '30';

try {
    // Base Query
    $sql = "SELECT 
                a.id as application_id,
                j.title as job_title,
                u.first_name,
                u.last_name,
                u.email,
                u.phone_number,
                a.status,
                a.application_date,
                c.highest_qualification,
                c.years_of_experience
            FROM applications a
            JOIN job_postings j ON a.job_id = j.id
            JOIN candidates c ON a.candidate_id = c.id
            JOIN users u ON c.user_id = u.id
            WHERE 1=1";

    $params = [];

    // Search Filter
    if (!empty($search)) {
        $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR j.title LIKE ?)";
        $term = "%$search%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    // Status Filter
    if (!empty($status) && $status !== 'all') {
        $sql .= " AND a.status = ?";
        $params[] = $status;
    }

    // Job Filter
    if ($job_id > 0) {
        $sql .= " AND a.job_id = ?";
        $params[] = $job_id;
    }

    // Date Filter
    if ($date_range !== 'all') {
        $days = (int)$date_range;
        if($days > 0) {
            $sql .= " AND a.application_date >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            $params[] = $days;
        }
    }

    $sql .= " ORDER BY a.application_date DESC"; // No Limit for Export

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set Headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="applications_export_' . date('Y-m-d') . '.csv"');

    // Open Output Stream
    $output = fopen('php://output', 'w');

    // Add Column Headings
    fputcsv($output, ['Application ID', 'Candidate Name', 'Email', 'Phone', 'Job Title', 'Applied Date', 'Status', 'Qualification', 'Experience (Years)']);

    // Add Data
    foreach ($applications as $app) {
        fputcsv($output, [
            $app['application_id'],
            $app['first_name'] . ' ' . $app['last_name'],
            $app['email'],
            $app['phone_number'],
            $app['job_title'],
            date('Y-m-d H:i', strtotime($app['application_date'])),
            ucfirst($app['status']),
            $app['highest_qualification'],
            $app['years_of_experience']
        ]);
    }

    fclose($output);

} catch (PDOException $e) {
    die("Error exporting data.");
}
?>
