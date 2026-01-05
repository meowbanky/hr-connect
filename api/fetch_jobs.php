<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 0;

// Fetch Params
$search = $_GET['q'] ?? '';
$location = $_GET['location'] ?? '';
$jobTypes = $_GET['type'] ?? [];
$expLevels = $_GET['level'] ?? [];
$salaryRange = $_GET['salary'] ?? '';
$datePosted = $_GET['date'] ?? '';
$sort = $_GET['sort'] ?? 'Newest';
$excludeId = $_GET['exclude_id'] ?? 0;

// Build Query
$selectFields = "j.*, d.name as department_name";

// Check for Saved Jobs Filter
$savedOnly = isset($_GET['saved_only']) && $_GET['saved_only'] === 'true';

if ($savedOnly && $userId) {
    $selectFields .= ", sj_filter.created_at as saved_at";
}

$sql = "SELECT $selectFields";

if ($userId) {
    $sql .= ", (SELECT COUNT(*) FROM saved_jobs sj WHERE sj.job_id = j.id AND sj.user_id = ?) as is_saved";
    // Check if applied (map user_id -> candidate -> application) 
    // Return specific status (e.g. 'pending', 'interviewed') or NULL if not applied.
    // Using a subquery for the status string.
    $sql .= ", (SELECT a.status FROM applications a JOIN candidates c ON a.candidate_id = c.id WHERE a.job_id = j.id AND c.user_id = ? LIMIT 1) as application_status";
} else {
    $sql .= ", 0 as is_saved, NULL as application_status";
}

$sql .= " FROM job_postings j 
        LEFT JOIN departments d ON j.department_id = d.id";

if ($savedOnly && $userId) {
    $sql .= " JOIN saved_jobs sj_filter ON j.id = sj_filter.job_id AND sj_filter.user_id = ?";
}

$sql .= " WHERE j.status = 'published'";

$params = [];
if ($userId) {
     $params[] = $userId; // For is_saved
     $params[] = $userId; // For is_applied
}
if ($savedOnly && $userId) {
    $params[] = $userId; // For the JOIN
}

if ($search) {
    $sql .= " AND (j.title LIKE ? OR j.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($location) {
    $sql .= " AND j.location LIKE ?";
    $params[] = "%$location%";
}

if (!empty($jobTypes)) {
    $placeholders = implode(',', array_fill(0, count($jobTypes), '?'));
    $sql .= " AND j.employment_type IN ($placeholders)";
    foreach ($jobTypes as $type) $params[] = $type;
}

if (!empty($expLevels)) {
    $placeholders = implode(',', array_fill(0, count($expLevels), '?'));
    $sql .= " AND j.experience_level IN ($placeholders)";
    foreach ($expLevels as $level) $params[] = $level;
}

if ($salaryRange) {
    $minSalaryFilter = (float)$salaryRange;
    $sql .= " AND (j.max_salary >= ? OR j.salary_range LIKE '%$salaryRange%')";
    $params[] = $minSalaryFilter;
}

if ($datePosted) {
    $dateFilter = '';
    switch ($datePosted) {
        case '24h': $dateFilter = date('Y-m-d H:i:s', strtotime('-24 hours')); break;
        case '7d': $dateFilter = date('Y-m-d H:i:s', strtotime('-7 days')); break;
        case '30d': $dateFilter = date('Y-m-d H:i:s', strtotime('-30 days')); break;
    }
    if ($dateFilter) {
        $sql .= " AND j.created_at >= ?";
        $params[] = $dateFilter;
    }
}

if ($excludeId) {
    $sql .= " AND j.id != ?";
    $params[] = $excludeId;
}

switch ($sort) {
    case 'Highest Salary':
        $orderSql = " ORDER BY j.max_salary DESC";
        break;
    case 'Most Relevant':
        $orderSql = " ORDER BY j.created_at DESC"; 
        break;
    default:
        $orderSql = " ORDER BY j.created_at DESC";
        break;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
if ($limit < 1) $limit = 5;
if ($limit > 20) $limit = 20; // Cap limit
$offset = ($page - 1) * $limit;

try {
    // Count Query
    // Count Query
    $countSql = "SELECT COUNT(*) FROM job_postings j";
    
    if ($savedOnly && $userId) {
        $countSql .= " JOIN saved_jobs sj_count ON j.id = sj_count.job_id AND sj_count.user_id = ?";
    }
    
    $countSql .= " WHERE j.status = 'published'";
    $countParams = [];
    
    if ($savedOnly && $userId) {
        $countParams[] = $userId;
    }
    
    // Re-apply filters for count
    if ($search) {
        $countSql .= " AND (j.title LIKE ? OR j.description LIKE ?)";
        $countParams[] = "%$search%";
        $countParams[] = "%$search%";
    }
    if ($location) {
        $countSql .= " AND j.location LIKE ?";
        $countParams[] = "%$location%";
    }
    if (!empty($jobTypes)) {
        $placeholders = implode(',', array_fill(0, count($jobTypes), '?'));
        $countSql .= " AND j.employment_type IN ($placeholders)";
        foreach ($jobTypes as $type) $countParams[] = $type;
    }
    if (!empty($expLevels)) {
        $placeholders = implode(',', array_fill(0, count($expLevels), '?'));
        $countSql .= " AND j.experience_level IN ($placeholders)";
        foreach ($expLevels as $level) $countParams[] = $level;
    }
    if ($salaryRange) {
        $minSalaryFilter = (float)$salaryRange;
        $countSql .= " AND (j.max_salary >= ? OR j.salary_range LIKE '%$salaryRange%')";
        $countParams[] = $minSalaryFilter;
    }
    if ($datePosted && $dateFilter) {
        $countSql .= " AND j.created_at >= ?";
        $countParams[] = $dateFilter;
    }
    if ($excludeId) {
        $countSql .= " AND j.id != ?";
        $countParams[] = $excludeId;
    }


    $stmt = $pdo->prepare($countSql);
    $stmt->execute($countParams);
    $totalJobs = $stmt->fetchColumn();
    $totalPages = ceil($totalJobs / $limit);

    // Final Query
    $sql .= $orderSql . " LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'jobs' => $jobs,
        'total_jobs' => $totalJobs,
        'total_pages' => $totalPages,
        'current_page' => $page
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
