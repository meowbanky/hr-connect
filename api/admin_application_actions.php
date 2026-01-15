<?php
require_once __DIR__ . '/../config/db.php';
session_start();

header('Content-Type: application/json');

// Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';
$status = $_POST['status'] ?? '';

// Handle Bulk IDs or Single ID
$ids = [];
if (isset($_POST['application_ids']) && is_array($_POST['application_ids'])) {
    $ids = array_map('intval', $_POST['application_ids']);
} elseif (isset($_POST['application_id']) && $_POST['application_id'] > 0) {
    $ids[] = (int)$_POST['application_id'];
}

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'No applications selected']);
    exit;
}

try {
    if ($action === 'update_status') {
        $allowedStatuses = ['pending', 'reviewed', 'shortlisted', 'interviewed', 'offered', 'hired', 'rejected'];
        
        if (!in_array($status, $allowedStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }

        // Prepare placeholders " (?, ?, ?)"
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        // 1. Fetch details for notification BEFORE update (to ensure we have valid data)
        // Explicitly select u.id as user_id for notifications
        $fetchSql = "SELECT a.id, u.id as user_id, u.email, u.first_name, u.last_name, j.title as job_title 
                     FROM applications a 
                     JOIN candidates c ON a.candidate_id = c.id 
                     JOIN users u ON c.user_id = u.id 
                     JOIN job_postings j ON a.job_id = j.id 
                     WHERE a.id IN ($placeholders)";
        $stmt = $pdo->prepare($fetchSql);
        $stmt->execute($ids);
        $appsToNotify = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Debug log (can be checked in php error log if needed)
        // error_log("Notification Prep: IDs=" . implode(',', $ids) . " Count=" . count($appsToNotify));

        // 2. Perform Update
        $sql = "UPDATE applications SET status = ? WHERE id IN ($placeholders)";
        // Params: Status first, then all IDs
        $params = array_merge([$status], $ids);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // 3. Send Notifications
        require_once __DIR__ . '/../includes/settings.php';
        require_once __DIR__ . '/../includes/MailHelper.php';
        require_once __DIR__ . '/../includes/NotificationHelper.php';
        
        $sentCount = 0;
        $notifCount = 0;
        foreach ($appsToNotify as $app) {
            $candidateName = $app['first_name'] . ' ' . $app['last_name'];
            
            // Send Email
            if (MailHelper::sendStatusChange($app['email'], $candidateName, $app['job_title'], $status)) {
                $sentCount++;
            }
            
            // Send In-App Notification
            if (NotificationHelper::createForStatusChange($app['user_id'], $app['job_title'], $status)) {
                $notifCount++;
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Status updated successfully',
            'updated_count' => count($ids),
            'emails_sent' => $sentCount,
            'notifications_sent' => $notifCount
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
