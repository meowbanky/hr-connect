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
    // Resolve Candidate ID if user is logged in
    $candidateId = 0;
    if ($userId) {
        $stmtC = $pdo->prepare("SELECT id FROM candidates WHERE user_id = ?");
        $stmtC->execute([$userId]);
        $candidateId = $stmtC->fetchColumn();
    }

    $sql = "SELECT j.*, d.name as department_name";
    
    // Subquery: Total Applicants
    $sql .= ", (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as applicant_count";
    
    // Subquery: Avg Experience
    $sql .= ", (SELECT AVG(c.years_of_experience) FROM applications a JOIN candidates c ON a.candidate_id = c.id WHERE a.job_id = j.id) as avg_experience";

    if ($userId) {
        $sql .= ", (SELECT COUNT(*) FROM saved_jobs sj WHERE sj.job_id = j.id AND sj.user_id = ?) as is_saved";
        
        if ($candidateId) {
             $sql .= ", (SELECT id FROM applications WHERE job_id = j.id AND candidate_id = ?) as application_id";
             $sql .= ", (SELECT status FROM applications WHERE job_id = j.id AND candidate_id = ?) as application_status";
             $sql .= ", (SELECT application_date FROM applications WHERE job_id = j.id AND candidate_id = ?) as application_date";
        } else {
             $sql .= ", NULL as application_id, NULL as application_status, NULL as application_date";
        }

    } else {
        $sql .= ", 0 as is_saved, NULL as application_id, NULL as application_status, NULL as application_date";
    }
    
    $sql .= " FROM job_postings j 
              LEFT JOIN departments d ON j.department_id = d.id 
              WHERE j.id = ? AND j.status = 'published'";
              
    // Params construction
    $params = [];
    if ($userId) {
        $params[] = $userId; // For is_saved
        if ($candidateId) {
            $params[] = $candidateId; // For application_id
            $params[] = $candidateId; // For application_status
            $params[] = $candidateId; // For application_date
        }
    }
    $params[] = $jobId; // For main query WHERE clause

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($job) {
        // Calculate dynamic percentile if user is logged in
        if ($userId && $candidateId) {
             // Get User's Experience (we can just fetch it again or store it above, but let's be clean)
             $stmtUser = $pdo->prepare("SELECT years_of_experience FROM candidates WHERE id = ?");
             $stmtUser->execute([$candidateId]);
             $userExp = $stmtUser->fetchColumn();
             
             if ($userExp !== false && $job['applicant_count'] > 0) {
                 // Get count of people with LESS experience
                 $stmtRank = $pdo->prepare("
                    SELECT COUNT(*) FROM applications a 
                    JOIN candidates c ON a.candidate_id = c.id 
                    WHERE a.job_id = ? AND c.years_of_experience < ?
                 ");
                 $stmtRank->execute([$jobId, $userExp]);
                 $worseThan = $stmtRank->fetchColumn();
                 
                 // Percentile
                 $job['user_experience_percentile'] = ($worseThan / $job['applicant_count']) * 100;
             }
        }
        
        echo json_encode(['success' => true, 'job' => $job]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Job not found']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
