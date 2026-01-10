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

$action = $_POST['action'] ?? '';
$jobId = $_POST['job_id'] ?? '';

if (empty($jobId)) {
    echo json_encode(['success' => false, 'message' => 'Job ID required']);
    exit;
}

try {
    if ($action === 'delete') {
        // Check for existing applications
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ?");
        $checkStmt->execute([$jobId]);
        $appCount = $checkStmt->fetchColumn();

        if ($appCount > 0) {
            echo json_encode([
                'success' => false, 
                'message' => "Cannot delete job. There are $appCount candidate application(s) linked to this job. Please close the job instead to preserve data."
            ]);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM job_postings WHERE id = ?");
        $stmt->execute([$jobId]);
        echo json_encode(['success' => true, 'message' => 'Job deleted successfully']);
    } 
    elseif ($action === 'update_status') {
        $newStatus = $_POST['status'] ?? '';
        $validStatuses = ['draft', 'published', 'closed'];
        
        if (!in_array($newStatus, $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE job_postings SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $jobId]);
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } 
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
