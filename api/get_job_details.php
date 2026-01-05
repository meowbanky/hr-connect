<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$jobId = $_GET['id'] ?? 0;

if (!$jobId) {
    echo json_encode(['success' => false, 'message' => 'Invalid Job ID']);
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;

try {
    $sql = "SELECT j.*, d.name as department_name";
    
    if ($userId) {
        $sql .= ", (SELECT COUNT(*) FROM saved_jobs sj WHERE sj.job_id = j.id AND sj.user_id = ?) as is_saved";
    } else {
        $sql .= ", 0 as is_saved";
    }
    
    $sql .= " FROM job_postings j 
              LEFT JOIN departments d ON j.department_id = d.id 
              WHERE j.id = ? AND j.status = 'published'";
              
    $params = $userId ? [$userId, $jobId] : [$jobId];
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($job) {
        echo json_encode(['success' => true, 'job' => $job]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Job not found']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
