<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_GET['template_id'])) {
    echo json_encode(['success' => false, 'message' => 'Template ID required']);
    exit;
}

$template_id = (int)$_GET['template_id'];

try {
    $stmt = $pdo->prepare("
        SELECT id, cycle_label, status, created_at, open_date, application_deadline,
        (SELECT COUNT(*) FROM applications WHERE job_id = job_postings.id) as applicant_count
        FROM job_postings 
        WHERE job_template_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$template_id]);
    $cycles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'cycles' => $cycles]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
