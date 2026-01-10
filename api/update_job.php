<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Inputs
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid job ID']);
    exit;
}

$title = $_POST['title'] ?? '';
$department_id = $_POST['department_id'] ?? null;
$employment_type_id = $_POST['employment_type_id'] ?? null;
$location = $_POST['location'] ?? '';
$experience_level = $_POST['experience_level'] ?? '';
$min_salary = !empty($_POST['min_salary']) ? $_POST['min_salary'] : null;
$max_salary = !empty($_POST['max_salary']) ? $_POST['max_salary'] : null;
$description = $_POST['description'] ?? '';
$requirements = $_POST['requirements'] ?? '';
$close_date = !empty($_POST['application_deadline']) ? $_POST['application_deadline'] : null;
$open_date = !empty($_POST['open_date']) ? $_POST['open_date'] : null;
$status = $_POST['status'] ?? 'draft';

// Validation
if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Job title is required']);
    exit;
}

try {
    // Basic mapping for redundancy if needed
    $empTypeEnum = '';
    if ($employment_type_id) {
        $stmtType = $pdo->prepare("SELECT name FROM employment_types WHERE id = ?");
        $stmtType->execute([$employment_type_id]);
        $empTypeRow = $stmtType->fetch(PDO::FETCH_ASSOC);
        if ($empTypeRow) $empTypeEnum = $empTypeRow['name']; 
    }

    $salary_range = '';
    if ($min_salary && $max_salary) {
        $salary_range = number_format($min_salary) . ' - ' . number_format($max_salary);
    }

    $query = "UPDATE job_postings SET 
        title = ?, 
        department_id = ?, 
        employment_type_id = ?, 
        employment_type = ?, 
        location = ?, 
        min_salary = ?, 
        max_salary = ?, 
        salary_range = ?, 
        description = ?, 
        requirements = ?, 
        experience_level = ?, 
        application_deadline = ?, 
        open_date = ?, 
        status = ?, 
        updated_at = NOW()
        WHERE id = ?";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        $title, $department_id, $employment_type_id, $empTypeEnum,
        $location, $min_salary, $max_salary, $salary_range,
        $description, $requirements, $experience_level,
        $close_date, $open_date, $status, 
        $id
    ]);

    echo json_encode(['success' => true, 'message' => 'Job updated successfully']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
