<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/NotificationHelper.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'mark_all_read') {
    if (NotificationHelper::markAllAsRead($user_id)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} elseif ($action === 'mark_read' && isset($_POST['id'])) {
    if (NotificationHelper::markAsRead($_POST['id'], $user_id)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} elseif ($action === 'mark_unread' && isset($_POST['id'])) {
    // Manually update since helper only has markAsRead
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 0 WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$_POST['id'], $user_id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} elseif ($action === 'archive' && isset($_POST['id'])) {
    if (NotificationHelper::archive($_POST['id'], $user_id)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} elseif ($action === 'unarchive' && isset($_POST['id'])) {
    if (NotificationHelper::unarchive($_POST['id'], $user_id)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} elseif ($action === 'delete' && isset($_POST['id'])) {
    if (NotificationHelper::delete($_POST['id'], $user_id)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
