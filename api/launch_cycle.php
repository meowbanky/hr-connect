<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || 
    ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff' && $_SESSION['user_role'] !== 'superadmin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Inputs
$template_id = $_POST['template_id'] ?? null;
$cycle_label = $_POST['cycle_label'] ?? ''; // e.g. "Summer 2026"
$open_date = $_POST['open_date'] ?? date('Y-m-d');
$close_date = $_POST['close_date'] ?? null;

if (!$template_id || !$cycle_label) {
    echo json_encode(['success' => false, 'message' => 'Template ID and Cycle Label are required']);
    exit;
}

try {
    // Fetch Template Details
    $stmt = $pdo->prepare("SELECT * FROM job_templates WHERE id = ?");
    $stmt->execute([$template_id]);
    $tpl = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tpl) {
        throw new Exception("Template not found");
    }

    // Insert into job_postings (The Cycle)
    // We copy the static details so the history is preserved even if template changes later
    // AND we link it via job_template_id
    $query = "INSERT INTO job_postings (
        job_template_id, cycle_label,
        title, department_id, employment_type_id, employment_type, 
        location, min_salary, max_salary, salary_range, 
        description, requirements, experience_level, 
        application_deadline, open_date, status, created_by, created_at
    ) VALUES (
        ?, ?,
        ?, ?, ?, ?, 
        ?, ?, ?, ?, 
        ?, ?, ?, 
        ?, ?, 'published', ?, NOW()
    )";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        $template_id, $cycle_label,
        $tpl['title'], 
        !empty($tpl['department_id']) ? $tpl['department_id'] : null, 
        !empty($tpl['employment_type_id']) ? $tpl['employment_type_id'] : null, 
        $tpl['employment_type'],
        $tpl['location'], 
        !empty($tpl['min_salary']) ? $tpl['min_salary'] : null, 
        !empty($tpl['max_salary']) ? $tpl['max_salary'] : null, 
        $tpl['salary_range'],
        $tpl['description'], $tpl['requirements'], $tpl['experience_level'],
        $close_date, $open_date, $_SESSION['user_id']
    ]);

    echo json_encode(['success' => true, 'message' => 'Recruitment Cycle launched successfully', 'id' => $pdo->lastInsertId()]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
