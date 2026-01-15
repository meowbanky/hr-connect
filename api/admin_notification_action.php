<?php
// api/admin_notification_action.php
session_start();
header('Content-Type: application/json');

// 1. Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'hr_staff')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/NotificationHelper.php';

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';
$notifId = $_POST['id'] ?? null;

try {
    $success = false;

    switch ($action) {
        case 'mark_read':
            if ($notifId) {
                $success = NotificationHelper::markAsRead($notifId, $userId);
            }
            break;

        case 'mark_all_read':
            $success = NotificationHelper::markAllAsRead($userId);
            break;

        case 'delete':
            if ($notifId) {
                $success = NotificationHelper::delete($notifId, $userId);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }

    // Get updated count
    global $pdo;
    if (!$pdo) {
         require_once __DIR__ . '/../config/db.php';
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $count = $stmt->fetchColumn();

    echo json_encode(['success' => $success, 'unread_count' => $count]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error processing action']);
    error_log("Notification Action Error: " . $e->getMessage());
}
?>
